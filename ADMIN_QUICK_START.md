# Admin System - Quick Start Guide

## ⚡ Quick Setup (5 minutes)

### Step 1: Initialize Database
```bash
php scripts/init-db.php
```

**Expected Output:**
```
✓ Collections dropped
✓ Admins collection created with sample data
✓ Teachers collection created with sample data
✓ Questions collection created with sample data
✓ Indexes created
✓ Database initialization complete!
```

### Step 2: Access Admin Panel
Navigate to: `http://localhost/xampp/teacher-eval/admin/login.php`

### Step 3: Login with Sample Credentials

**Option A: Super Admin (Full Access)**
- Username: `superadmin`
- Password: `superadmin123`
- Role: Super Admin

**Option B: Admin (Limited Access)**
- Username: `admin`
- Password: `admin123`
- Role: Admin

---

## 🎯 What You Can Do

### Dashboard
- View overall statistics
- See charts and analytics
- Quick navigation to other sections

### Teachers Management
- ✅ Add new teachers (click "Add New Teacher")
- ✅ Edit teacher info (click Edit button)
- ✅ Delete teachers (click Delete button)
- ✅ View department breakdown
- ✅ Search and filter

### Questions Management
- ✅ Add evaluation questions
- ✅ Edit questions
- ✅ Delete questions
- ✅ Toggle question status (active/inactive)
- ✅ Set question order

### Results & Analytics
- ✅ View evaluation results
- ✅ Filter by teacher
- ✅ See question-wise statistics
- ✅ View charts and distribution
- ✅ Download or export data

### User Management (Super Admin Only)
- ✅ Add new admin users
- ✅ Edit user details
- ✅ Change user roles
- ✅ Manage user status
- ✅ Delete inactive users

---

## 🔧 Troubleshooting

### Issue: "Database connection failed"
**Solution:** Check MongoDB is running
```bash
# Windows - Check MongoDB service
netstat -ano | findstr :27017
```

### Issue: "Collection not found"
**Solution:** Run init-db.php script
```bash
php scripts/init-db.php
```

### Issue: "Login failed"
**Solution:** Verify:
- Username is correct
- Password matches exactly
- Role is selected
- User account is active

### Issue: "CSRF token mismatch"
**Solution:** 
- Refresh page
- Clear browser cache
- Check session is active
- Verify POST request includes CSRF token

### Issue: "Access denied"
**Solution:**
- Verify user role (super_admin required for Users page)
- Check session is still active (may need to re-login)
- Confirm user account status is 'active'

---

## 📝 Sample Data

### Teachers
| Name | Department | Email | Status |
|------|-----------|-------|--------|
| Maria Santos | ECT | maria.santos@school.edu | Active |
| Juan Rivera | EDUC | juan.rivera@school.edu | Active |
| Ana Garcia | CCJE | ana.garcia@school.edu | Active |
| Carlos Lopez | BHT | carlos.lopez@school.edu | Active |

### Questions
| Questions | Category | Order | Status |
|-----------|----------|-------|--------|
| How well does the teacher explain concepts? | Teaching | 1 | Active |
| How prepared is the teacher for classes? | Preparation | 2 | Active |
| How responsive is the teacher to student questions? | Engagement | 3 | Active |
| How fair are the grading practices? | Fairness | 4 | Active |
| How effectively does the teacher use classroom time? | Efficiency | 5 | Active |

### Admin Users
| Username | Email | Role | Password |
|----------|-------|------|----------|
| superadmin | superadmin@school.edu | Super Admin | superadmin123 |
| admin | admin@school.edu | Admin | admin123 |

---

## 🎬 Common Workflows

### Workflow 1: Add a New Teacher

1. Login with admin account
2. Click "Teachers" in sidebar
3. Click "Add New Teacher" button
4. Fill in form:
   - First Name: `Jose`
   - Last Name: `Reyes`
   - Department: `ECT`
   - Email: `jose.reyes@school.edu` (optional)
5. Click "Save Teacher"
6. See success message
7. Teacher appears in table

### Workflow 2: Create a Evaluation Question

1. Click "Questions" in sidebar
2. Click "Add New Question" button
3. Fill in:
   - Question: `How well does the teacher demonstrate subject expertise?`
   - Category: `Expertise`
   - Order: `6`
4. Click "Save Question"
5. Question appears in list

### Workflow 3: Create New Admin User (Super Admin Only)

1. Login with super_admin account
2. Click "Users" in sidebar
3. Click "Add New User" button
4. Fill in:
   - Username: `newadmin`
   - Email: `newadmin@school.edu`
   - Password: `secure_password`
   - Role: `Admin`
   - Status: `Active`
5. Click "Save User"
6. User appears in table

### Workflow 4: View Evaluation Results

1. Click "Results" in sidebar
2. (Optional) Select teacher from dropdown to filter
3. View statistics cards
4. See charts and distribution
5. Scroll down for detailed breakdown

---

## 🔐 Security Reminders

✅ **Do:**
- Change default passwords in production
- Use strong passwords (min 8 characters)
- Logout when finished
- Clear browser cache periodically
- Keep MongoDB credentials secure
- Update PHP and MongoDB regularly

❌ **Don't:**
- Share admin credentials
- Use same password across accounts
- Store passwords in plain text
- Disable CSRF protection
- Expose API endpoints to public
- Run init-db.php in production mode

---

## 📊 Data Validation Rules

### Teachers
- First Name: Required, 2+ characters
- Last Name: Required, 2+ characters
- Middle Name: Optional
- Department: Required, must be: ECT, EDUC, CCJE, or BHT
- Email: Optional but must be unique if provided
- Status: active or inactive

### Questions
- Question Text: Required, 5+ characters
- Category: Optional
- Question Order: Optional (defaults to 0)
- Status: active or inactive

### Users
- Username: Required, 3+ characters, must be unique
- Email: Required, valid email format
- Password: Required, 6+ characters (min 10 recommended)
- Role: admin or super_admin
- Status: active or inactive

---

## 🚀 API Examples

If you want to make direct API calls:

### Add Teacher
```javascript
const data = new FormData();
data.append('action', 'add');
data.append('first_name', 'John');
data.append('last_name', 'Doe');
data.append('department', 'ECT');

fetch('/api/teachers.php', {
  method: 'POST',
  body: data
})
.then(r => r.json())
.then(data => console.log(data));
```

### Add Question
```javascript
const formData = new FormData();
formData.append('action', 'add_question');
formData.append('question_text', 'How effective is the teacher?');
formData.append('category', 'Effectiveness');
formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

fetch('/api/questions.php', {
  method: 'POST',
  body: formData
})
.then(r => r.json())
.then(result => {
  if(result.success) {
    alert('Question added!');
  }
});
```

---

## 📱 Access Points

| Page | URL | Required Role | Purpose |
|------|-----|---|---------|
| Login | `/admin/login.php` | None | Authentication |
| Dashboard | `/admin/dashboard.php` | Admin/Super Admin | Overview & stats |
| Teachers | `/admin/teachers.php` | Admin/Super Admin | Manage teachers |
| Questions | `/admin/questions.php` | Admin/Super Admin | Manage questions |
| Results | `/admin/results.php` | Admin/Super Admin | View analytics |
| Users | `/admin/users.php` | Super Admin | Manage admins |
| Logout | `/admin/logout.php` | Admin/Super Admin | End session |

---

## 🎨 Customization Tips

### Change Dashboard Colors
Edit `assets/css/admin.css`:
```css
:root {
  --primary: #667eea;      /* Change this */
  --secondary: #764ba2;    /* Change this */
  --success: #48bb78;      /* Change this */
}
```

### Add More Questions
Edit `scripts/init-db.php` and add to questions array:
```php
[
  'question_text' => 'Your new question?',
  'category' => 'New Category',
  'question_order' => 6,
  'status' => 'active'
]
```

### Add More Departments
Edit `includes/helpers.php`:
```php
ALLOWED_DEPARTMENTS = ['ECT', 'EDUC', 'CCJE', 'BHT', 'NEW_DEPT']
```

---

## ✅ Verification Checklist

After setup, verify:

- [ ] Database initialized successfully
- [ ] Can login with superadmin account
- [ ] Can login with admin account
- [ ] Dashboard loads with statistics
- [ ] Can add a new teacher
- [ ] Can edit a teacher
- [ ] Can delete a teacher
- [ ] Can add a new question
- [ ] Can toggle question status
- [ ] Can view results page
- [ ] Can access Users page (super admin only)
- [ ] Logout works correctly
- [ ] Re-login works after logout

---

## 📞 Need Help?

1. **Check logs:** View activity_logs collection in MongoDB
2. **Read documentation:** See `ADMIN_SYSTEM_DOCUMENTATION.md`
3. **Browser console:** Check for JavaScript errors (F12)
4. **Network tab:** Check API responses (F12 → Network)
5. **PHP errors:** Check Apache error logs

---

## 🎓 Next Steps

1. ✅ Initialize database
2. ✅ Test login functionality
3. ✅ Explore each admin page
4. ✅ Test CRUD operations
5. ✅ Customize colors/text
6. ✅ Add your own data
7. ✅ Connect to frontend evaluation system
8. ✅ Deploy to production

---

**Ready to go!** 🚀

Start with: `php scripts/init-db.php`, then navigate to `http://localhost/admin/login.php`

Good luck!
