# Admin Web System - Complete Documentation

## 📋 Overview

The Admin Web Panel is a secure, role-based management system for the Teacher Evaluation System. It provides comprehensive tools for managing teachers, evaluation questions, viewing results, and controlling user access.

**Key Features:**
- Role-based access control (Admin/Super Admin)
- Responsive Bootstrap 5 UI
- Real-time data management with AJAX
- Security hardening (CSRF, XSS prevention, password hashing)
- Activity logging and audit trails
- Modern dashboard with analytics

---

## 🔐 Authentication & Login System

### Login Page (`login.php`)

**URL:** `/admin/login.php`

**Features:**
- Gradient background design
- Role selection dropdown (Admin/Super Admin)
- CSRF token protection
- Session initialization
- Automatic redirect for logged-in users

**Login Workflow:**
1. User selects role (admin/super_admin)
2. Enters username and password
3. Server validates credentials against MongoDB `admins` collection
4. Password verified using `verifyPassword()` function
5. Session created with role validation
6. Activity logged
7. User redirected to dashboard

**Security Measures:**
- CSRF token validation: `verifyCSRFToken()`
- Bcrypt password hashing: `verifyPassword(password, hash)`
- Session regeneration on login
- Failed login logging for audit trails
- Role mismatch detection

**Session Variables Set:**
```php
$_SESSION['admin_id']       // MongoDB ObjectId as string
$_SESSION['admin_username'] // Username for display
$_SESSION['admin_role']     // 'admin' or 'super_admin'
$_SESSION['just_logged_in'] // Flag for skeleton loading animation
```

### Logout (`logout.php`)

**Functionality:**
- Destroys session completely
- Logs logout activity
- Redirects to login page

---

## 📊 Dashboard

### Dashboard Page (`dashboard.php`)

**URL:** `/admin/dashboard.php`

**Display Components:**

#### 1. **Skeleton Loading Animation**
- Shows on first login (`$_SESSION['just_logged_in']` = true)
- Animated gradient effect with placeholders
- Simulates content loading for better UX
- Auto-hides when content loads

#### 2. **Statistics Cards**
Shows four key metrics:
- **Total Teachers** - Count from `teachers_collection`
- **Questions** - Count from `questions_collection`
- **Total Evaluations** - Count from `evaluations_collection`
- **Avg Rating** - Calculated from all evaluations

#### 3. **Quick Actions**
- "Add Teacher" button → `teachers.php`
- "Add Question" button → `questions.php`

#### 4. **Charts & Analytics**
- Teacher rating distribution chart
- Recent evaluations listing
- Department breakdown
- Teacher performance comparison

**Data Retrieval Logic:**
```php
// Count documents
$total_teachers = $teachers_collection->countDocuments();
$total_questions = $questions_collection->countDocuments();
$total_evaluations = $evaluations_collection->countDocuments();

// Aggregate teacher ratings
$avg_rating = $evaluations_collection->aggregate([
    ['$match' => ['teacher_id' => $teacher_id]],
    ['$unwind' => '$answers'],
    ['$group' => ['_id' => null, 'avg_rating' => ['$avg' => '$answers.rating']]]
]);
```

---

## 👨‍🏫 Teachers Management

### Teachers Page (`teachers.php`)

**URL:** `/admin/teachers.php`

**CRUD Operations:**

#### **CREATE - Add New Teacher**

**Form Fields:**
- First Name (required, min 2 chars)
- Middle Name (optional)
- Last Name (required, min 2 chars)
- Department (required, dropdown):
  - ECT (Education Technology)
  - EDUC (Education)
  - CCJE (Criminal Justice)
  - BHT (Behavioral Health)
- Email (optional, email format validation)
- Status (active/inactive, default: active)

**Validation:**
- Required fields check
- Minimum length validation
- Email format validation (if provided)
- Duplicate email prevention
- Duplicate full name prevention (case-insensitive)
- Department allowlist validation

**Database Insert:**
```php
$teachers_collection->insertOne([
    'first_name' => $first_name,
    'middle_name' => $middle_name,
    'last_name' => $last_name,
    'department' => $department,          // Must be in ALLOWED_DEPARTMENTS
    'email' => $email,
    'status' => $status,
    'created_at' => new MongoDB\BSON\UTCDateTime(),
    'created_by' => $admin_username,      // Logged-in admin
    'updated_at' => new MongoDB\BSON\UTCDateTime(),
    'updated_by' => $admin_username
]);
```

#### **READ - View Teachers**

**Display:**
- Table listing all teachers
- Columns: Name, Department, Email, Status, Actions
- Sorted by creation date (newest first)
- Department statistics sidebar
- Search/filter functionality

#### **UPDATE - Edit Teacher**

**Workflow:**
1. Click "Edit" button on teacher row
2. AJAX loads teacher data into modal form
3. Admin modifies fields
4. Form submission triggers update validation
5. MongoDB document updated with `$set`
6. Updated timestamp and admin name recorded

**Duplicate Email Check (Edit Mode):**
```php
$existing_email = $teachers_collection->findOne([
    'email' => $email,
    '_id' => ['$ne' => new MongoDB\BSON\ObjectId($teacher_id)]  // Exclude current
]);
```

#### **DELETE - Remove Teacher**

**Workflow:**
1. Click "Delete" button
2. SweetAlert2 confirmation dialog
3. CSRF token verification
4. MongoDB document deleted
5. Activity logged
6. Success message displayed

**CSRF Requirement:**
```php
if (!verifyCSRFToken(getGET('csrf'))) {
    setErrorMessage('Security token invalid.');
}
```

**Data Access:**
```php
$teachers = $teachers_collection->find([], ['sort' => ['created_at' => -1]]);
```

**Department Statistics:**
```php
$dept_stats = [];
foreach (ALLOWED_DEPARTMENTS as $dept) {
    $dept_stats[$dept] = $teachers_collection->countDocuments(['department' => $dept]);
}
```

---

## ❓ Questions Management

### Questions Page (`questions.php`)

**URL:** `/admin/questions.php`

**Features:**
- DataTables integration for responsive tables
- Modal-based forms (add/edit)
- AJAX for all operations (no page reload)
- Question ordering/sequencing
- Status toggle without modal

**AJAX Actions:**

#### **1. Get All Questions**
```php
AJAX Action: 'get_questions'
Returns: Array of all questions with formatting
Sort: By question_order, then created_at (DESC)
```

#### **2. Add Question**
```
AJAX Action: 'add_question'
Parameters:
  - question_text (required, min 5 chars)
  - category (optional, default: 'General')
  - question_order (default: 0)
  - status (active/inactive, default: active)
  - csrf_token (required)

Returns: Success/error message
Validation: Question text length, status allowlist
```

**Insert Logic:**
```php
$questions_collection->insertOne([
    'question_text' => $question_text,
    'category' => $category,
    'status' => $status,
    'question_order' => $question_order,
    'question_type' => 'rating',              // Fixed type
    'created_at' => new MongoDB\BSON\UTCDateTime(),
    'created_by' => getLoggedInAdminUsername(),
    'updated_at' => new MongoDB\BSON\UTCDateTime(),
    'updated_by' => getLoggedInAdminUsername()
]);
```

#### **3. Get Single Question (for editing)**
```
AJAX Action: 'get_question'
Parameters:
  - question_id (required, valid ObjectId)
  - csrf_token (required)

Returns: Question document as JSON
```

#### **4. Update Question**
```
AJAX Action: 'update_question'
Parameters:
  - question_id (required)
  - question_text (required, min 5 chars)
  - category (optional)
  - question_order (optional)
  - status (active/inactive)
  - csrf_token (required)

Returns: Success/error message
```

**Update Logic:**
```php
$questions_collection->updateOne(
    ['_id' => new MongoDB\BSON\ObjectId($question_id)],
    [
        '$set' => [
            'question_text' => $question_text,
            'category' => $category,
            'status' => $status,
            'question_order' => $question_order,
            'updated_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_by' => getLoggedInAdminUsername()
        ]
    ]
);
```

#### **5. Delete Question**
```
AJAX Action: 'delete_question'
Parameters:
  - question_id (required)
  - csrf_token (required)

Returns: Success/error message
```

#### **6. Toggle Question Status**
```
AJAX Action: 'toggle_status'
Parameters:
  - question_id (required)
  - csrf_token (required)

Behavior: Switches between 'active' ↔ 'inactive'
Returns: New status in response
```

**Status Toggle Logic:**
```php
$current_status = $question['status'] ?? 'active';
$new_status = $current_status === 'active' ? 'inactive' : 'active';

$questions_collection->updateOne(
    ['_id' => new MongoDB\BSON\ObjectId($question_id)],
    ['$set' => ['status' => $new_status, 'updated_at' => new MongoDB\BSON\UTCDateTime()]]
);
```

**Response Format (All AJAX):**
```json
{
  "success": true/false,
  "message": "Error or success message",
  "data": { /* response data */ }
}
```

---

## 📈 Results & Analytics

### Results Page (`results.php`)

**URL:** `/admin/results.php?teacher_id={optional_id}`

**Features:**
- Filter evaluations by teacher
- Statistical breakdown
- Question-wise analytics
- Rating distribution charts
- Exportable data

**Data Collection:**

#### **Teacher Filter**
```php
// Query defaults to all teachers
$query = [];
if (!empty($filter_teacher_id) && isValidObjectId($filter_teacher_id)) {
    $query['teacher_id'] = $filter_teacher_id;  // Filter by specific teacher
}

$evaluations = $evaluations_collection->find($query, ['sort' => ['created_at' => -1]]);
```

#### **Statistics Calculation**

**Question-wise Stats:**
```php
// For each evaluation's answer
$question_stats[$q_id] = [
    'total' => 0,           // Response count
    'sum' => 0,             // Sum of ratings
    'count' => 0,           // Count of ratings
    'ratings' => [          // Distribution
        1 => 0,             // Count of 1-star
        2 => 0,             // Count of 2-star
        3 => 0,             // Count of 3-star
        4 => 0,             // Count of 4-star
        5 => 0              // Count of 5-star
    ],
    'avg' => 0              // Calculated average
];

// Calculate average
$stats_item['avg'] = round($stats_item['sum'] / $stats_item['count'], 2);
```

**Display Cards:**
- Total Responses count
- Overall Average Rating across all questions
- Per-question breakdown with ratings distribution

#### **Chart Types:**
- Bar charts for rating distribution
- Line charts for trends over time
- Pie charts for teacher comparison

---

## 👥 Users Management

### Users Page (`users.php`)

**URL:** `/admin/users.php` (Admin Only)

**Access Control:**
```php
requireLogin();          // Must be logged in
hasRole('admin');        // Must have 'admin' role (not just teacher/viewer)
```

**AJAX Actions:**

#### **1. Get All Users**
```
AJAX Action: 'get_users'
Parameters:
  - csrf_token (required)

Returns: Array of all admin users with:
  - _id, username, email, role
  - role_display (formatted role name)
  - status, created_at, created_by
  - updated_at, updated_by, last_login
```

#### **2. Get Single User**
```
AJAX Action: 'get_user'
Parameters:
  - user_id (required, valid ObjectId)
  - csrf_token (required)

Returns: User document for editing
```

#### **3. Add New User**
```
AJAX Action: 'add_user'
Parameters:
  - username (required, min 3 chars, unique)
  - email (required, valid email format)
  - password (required, min 6 chars)
  - role (required: 'admin' or 'super_admin')
  - status (required: 'active' or 'inactive')
  - csrf_token (required)

Validation:
  - Username uniqueness
  - Email format validation
  - Password minimum length
  - Role allowlist validation
  - Status allowlist validation

Returns: Success/error message
```

**Insert Logic:**
```php
$admins_collection->insertOne([
    'username' => $username,
    'email' => $email,
    'password' => hashPassword($password),        // Bcrypt hashing
    'role' => $role,                               // 'admin' or 'super_admin'
    'status' => $status,
    'created_at' => new MongoDB\BSON\UTCDateTime(),
    'created_by' => getLoggedInAdminUsername(),
    'updated_at' => new MongoDB\BSON\UTCDateTime(),
    'updated_by' => getLoggedInAdminUsername(),
    'last_login' => null
]);
```

#### **4. Update User**
```
AJAX Action: 'update_user'
Parameters:
  - user_id (required)
  - email (required)
  - role (required)
  - status (required)
  - password (optional, leave empty to keep current)
  - csrf_token (required)

Returns: Success/error message
```

#### **5. Delete User**
```
AJAX Action: 'delete_user'
Parameters:
  - user_id (required)
  - csrf_token (required)

Restrictions: Cannot delete currently logged-in user

Returns: Success/error message
```

**Allowed Roles:**
- `'admin'` - Can manage teachers, questions, view results
- `'super_admin'` - Full access including user management

---

## 🔒 Security Features

### CSRF Protection
```php
// Generate token
$token = generateCSRFToken();    // Creates/regenerates in session

// Verify on form submission
verifyCSRFToken($_POST['csrf_token'])  // Must match session

// Used in: All forms, AJAX requests
```

**Token Storage:** `$_SESSION['csrf_token']`

### Password Security
```php
// Hashing
$hashed = hashPassword($plaintext);    // Bcrypt with cost 10

// Verification
verifyPassword($plaintext, $hash);     // Safe comparison
```

### Output Escaping (XSS Prevention)
```php
escapeOutput($user_input);   // HTML entity encoding
// Prevents: <script>, malicious HTML, etc.
```

### Input Sanitization
```php
sanitizeInput($_POST['field']);        // XSS & injection prevention
getGET('param', 'default');           // Safe GET parameter retrieval
getPOST('field', 'default');          // Safe POST parameter retrieval
```

### MongoDB ObjectId Validation
```php
isValidObjectId($id);  // Validates MongoDB ObjectId format
// Prevents: Invalid queries, injection attempts
```

### Session Security
```php
initializeSession();   // Creates session if needed
requireLogin();        // Redirects if not authenticated
// Session timeout: Configured in database.php
```

---

## 🗄️ Database Collections

### `teachers` Collection
```javascript
{
  _id: ObjectId,
  first_name: String,
  middle_name: String (optional),
  last_name: String,
  department: String (ECT|EDUC|CCJE|BHT),
  email: String (optional, unique),
  status: String (active|inactive),
  created_at: Date,
  created_by: String,
  updated_at: Date,
  updated_by: String
}
```

### `questions` Collection
```javascript
{
  _id: ObjectId,
  question_text: String,
  category: String,
  question_type: String (rating),
  question_order: Number,
  status: String (active|inactive),
  created_at: Date,
  created_by: String,
  updated_at: Date,
  updated_by: String
}
```

### `evaluations` Collection
```javascript
{
  _id: ObjectId,
  teacher_id: String (ObjectId as string),
  answers: [
    {
      question_id: String,
      rating: Number (1-5)
    }
  ],
  created_at: Date,
  session_id: String (for duplicate prevention)
}
```

### `admins` Collection
```javascript
{
  _id: ObjectId,
  username: String (unique),
  email: String,
  password: String (hashed),
  role: String (admin|super_admin),
  status: String (active|inactive),
  created_at: Date,
  created_by: String,
  updated_at: Date,
  updated_by: String,
  last_login: Date (nullable)
}
```

---

## 🛠️ Key Helper Functions

### Authentication Functions
```php
initializeSession()           // Initialize session if not exists
requireLogin()               // Redirect to login if not logged in
isLoggedIn()                 // Check if user has valid session
getLoggedInAdminUsername()   // Get current admin's username
hasRole($role)               // Check if current user has role
getUserRole()                // Get current user's role
```

### Validation Functions
```php
isValidEmail($email)         // Validate email format
isValidObjectId($id)         // Validate MongoDB ObjectId
verifyPassword($pwd, $hash)  // Verify password hash
verifyCSRFToken($token)      // Verify CSRF token
```

### Data Manipulation
```php
escapeOutput($text)          // HTML escape for output
sanitizeInput($input)        // Sanitize user input
formatDateTime($date)        // Format MongoDB date for display
formatFullName($first, $mid, $last)  // Combine name parts
objectIdToString($objectId)  // Convert ObjectId to string
```

### Message Functions
```php
setSuccessMessage($msg)      // Set flash success message
setErrorMessage($msg)        // Set flash error message
getSuccessMessage()          // Get and clear success message
getErrorMessage()            // Get and clear error message
```

### Parameter Functions
```php
getGET($key, $default)       // Safe GET parameter access
getPOST($key, $default)      // Safe POST parameter access
```

### Activity Logging
```php
logActivity($type, $description)  // Log admin actions to database
// Types: LOGIN, LOGOUT, LOGIN_FAILED, ADD_TEACHER, UPDATE_TEACHER, etc.
```

### JSON Response
```php
jsonResponse($success, $message, $data)  // Send JSON response (AJAX)
// Sets proper headers and exits
```

---

## 🎨 UI/UX Components

### Navbar (`includes/navbar.php`)
- Admin username display
- Navigation menu
- Logout button
- Role indicator
- Theme toggle

### Modals
- Add/Edit Teacher Modal
- Add/Edit Question Modal
- Add/Edit User Modal
- Delete Confirmation Modal (SweetAlert2)

### Forms
- Table-based data display
- Bootstrap form controls
- Real-time validation (client-side)
- AJAX form submission
- Success/error toast notifications

### Charts
- Chart.js integration
- Bar charts for distributions
- Line charts for trends
- Pie charts for comparisons
- Real-time chart updates

### Data Tables
- DataTables.js integration
- Sortable columns
- Searchable rows
- Responsive design
- Export functionality

---

## 📱 Responsive Design

**Breakpoints:**
- Mobile: < 576px
- Tablet: 576px - 768px
- Desktop: > 768px

**Classes Used:**
- `col-md-*` Bootstrap grid
- `container-fluid` Full-width containers
- `d-flex` Flexbox layouts
- Responsive images in modals

---

## 🚀 Navigation Flow

```
login.php
    ↓ (After Auth)
dashboard.php
    ├─→ teachers.php (Add/Edit/Delete)
    ├─→ questions.php (Add/Edit/Delete)
    ├─→ results.php (View Analytics)
    ├─→ users.php (Admin Only - Manage Users)
    ├─→ settings.php (System Settings)
    └─→ logout.php (Destroy Session)
```

---

## 📝 Common Workflows

### Adding a Teacher
1. Click "Add Teacher" button on dashboard
2. Fill form: First Name, Last Name, Department, Email (optional)
3. Select status (active/inactive)
4. Click "Add"
5. Validation performed
6. Teacher inserted to MongoDB
7. Activity logged
8. Success message shown
9. Table refreshes with new teacher

### Creating an Evaluation Question
1. Go to Questions page
2. Click "Add Question" button
3. Enter question text (min 5 chars)
4. Select category and order
5. Set status (active/inactive)
6. Click "Save"
7. AJAX validates and inserts
8. Table updates with new question
9. No page reload

### Filtering Results
1. Go to Results page
2. Use "Filter by Teacher" dropdown
3. Select specific teacher OR keep "All Teachers"
4. Form auto-submits
5. Results filtered and statistics recalculated
6. Charts updated with filtered data

### Managing Users (Admin Panel)
1. Go to Users page (Admin only)
2. View all admin users in table
3. Click "Edit" to modify role/status
4. Click "Add User" to create new admin account
5. Set username, email, password, role
6. New user can log in with credentials

---

## 🐛 Error Handling

**Error Messages:**
- Flash messages stored in session
- Display on page reload
- Auto-clear after display

**Validation Errors:**
- Returned in AJAX response
- Displayed in form or toast notification
- Prevents form submission

**Database Errors:**
- Caught in try-catch blocks
- Logged for debugging
- User-friendly message shown
- Database connection errors handled

**Security Errors:**
- CSRF token mismatch → 403 Forbidden
- Invalid role → Redirect to login
- Unauthorized AJAX → 403 response
- All logged for audit

---

## 📋 Activity Logging

All admin actions are logged to database:

**Logged Events:**
- LOGIN / LOGOUT / LOGIN_FAILED
- ADD_TEACHER / UPDATE_TEACHER / DELETE_TEACHER
- QUESTION_ADDED / QUESTION_UPDATED / QUESTION_DELETED
- USER_ADDED / USER_UPDATED / USER_DELETED
- SETTING_CHANGED
- BULK_OPERATIONS

**Log Fields:**
```php
[
  'admin_username' => String,   // Who did it
  'action_type' => String,      // What action
  'description' => String,      // Details
  'timestamp' => Date,          // When
  'ip_address' => String        // From where
]
```

---

## 🔄 Technologies Used

- **Backend:** PHP 7.4+
- **Database:** MongoDB (via PHP Driver)
- **Frontend:** Bootstrap 5.3, JavaScript
- **UI Components:** SweetAlert2 (modals), DataTables
- **Charts:** Chart.js 4.4
- **HTTP:** RESTful AJAX
- **Security:** Bcrypt, CSRF tokens, Password hashing
- **Icons:** Bootstrap Icons

---

## ⚡ Performance Features

- Skeleton loading animation on first load
- AJAX for no-page-reload operations
- DataTables server-side processing
- Efficient MongoDB queries with indexing
- CSS/JS minified in production
- CDN for Bootstrap and libraries
- Database connection pooling

---

## 📚 Related Files

**Core Files:**
- `includes/helpers.php` - Helper functions
- `includes/navbar.php` - Navigation component
- `config/database.php` - Database setup
- `assets/css/style.css` - Global styles
- `assets/js/*.js` - JavaScript utilities

**App Classes:**
- `app/Middleware/AuthMiddleware.php` - Auth checks
- `app/Middleware/CsrfMiddleware.php` - CSRF protection
- `app/Models/*.php` - Data models
- `app/Services/AuthService.php` - Auth logic

---

## 🎯 Quick Reference

**Constants:**
```php
ALLOWED_DEPARTMENTS = ['ECT', 'EDUC', 'CCJE', 'BHT']
ALLOWED_STATUS = ['active', 'inactive']
ALLOWED_ROLES = ['admin', 'super_admin']
```

**Response Codes:**
```
200 OK - Success
400 Bad Request - Validation error
403 Forbidden - Auth/CSRF error
404 Not Found - Resource not found
409 Conflict - Duplicate exists
500 Server Error - Database/server error
```

---

**Last Updated:** 2026-04-03  
**Version:** 1.0 (Production)  
**Backend:** PHP + MongoDB  
**Frontend:** Bootstrap 5 + Vanilla JS
