# Quick Start Guide

## Setup (5 minutes)

### 1. Ensure MongoDB is Running

On Windows with MongoDB installed:
```powershell
# Start MongoDB service (if not already running)
net start MongoDB

# Or if using MongoDB Community Edition
mongod
```

Check connection:
```bash
mongosh  # or mongo for older versions
> exit
```

### 2. Initialize Database

```bash
cd C:\xampp\htdocs\teacher-eval
php scripts/init-db.php
```

**Output should show:**
```
✓ Users collection created
✓ Teachers collection created
✓ Evaluations collection created
✓ Departments collection created
✓ Admin users inserted
  - Superadmin: username=admin, password=admin123
  - Staff: username=staff, password=staff123
✓ Sample teachers inserted (5 teachers)
✓ Sample evaluations inserted (15 evaluations)
```

### 3. Start Apache

```bash
# XAMPP Control Panel: Start Apache
# Or from command line:
C:\xampp\apache_start.bat
```

### 4. Test the API

Open your browser or use cURL:

```bash
# Get API documentation
curl http://localhost/teacher-eval/

# Login
curl -X POST http://localhost/teacher-eval/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

You should see a response like:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGc...",
    "user": {...}
  }
}
```

## Testing All Endpoints

### Save Token
After login, copy the token and use in subsequent requests:
```bash
SET TOKEN=<your_token_here>
```

### 1. Get all teachers
```bash
curl -X GET http://localhost/teacher-eval/api/teachers \
  -H "Authorization: Bearer %TOKEN%"
```

### 2. Add new teacher
```bash
curl -X POST http://localhost/teacher-eval/api/teachers \
  -H "Authorization: Bearer %TOKEN%" \
  -H "Content-Type: application/json" \
  -d "{\"firstname\":\"Test\",\"lastname\":\"Teacher\",\"department\":\"ECT\"}"
```

### 3. Submit evaluation (no auth needed)
```bash
curl -X POST http://localhost/teacher-eval/api/evaluations \
  -H "Content-Type: application/json" \
  -d "{\"teacher_id\":\"<teacher_id>\",\"ratings\":{\"teaching\":5,\"communication\":4,\"knowledge\":5},\"feedback\":\"Excellent instructor!\"}"
```

### 4. Get evaluations
```bash
curl -X GET http://localhost/teacher-eval/api/evaluations/<teacher_id> \
  -H "Authorization: Bearer %TOKEN%"
```

### 5. Get departments
```bash
curl -X GET http://localhost/teacher-eval/api/departments
```

## Troubleshooting

### "Database Connection Error"
- MongoDB is not running
- Check MongoDB service is started
- Verify connection URI in config/database.php

### "Method not allowed" on POST requests
- Ensure Apache mod_rewrite is enabled
- Check .htaccess file exists in project directory
- Restart Apache

### "Token expired"
- Login again to get new token
- Tokens expire after 24 hours
- For development, increase expiration in helpers.php

### Can't connect to localhost
- Check Apache is running (XAMPP Control Panel)
- Verify DocumentRoot points to /teacher-eval
- Try http://127.0.0.1/teacher-eval/

## Important Files

| File | Purpose |
|------|---------|
| `config/database.php` | MongoDB connection |
| `includes/helpers.php` | Utility functions & auth |
| `api/login.php` | Authentication endpoint |
| `api/teachers.php` | Teacher CRUD |
| `api/evaluations.php` | Evaluations |
| `scripts/init-db.php` | Database setup |

## Next Steps

1. **Change default passwords:** Update admin/staff accounts in MongoDB
2. **Add custom departments:** Modify getValidDepartments() in helpers.php
3. **Setup frontend:** Build React/Vue/Angular client using endpoints in README.md
4. **Enable HTTPS:** Configure SSL for production
5. **Add logging:** Implement request/audit logging
6. **Performance:** Add caching layer for frequently accessed data

## Tips

- Always backup MongoDB before running init-db.php
- Test endpoints with Postman for easier debugging
- Check API documentation in README.md for detailed endpoint info
- Monitor MongoDB logs for connection issues
- Use environment variables for sensitive configuration

## Still Stuck?

1. Check PHP error logs: C:\xampp\apache\logs\error.log
2. Check MongoDB logs: (varies by installation)
3. Verify MongoDB is listening on localhost:27017
4. Run `mongosh --host localhost --port 27017` to test connection
5. Review vendor/mongodb/mongodb error messages

Enjoy building with the Teacher Evaluation System! 🎓
