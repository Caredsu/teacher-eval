# New Architecture Guide

## 🏗️ Architecture Layers

### **Layer 1: Core**
Files in `src/Core/` that handle fundamental HTTP operations:

- **Request.php** - Abstracts HTTP requests
  ```php
  $request = new Request();
  $username = $request->get('username');
  $errors = $request->validate(['username' => 'required']);
  ```

- **Response.php** - Standard JSON responses
  ```php
  Response::success($data, 'Success message', 200);
  Response::error('Error message', 400);
  Response::validation(['field' => ['error']]);
  ```

- **Bootstrap.php** - Dependency injection container
  ```php
  $app = Bootstrap::getInstance();
  $db = $app->make('db');
  ```

---

### **Layer 2: HTTP (Controllers & Middleware)**

#### **Controllers** (`src/Http/Controllers/`)
Handle incoming requests and return responses:

```php
class AuthController {
    public function login() {
        $user = $this->authService->login(...);
        Response::success($user, 'Login successful');
    }
}
```

**One controller per domain:**
- `AuthController.php` - Handles /api/auth/* endpoints
- `UserController.php` - Handles /api/users/* endpoints
- `TeacherController.php` - Handles /api/teachers/* endpoints
- `EvaluationController.php` - Handles /api/evaluations/* endpoints

#### **Middleware** (`src/Http/Middleware/`)
Intercept and process requests before reaching controllers:

```php
class AuthMiddleware {
    public function handle() {
        // Check authentication
        // Validate JWT token
        // Continue to controller
    }
}
```

---

### **Layer 3: Services (Business Logic)**
Files in `src/Services/` contain all business logic:

```php
class AuthService {
    public function login($username, $password) {
        $user = $this->repository->findByUsername($username);
        if (!password_verify($password, $user['password'])) {
            throw new AuthException('Invalid credentials');
        }
        // Update last login
        return $this->formatUser($user);
    }
}
```

**Benefits:**
- Logic separated from HTTP
- Reusable from multiple places (API, console commands, jobs)
- Easy to test
- Easy to understand

---

### **Layer 4: Repositories (Database Access)**
Files in `src/Repositories/` handle all MongoDB queries:

```php
class UserRepository {
    public function findByUsername($username) {
        return $this->collection->findOne(['username' => $username]);
    }
    
    public function create($data) {
        return $this->collection->insertOne($data);
    }
}
```

**Why separate from Service?**
- Single Responsibility: Query logic in one place
- Easy to change database without touching service
- Reusable across multiple services

---

### **Layer 5: Models (Data Objects)**
Files in `src/Models/` represent database documents:

```php
class User {
    public $id;
    public $username;
    public $email;
    public $role;
    
    public function isAdmin() {
        return $this->role === 'admin';
    }
}
```

---

### **Layer 6: Validators (Input Validation)**
Files in `src/Validators/` contain validation rules:

```php
class UserValidator {
    public static function createRules() {
        return [
            'username' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:6'
        ];
    }
}
```

---

## 📊 Request Flow Example

**User submits login form:**

```
Browser (POST /api/auth/login)
↓
public_api.php (Route dispatcher)
↓
AuthMiddleware (Check format, CSRF, etc.)
↓
AuthController::login() (Extract data, call service)
↓
AuthService::login() (Run business logic)
↓
UserRepository::findByUsername() (Query database)
↓
Response::success() (Return JSON)
↓
Browser (Receives JSON response)
```

---

## 🚀 Request Structure Details

### **1. Entry Point (public_api.php)**
```php
// Routes requests to controllers
if ($path === 'api/auth/login' && $method === 'POST') {
    $controller = new AuthController($authService, $request);
    $controller->login();
}
```

### **2. Controller (AuthController)**
```php
public function login() {
    // Validate input
    $errors = $this->request->validate([...]);
    if (!empty($errors)) {
        Response::validation($errors);
    }
    
    // Call service
    $user = $this->authService->login(
        $this->request->get('username'),
        $this->request->get('password')
    );
    
    // Return response
    Response::success($user);
}
```

### **3. Service (AuthService)**
```php
public function login($username, $password) {
    // Get user from repository
    $user = $this->repository->findByUsername($username);
    
    // Validate
    if (!$user) {
        throw new AuthException('Invalid credentials');
    }
    
    // Execute business logic
    if (!password_verify($password, $user['password'])) {
        throw new AuthException('Invalid credentials');
    }
    
    // Persist changes
    $this->repository->updateLastLogin($user['_id']);
    
    // Return formatted data
    return $this->formatUser($user);
}
```

### **4. Repository (UserRepository)**
```php
public function findByUsername($username) {
    return $this->collection->findOne(['username' => $username]);
}

public function updateLastLogin($userId) {
    return $this->collection->updateOne(
        ['_id' => $userId],
        ['$set' => ['last_login' => new UTCDateTime()]]
    );
}
```

---

## 🔄 Comparison: Old vs New

### **OLD (Current)**
```
admin/users.php contains:
- HTML template
- JavaScript
- PHP logic (queries, validation, routing)
- Database queries

Problems:
- Hard to test
- Logic scattered
- Can't reuse for API
- Mix of concerns
```

### **NEW (Refactored)**
```
public_api.php
    ↓
AuthController::login()
    ↓
AuthService::login()
    ↓
UserRepository::findByUsername()
    ↓
MongoDB

Benefits:
- Easy to test each layer
- Reusable logic
- Multiple frontends can consume same API
- Clear separation of concerns
```

---

## 📁 File Organization

```
teacher-eval/
├── public_api.php            ← Entry point for API
├── index.php                 ← Entry point for old system (temporary)
├── admin/                    ← Admin web interface (uses API)
├── student/                  ← Student interface (uses API)
├── src/                      ← NEW: Our application code
│   ├── Core/                 ← Router, Request, Response
│   ├── Http/                 ← Controllers, Middleware
│   ├── Services/             ← Business logic
│   ├── Repositories/         ← Database queries
│   ├── Models/               ← Data objects
│   ├── Validators/           ← Input validation
│   ├── Exceptions/           ← Custom exceptions
│   ├── Config/               ← Configuration
│   └── NAMESPACE_STRUCTURE.md
├── tests/                    ← Test files
│   ├── Unit/
│   ├── Integration/
│   └── Manual/               ← test-*.php files moved here
├── storage/logs/             ← Application logs
├── docs/                     ← Documentation
├── .env                      ← Environment variables
├── composer.json             ← Updated with PSR-4
└── vendor/                   ← Dependencies
```

---

## 🎯 Next Steps

### **Completed:**
✅ Created `src/` directory structure
✅ Built `Response` class (standard responses)
✅ Built `Request` class (input handling)
✅ Created first `AuthService` and `AuthController`
✅ Updated `composer.json` with PSR-4 autoloading
✅ Created `.env` file

### **To Do:**
- [ ] Create more Controllers (User, Teacher, Question, Evaluation)
- [ ] Create corresponding Services
- [ ] Create Repositories
- [ ] Move test files to `tests/Manual/`
- [ ] Build Middleware (Auth, Role, Validation)
- [ ] Update admin to use new API
- [ ] Create comprehensive test suite

---

## 💡 Best Practices

1. **Keep Controllers Thin**
   - Don't put business logic in controllers
   - Controllers should only handle HTTP concerns

2. **Put Logic in Services**
   - One service per domain (Auth, User, Teacher, etc.)
   - Services should be testable

3. **Use Repositories for Database**
   - Don't write queries in Services
   - Repositories handle all DB access

4. **Validate Early**
   - Validate input in Controller using Request::validate()
   - Throw exceptions from Service if validation fails

5. **Return Consistent Responses**
   - Always use Response class for API responses
   - Include success, status, message, data fields

6. **Use .env for Configuration**
   - Never hardcode configuration
   - Use `getenv()` to access .env values

---

## 🧪 Testing Example

With this structure, testing becomes easy:

```php
class AuthServiceTest {
    public function testLoginSuccessful() {
        $repository = new MockUserRepository();
        $service = new AuthService($repository);
        $user = $service->login('admin', 'password123');
        
        $this->assertEquals('admin', $user['username']);
    }
    
    public function testLoginInvalidPassword() {
        $repository = new MockUserRepository();
        $service = new AuthService($repository);
        
        $this->expectException(AuthException::class);
        $service->login('admin', 'wrongpassword');
    }
}
```

---

## 📞 Questions?

See specific controller examples or service examples in the code comments.
