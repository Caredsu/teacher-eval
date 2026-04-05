# Frontend Integration Guide

## Overview

This guide explains how to integrate the Teacher Evaluation System API with your frontend application (React, Vue, Angular, vanilla JavaScript, etc.).

## Base URL

```javascript
const API_BASE_URL = 'http://localhost/teacher-eval/api';
```

## Authentication Setup

### 1. Login and Store Token

```javascript
async function login(username, password) {
  try {
    const response = await fetch(`${API_BASE_URL}/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ username, password })
    });

    const data = await response.json();

    if (data.success) {
      // Store token in localStorage or sessionStorage
      localStorage.setItem('auth_token', data.data.token);
      localStorage.setItem('user_role', data.data.user.role);
      localStorage.setItem('user_id', data.data.user.id);
      return data.data;
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('Login failed:', error);
    throw error;
  }
}
```

### 2. Create API Helper Function

```javascript
class TeacherEvalAPI {
  constructor(baseUrl = 'http://localhost/teacher-eval/api') {
    this.baseUrl = baseUrl;
  }

  // Get stored token
  getToken() {
    return localStorage.getItem('auth_token');
  }

  // Check if user is authenticated
  isAuthenticated() {
    return !!this.getToken();
  }

  // Generic fetch wrapper
  async request(endpoint, options = {}) {
    const url = `${this.baseUrl}${endpoint}`;
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers
    };

    // Add auth token if available and not explicitly disabled
    if (this.isAuthenticated() && !options.skipAuth) {
      headers['Authorization'] = `Bearer ${this.getToken()}`;
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'API Error');
      }

      return data;
    } catch (error) {
      console.error(`API Error (${endpoint}):`, error);
      throw error;
    }
  }

  // Auth endpoints
  login(username, password) {
    return this.request('/login', {
      method: 'POST',
      skipAuth: true,
      body: JSON.stringify({ username, password })
    });
  }

  logout() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_role');
    localStorage.removeItem('user_id');
  }

  // Teacher endpoints
  getTeachers() {
    return this.request('/teachers', { method: 'GET' });
  }

  addTeacher(teacher) {
    return this.request('/teachers', {
      method: 'POST',
      body: JSON.stringify(teacher)
    });
  }

  updateTeacher(id, updates) {
    return this.request(`/teachers/${id}`, {
      method: 'PUT',
      body: JSON.stringify(updates)
    });
  }

  deleteTeacher(id) {
    return this.request(`/teachers/${id}`, { method: 'DELETE' });
  }

  // Evaluation endpoints
  submitEvaluation(evaluation) {
    return this.request('/evaluations', {
      method: 'POST',
      skipAuth: true,
      body: JSON.stringify(evaluation)
    });
  }

  getTeacherEvaluations(teacherId) {
    return this.request(`/evaluations/${teacherId}`, { method: 'GET' });
  }

  // Department endpoints
  getDepartments() {
    return this.request('/departments', {
      method: 'GET',
      skipAuth: true
    });
  }
}

// Usage
const api = new TeacherEvalAPI();
```

## Vue.js Example

### Setup API in main.js

```javascript
import Vue from 'vue'
import App from './App.vue'
import TeacherEvalAPI from './api/client'

Vue.prototype.$api = new TeacherEvalAPI()
Vue.config.productionTip = false

new Vue({
  render: h => h(App)
}).$mount('#app')
```

### Using in Vue Component

```vue
<template>
  <div>
    <h1>Teacher Evaluations</h1>
    
    <!-- Login Form -->
    <div v-if="!isLoggedIn">
      <input v-model="username" placeholder="Username" />
      <input v-model="password" type="password" placeholder="Password" />
      <button @click="handleLogin">Login</button>
    </div>

    <!-- Teachers List -->
    <div v-if="isLoggedIn">
      <h2>Teachers</h2>
      <ul>
        <li v-for="teacher in teachers" :key="teacher.id">
          {{ teacher.firstname }} {{ teacher.lastname }}
          <button @click="viewEvaluations(teacher.id)">View Evaluations</button>
        </li>
      </ul>
    </div>

    <!-- Evaluation Statistics -->
    <div v-if="selectedTeacher">
      <h2>Evaluations for {{ selectedTeacher.firstname }}</h2>
      <p>Total: {{ evaluationStats.total }}</p>
      <p>Average Teaching: {{ evaluationStats.average_teaching }}</p>
      <p>Average Communication: {{ evaluationStats.average_communication }}</p>
      <p>Average Knowledge: {{ evaluationStats.average_knowledge }}</p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      username: '',
      password: '',
      teachers: [],
      selectedTeacher: null,
      evaluationStats: {},
      isLoggedIn: false
    }
  },
  mounted() {
    this.checkAuth()
  },
  methods: {
    checkAuth() {
      this.isLoggedIn = this.$api.isAuthenticated()
      if (this.isLoggedIn) {
        this.loadTeachers()
      }
    },
    async handleLogin() {
      try {
        await this.$api.login(this.username, this.password)
        this.isLoggedIn = true
        this.loadTeachers()
      } catch (error) {
        alert('Login failed: ' + error.message)
      }
    },
    async loadTeachers() {
      try {
        const response = await this.$api.getTeachers()
        this.teachers = response.data
      } catch (error) {
        console.error('Failed to load teachers:', error)
      }
    },
    async viewEvaluations(teacherId) {
      try {
        const response = await this.$api.getTeacherEvaluations(teacherId)
        this.selectedTeacher = response.data.teacher
        this.evaluationStats = response.data.statistics
      } catch (error) {
        console.error('Failed to load evaluations:', error)
      }
    }
  }
}
</script>
```

## React Example

### useAPI Hook

```javascript
import { useState, useCallback } from 'react'

export function useAPI() {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  const request = useCallback(async (endpoint, options = {}) => {
    setLoading(true)
    setError(null)

    try {
      const baseUrl = 'http://localhost/teacher-eval/api'
      const url = `${baseUrl}${endpoint}`
      const headers = {
        'Content-Type': 'application/json',
        ...options.headers
      }

      // Add token if available
      const token = localStorage.getItem('auth_token')
      if (token && !options.skipAuth) {
        headers['Authorization'] = `Bearer ${token}`
      }

      const response = await fetch(url, {
        ...options,
        headers
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.message || 'API Error')
      }

      return data.data
    } catch (err) {
      setError(err.message)
      throw err
    } finally {
      setLoading(false)
    }
  }, [])

  return { request, loading, error }
}

// Usage
export function LoginComponent() {
  const { request } = useAPI()
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')

  const handleLogin = async () => {
    try {
      const result = await request('/login', {
        method: 'POST',
        skipAuth: true,
        body: JSON.stringify({ username, password })
      })
      localStorage.setItem('auth_token', result.token)
      // Redirect to dashboard
    } catch (error) {
      alert('Login failed: ' + error.message)
    }
  }

  return (
    <div>
      <input value={username} onChange={e => setUsername(e.target.value)} placeholder="Username" />
      <input value={password} onChange={e => setPassword(e.target.value)} type="password" placeholder="Password" />
      <button onClick={handleLogin}>Login</button>
    </div>
  )
}
```

## Angular Example

### API Service

```typescript
import { Injectable } from '@angular/core'
import { HttpClient, HttpHeaders } from '@angular/common/http'
import { Observable } from 'rxjs'

@Injectable({
  providedIn: 'root'
})
export class TeacherEvaluationService {
  private apiUrl = 'http://localhost/teacher-eval/api'

  constructor(private http: HttpClient) {}

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('auth_token')
    let headers = new HttpHeaders({
      'Content-Type': 'application/json'
    })

    if (token) {
      headers = headers.set('Authorization', `Bearer ${token}`)
    }

    return headers
  }

  login(username: string, password: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/login`, { username, password })
  }

  getTeachers(): Observable<any> {
    return this.http.get(`${this.apiUrl}/teachers`, {
      headers: this.getHeaders()
    })
  }

  submitEvaluation(evaluation: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/evaluations`, evaluation)
  }

  getTeacherEvaluations(teacherId: string): Observable<any> {
    return this.http.get(`${this.apiUrl}/evaluations/${teacherId}`, {
      headers: this.getHeaders()
    })
  }

  getDepartments(): Observable<any> {
    return this.http.get(`${this.apiUrl}/departments`)
  }
}
```

## Vanilla JavaScript Example

```html
<!DOCTYPE html>
<html>
<head>
  <title>Teacher Evaluation System</title>
</head>
<body>
  <div id="app">
    <!-- Login Form -->
    <div id="login-form" style="display: block;">
      <input id="username" placeholder="Username" />
      <input id="password" type="password" placeholder="Password" />
      <button onclick="login()">Login</button>
    </div>

    <!-- Main App -->
    <div id="main-app" style="display: none;">
      <h1>Teachers</h1>
      <ul id="teachers-list"></ul>

      <h2>Submit Evaluation</h2>
      <form onsubmit="submitEvaluation(event)">
        <select id="teacher-select" required></select>
        <label>Teaching: <input type="number" id="rating-teaching" min="1" max="5" required /></label>
        <label>Communication: <input type="number" id="rating-communication" min="1" max="5" required /></label>
        <label>Knowledge: <input type="number" id="rating-knowledge" min="1" max="5" required /></label>
        <textarea id="feedback" required placeholder="Feedback (10-1000 chars)"></textarea>
        <button type="submit">Submit Evaluation</button>
      </form>
    </div>
  </div>

  <script>
    const API_URL = 'http://localhost/teacher-eval/api'

    async function login() {
      const username = document.getElementById('username').value
      const password = document.getElementById('password').value

      const response = await fetch(`${API_URL}/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
      })

      const data = await response.json()

      if (data.success) {
        localStorage.setItem('auth_token', data.data.token)
        document.getElementById('login-form').style.display = 'none'
        document.getElementById('main-app').style.display = 'block'
        loadTeachers()
      } else {
        alert('Login failed: ' + data.message)
      }
    }

    async function loadTeachers() {
      const token = localStorage.getItem('auth_token')
      const response = await fetch(`${API_URL}/teachers`, {
        headers: { 'Authorization': `Bearer ${token}` }
      })

      const data = await response.json()

      if (data.success) {
        const list = document.getElementById('teachers-list')
        const select = document.getElementById('teacher-select')

        list.innerHTML = ''
        select.innerHTML = '<option value="">Select a teacher...</option>'

        data.data.forEach(teacher => {
          const li = document.createElement('li')
          li.textContent = `${teacher.firstname} ${teacher.lastname} (${teacher.department})`
          list.appendChild(li)

          const option = document.createElement('option')
          option.value = teacher.id
          option.textContent = `${teacher.firstname} ${teacher.lastname}`
          select.appendChild(option)
        })
      }
    }

    async function submitEvaluation(event) {
      event.preventDefault()

      const evaluation = {
        teacher_id: document.getElementById('teacher-select').value,
        ratings: {
          teaching: parseInt(document.getElementById('rating-teaching').value),
          communication: parseInt(document.getElementById('rating-communication').value),
          knowledge: parseInt(document.getElementById('rating-knowledge').value)
        },
        feedback: document.getElementById('feedback').value
      }

      const response = await fetch(`${API_URL}/evaluations`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(evaluation)
      })

      const data = await response.json()

      if (data.success) {
        alert('Evaluation submitted successfully!')
        event.target.reset()
      } else {
        alert('Submission failed: ' + data.message)
      }
    }
  </script>
</body>
</html>
```

## Error Handling

```javascript
async function apiCall(endpoint, options = {}) {
  try {
    const response = await fetch(endpoint, options)
    const data = await response.json()

    if (!data.success) {
      // Handle API error
      switch (response.status) {
        case 400:
          console.error('Validation error:', data.message)
          break
        case 401:
          console.error('Not authenticated:', data.message)
          // Redirect to login
          break
        case 403:
          console.error('Not authorized:', data.message)
          break
        case 404:
          console.error('Not found:', data.message)
          break
        case 500:
          console.error('Server error:', data.message)
          break
        default:
          console.error('Error:', data.message)
      }
    }

    return data
  } catch (error) {
    console.error('Network error:', error)
    throw error
  }
}
```

## CORS Configuration

If your frontend is on a different domain/port, you may need to handle CORS. The backend already allows CORS for all origins. In production, you should restrict it:

**In helpers.php:**
```php
// Change this:
header('Access-Control-Allow-Origin: *');

// To this (production):
header('Access-Control-Allow-Origin: https://yourdomain.com');
```

## Response Structure

All endpoints return consistent JSON structure:

```javascript
{
  success: true|false,
  message: "string",
  data: {} | []
}
```

Always check `success` field before accessing `data`.

## Best Practices

1. **Store tokens securely:** Use httpOnly cookies for production (instead of localStorage)
2. **Implement token refresh:** Add refresh token mechanism for long sessions
3. **Error handling:** Always catch and handle API errors gracefully
4. **Loading states:** Show loading indicators during API calls
5. **Validation:** Validate input before sending to API
6. **Rate limiting:** Implement client-side rate limiting if needed
7. **Caching:** Cache teacher list and departments to reduce API calls
8. **Timeout handling:** Add request timeouts to prevent hanging requests
9. **Mobile considerations:** Use mobile-friendly endpoints (already implemented)
10. **Security:** Never expose tokens in URLs or insecure storage

## Testing API Endpoints

### Using curl

```bash
# Login
TOKEN=$(curl -X POST http://localhost/teacher-eval/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}' \
  | jq -r '.data.token')

# Get teachers
curl -X GET http://localhost/teacher-eval/api/teachers \
  -H "Authorization: Bearer $TOKEN"

# Get departments
curl -X GET http://localhost/teacher-eval/api/departments
```

### Using Postman

See `postman_collection.json` for ready-to-use Postman collection.
