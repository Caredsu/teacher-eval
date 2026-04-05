# Teacher Evaluation System - Backend API

A complete REST API backend for a teacher evaluation system built with PHP 8+ and MongoDB.

## Features

- ✅ Role-based access control (Superadmin, Staff, Student)
- ✅ JWT-based authentication for admin users
- ✅ Anonymous evaluation submission for students
- ✅ Teacher management (CRUD operations)
- ✅ Evaluation statistics and aggregation
- ✅ Department management
- ✅ Input validation and sanitization
- ✅ MongoDB integration with proper indexing
- ✅ CORS enabled for frontend/mobile apps

## Technology Stack

- **PHP 8+**
- **MongoDB** (via mongodb/mongodb Composer package)
- **JSON API** for all endpoints
- **bcrypt** for password hashing
- **JWT** for session tokens

## Project Structure

```
teacher-eval/
├── api/                      # API endpoint files
│   ├── login.php            # Authentication endpoint
│   ├── teachers.php         # Teacher CRUD operations
│   ├── evaluations.php      # Evaluation submission & retrieval
│   └── departments.php      # Department listing
├── config/
│   └── database.php         # MongoDB connection configuration
├── includes/
│   └── helpers.php          # Utility functions
├── scripts/
│   └── init-db.php          # Database initialization script
├── index.php                # Main router
├── .htaccess                # Apache rewrite rules
└── vendor/                  # Composer dependencies (already present)
```

## Database Collections

### users
```json
{
  "_id": ObjectId,
  "username": "string",
  "password_hashed": "string",
  "role": "superadmin|staff",
  "created_at": ISODate
}
```

### teachers
```json
{
  "_id": ObjectId,
  "firstname": "string",
  "lastname": "string",
  "middlename": "string",
  "department": "ECT|EDUC|CCJE|BHT",
  "created_at": ISODate,
  "updated_at": ISODate
}
```

### evaluations
```json
{
  "_id": ObjectId,
  "teacher_id": ObjectId,
  "ratings": {
    "teaching": 1-5,
    "communication": 1-5,
    "knowledge": 1-5
  },
  "feedback": "string",
  "session_identifier": "string (IP address)",
  "submitted_at": ISODate
}
```

### departments
Optional collection for department information
```json
{
  "_id": ObjectId,
  "code": "ECT|EDUC|CCJE|BHT",
  "name": "string"
}
```

## Installation & Setup

### 1. Prerequisites
- PHP 8.0 or higher
- MongoDB Server running locally or remote
- Composer (for managing PHP dependencies)
- Apache with mod_rewrite enabled (for .htaccess)

### 2. Environment Setup

Create a `.env` file in the project root (optional):
```
MONGO_URI=mongodb://localhost:27017
MONGO_DB=teacher_evaluation
```

The system will default to `mongodb://localhost:27017` and database `teacher_evaluation` if not specified.

### 3. Initialize Database

Run the initialization script to create collections, indexes, and sample data:

```bash
php scripts/init-db.php
```

**Sample Credentials (created by init script):**
- Superadmin: `username: admin, password: admin123`
- Staff: `username: staff, password: staff123`

### 4. Configure Apache

Ensure your virtual host points to the project directory and mod_rewrite is enabled:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:\xampp\htdocs\teacher-eval"
    
    <Directory "C:\xampp\htdocs\teacher-eval">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## API Endpoints

### Base URL
```
http://localhost/teacher-eval/api/
```

---

### 1. Login (Authentication)

**Endpoint:** `POST /api/login`

**Description:** Authenticate admin/staff users and receive JWT token

**Authentication:** None (public)

**Request Body:**
```json
{
  "username": "admin",
  "password": "admin123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGc...",
    "user": {
      "id": "507f1f77bcf86cd799439011",
      "username": "admin",
      "role": "superadmin"
    }
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Invalid username or password",
  "data": null
}
```

---

### 2. Get All Teachers

**Endpoint:** `GET /api/teachers`

**Description:** Retrieve list of all teachers

**Authentication:** Required (Bearer Token)

**Allowed Roles:** superadmin, staff

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "success": true,
  "message": "Teachers retrieved successfully",
  "data": [
    {
      "id": "507f1f77bcf86cd799439011",
      "firstname": "John",
      "lastname": "Smith",
      "middlename": "Michael",
      "department": "ECT"
    },
    {
      "id": "507f1f77bcf86cd799439012",
      "firstname": "Sarah",
      "lastname": "Johnson",
      "middlename": "Elizabeth",
      "department": "EDUC"
    }
  ]
}
```

---

### 3. Add New Teacher

**Endpoint:** `POST /api/teachers`

**Description:** Add a new teacher to the system

**Authentication:** Required (Bearer Token)

**Allowed Roles:** superadmin only

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "firstname": "Robert",
  "lastname": "Martinez",
  "middlename": "Carlos",
  "department": "ECT"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Teacher added successfully",
  "data": {
    "id": "507f1f77bcf86cd799439013",
    "firstname": "Robert",
    "lastname": "Martinez",
    "middlename": "Carlos",
    "department": "ECT"
  }
}
```

---

### 4. Update Teacher

**Endpoint:** `PUT /api/teachers/:id`

**Description:** Update teacher information

**Authentication:** Required (Bearer Token)

**Allowed Roles:** superadmin only

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**URL Parameter:**
- `id`: Teacher MongoDB ObjectId

**Request Body (all fields optional):**
```json
{
  "firstname": "Robert",
  "lastname": "Martinez",
  "middlename": "Carlos",
  "department": "BHT"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Teacher updated successfully",
  "data": {
    "id": "507f1f77bcf86cd799439013",
    "firstname": "Robert",
    "lastname": "Martinez",
    "middlename": "Carlos",
    "department": "BHT"
  }
}
```

---

### 5. Delete Teacher

**Endpoint:** `DELETE /api/teachers/:id`

**Description:** Delete a teacher and associated evaluations

**Authentication:** Required (Bearer Token)

**Allowed Roles:** superadmin only

**Headers:**
```
Authorization: Bearer <token>
```

**URL Parameter:**
- `id`: Teacher MongoDB ObjectId

**Response:**
```json
{
  "success": true,
  "message": "Teacher deleted successfully",
  "data": null
}
```

---

### 6. Submit Evaluation

**Endpoint:** `POST /api/evaluations`

**Description:** Submit an anonymous teacher evaluation (student/mobile)

**Authentication:** None (public - anonymous)

**Request Body:**
```json
{
  "teacher_id": "507f1f77bcf86cd799439011",
  "ratings": {
    "teaching": 4,
    "communication": 5,
    "knowledge": 5
  },
  "feedback": "Great teaching methods and very knowledgeable instructor. Highly recommended!"
}
```

**Validation Rules:**
- `teacher_id`: Valid MongoDB ObjectId
- `ratings.teaching`: Integer 1-5
- `ratings.communication`: Integer 1-5
- `ratings.knowledge`: Integer 1-5
- `feedback`: String between 10-1000 characters
- Duplicate prevention: One evaluation per IP per hour

**Response:**
```json
{
  "success": true,
  "message": "Evaluation submitted successfully",
  "data": {
    "id": "507f1f77bcf86cd799439020",
    "teacher_id": "507f1f77bcf86cd799439011",
    "submitted_at": "2026-04-03 14:30:00"
  }
}
```

**Error (Duplicate Submission):**
```json
{
  "success": false,
  "message": "You have already submitted an evaluation for this teacher in the last hour",
  "data": null
}
```

---

### 7. Get Teacher Evaluations

**Endpoint:** `GET /api/evaluations/:teacher_id`

**Description:** Get all evaluations for a specific teacher with statistics

**Authentication:** Required (Bearer Token)

**Allowed Roles:** superadmin, staff

**Headers:**
```
Authorization: Bearer <token>
```

**URL Parameter:**
- `teacher_id`: Teacher MongoDB ObjectId

**Response:**
```json
{
  "success": true,
  "message": "Evaluations retrieved successfully",
  "data": {
    "teacher": {
      "id": "507f1f77bcf86cd799439011",
      "firstname": "John",
      "lastname": "Smith",
      "middlename": "Michael",
      "department": "ECT"
    },
    "statistics": {
      "total": 15,
      "average_teaching": 4.67,
      "average_communication": 4.53,
      "average_knowledge": 4.8
    },
    "evaluations": [
      {
        "id": "507f1f77bcf86cd799439020",
        "teacher_id": "507f1f77bcf86cd799439011",
        "ratings": {
          "teaching": 5,
          "communication": 4,
          "knowledge": 5
        },
        "feedback": "Excellent instructor with great communication skills.",
        "submitted_at": "2026-04-02 10:15:30"
      }
    ]
  }
}
```

---

### 8. Get Departments

**Endpoint:** `GET /api/departments`

**Description:** Get list of all departments

**Authentication:** None (public)

**Response:**
```json
{
  "success": true,
  "message": "Departments retrieved successfully",
  "data": [
    {
      "code": "ECT",
      "name": "Education and Communication Technologies"
    },
    {
      "code": "EDUC",
      "name": "Education"
    },
    {
      "code": "CCJE",
      "name": "Criminal Justice and Education"
    },
    {
      "code": "BHT",
      "name": "Business and Hospitality"
    }
  ]
}
```

---

## Error Responses

All errors follow this format:

```json
{
  "success": false,
  "message": "Error description",
  "data": null
}
```

### Common Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created |
| 400 | Bad Request - Validation error |
| 401 | Unauthorized - Invalid/missing token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 405 | Method Not Allowed |
| 500 | Server Error - Database or code error |

## Security Features

1. **Password Hashing:** bcrypt with cost factor 12
2. **Token-based Authentication:** JWT with 24-hour expiration
3. **Role-based Access Control:** Superadmin and Staff roles
4. **Input Validation:** All inputs sanitized and validated
5. **SQL/MongoDB Injection Prevention:** Using prepared queries
6. **CORS Support:** Configured for cross-origin requests
7. **Duplicate Prevention:** One evaluation per IP per hour
8. **Database Indexing:** Proper indexes for query performance

## Testing the API

### Using cURL

```bash
# 1. Login
curl -X POST http://localhost/teacher-eval/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123"
  }'

# 2. Get Teachers (replace TOKEN with actual token)
curl -X GET http://localhost/teacher-eval/api/teachers \
  -H "Authorization: Bearer TOKEN"

# 3. Add Teacher
curl -X POST http://localhost/teacher-eval/api/teachers \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "firstname": "Jane",
    "lastname": "Doe",
    "middlename": "Marie",
    "department": "EDUC"
  }'

# 4. Submit Evaluation (anonymous)
curl -X POST http://localhost/teacher-eval/api/evaluations \
  -H "Content-Type: application/json" \
  -d '{
    "teacher_id": "507f1f77bcf86cd799439011",
    "ratings": {
      "teaching": 5,
      "communication": 4,
      "knowledge": 5
    },
    "feedback": "Great teacher with excellent communication skills!"
  }'

# 5. Get Teacher Evaluations
curl -X GET http://localhost/teacher-eval/api/evaluations/507f1f77bcf86cd799439011 \
  -H "Authorization: Bearer TOKEN"

# 6. Get Departments
curl -X GET http://localhost/teacher-eval/api/departments
```

### Using Postman

1. Create a new collection
2. Import the cURL commands above
3. Set up environment variables for `TOKEN` and base URL
4. Execute requests in order

## MongoDB Connection Troubleshooting

### Connection Refused
- Ensure MongoDB server is running
- Check connection URI in config/database.php
- Verify firewall allows port 27017

### Authentication Failed
- Verify MongoDB credentials if using authentication
- Check database name is correct
- Ensure user has access to the database

### Database Not Found
- Create the database manually in MongoDB
- Or let the init-db.php script handle it

## Development Tips

1. **Enable Debug Mode:** Modify error handling in api files to show detailed errors
2. **Database Backup:** Always backup your MongoDB before running init-db.php
3. **Token Expiration:** Tokens expire after 24 hours, regenerate with login
4. **Rate Limiting:** Consider adding rate limiting for production
5. **HTTPS:** Use HTTPS in production instead of HTTP

## Future Enhancements

- [ ] Email notifications for evaluations
- [ ] PDF report generation for evaluation statistics
- [ ] Advanced filtering and search for evaluations
- [ ] Bulk import/export functionality
- [ ] Dashboard analytics and charts
- [ ] Two-factor authentication
- [ ] API rate limiting
- [ ] Database query optimization and caching
- [ ] Audit logging
- [ ] Evaluation period management

## License

This project is provided as-is for educational and development purposes.

## Support

For issues or questions:
1. Check the endpoint documentation above
2. Review error messages and HTTP status codes
3. Verify database connection
4. Check MongoDB logs for any issues
