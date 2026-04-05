# Admin System - Testing Checklist

## 🧪 Complete Testing Guide

Use this checklist to verify all functionality works correctly.

---

## Phase 1: Setup & Database (5 minutes)

- [ ] MongoDB service is running
- [ ] PHP 7.4+ installed and running
- [ ] Project files are in `/xampp/htdocs/teacher-eval/`
- [ ] `scripts/init-db.php` runs without errors
- [ ] Database collections created (teachers, questions, admins, evaluations, activity_logs)
- [ ] All indexes created successfully
- [ ] Sample data inserted correctly

**Test Command:**
```bash
php scripts/init-db.php
```

**Expected:** All ✓ checks pass

---

## Phase 2: Authentication (10 minutes)

### Test 2.1: Login Page Load
- [ ] Navigate to `http://localhost/admin/login.php`
- [ ] Page loads without errors
- [ ] Form is visible (username, password, role dropdown)
- [ ] CSS styling is applied
- [ ] Page is responsive

### Test 2.2: Login with Super Admin
- [ ] Enter username: `superadmin`
- [ ] Enter password: `superadmin123`
- [ ] Select role: `Super Admin`
- [ ] Click "Login"
- [ ] Redirected to dashboard
- [ ] Dashboard loads with animations

### Test 2.3: Login with Admin
- [ ] Logout first
- [ ] Navigate to login page
- [ ] Enter username: `admin`
- [ ] Enter password: `admin123`
- [ ] Select role: `Admin`
- [ ] Click "Login"
- [ ] Redirected to dashboard

### Test 2.4: Invalid Login
- [ ] Try username: `admin` with password: `wrong`
- [ ] Login fails with error message
- [ ] Page doesn't reload
- [ ] Can try again
- [ ] Error message displays

### Test 2.5: Login with Wrong Role
- [ ] Try username: `admin`
- [ ] Password: `admin123`
- [ ] Select role: `Super Admin` (wrong role)
- [ ] Login fails
- [ ] Error message: "Role mismatch"

### Test 2.6: CSRF Protection
- [ ] Open browser developer tools (F12)
- [ ] Open Network tab
- [ ] Try logging in
- [ ] Verify POST request includes `csrf_token` parameter
- [ ] Token should be a long random string

### Test 2.7: Session Created
- [ ] After login, check browser storage
- [ ] Session cookie set with `PHPSESSID`
- [ ] Session data includes admin username
- [ ] Can access protected pages

---

## Phase 3: Dashboard (10 minutes)

### Test 3.1: Dashboard Load
- [ ] Navigate to dashboard after login
- [ ] Page loads completely
- [ ] No console errors (F12)
- [ ] All sections visible

### Test 3.2: Statistics Cards
- [ ] "Total Teachers" card shows number
- [ ] "Total Questions" card shows number
- [ ] "Total Evaluations" card shows number
- [ ] "Average Rating" card shows rating
- [ ] All cards have correct data

### Test 3.3: Skeleton Loading (Super Admin Only)
- [ ] Re-login with fresh session
- [ ] Watch dashboard load
- [ ] Skeleton animation visible briefly
- [ ] Charts load after skeleton

### Test 3.4: Charts
- [ ] Department breakdown chart displays
- [ ] Chart has correct data
- [ ] Rating distribution chart displays
- [ ] Both charts are responsive
- [ ] Chart legends are visible

### Test 3.5: Recent Evaluations
- [ ] Recent evaluations list visible
- [ ] Shows teacher names
- [ ] Shows ratings
- [ ] Shows dates
- [ ] Max 5 items displayed

### Test 3.6: Quick Actions
- [ ] "View Teachers" button clickable
- [ ] Navigates to teachers page
- [ ] Go back, verify "View Questions" button works
- [ ] Navigates to questions page

---

## Phase 4: Teachers Management (15 minutes)

### Test 4.1: Teachers Page Load
- [ ] Navigate to Teachers page
- [ ] Table loads with sample data
- [ ] All 4 sample teachers visible
- [ ] Department breakdown shown
- [ ] Page is responsive

### Test 4.2: Add Teacher
- [ ] Click "Add New Teacher" button
- [ ] Modal appears
- [ ] Form is visible with fields:
  - [ ] First Name
  - [ ] Middle Name
  - [ ] Last Name
  - [ ] Department
  - [ ] Email
  - [ ] Status
- [ ] Fill in test data:
  - [ ] First Name: `Robert`
  - [ ] Last Name: `Johnson`
  - [ ] Department: `ECT`
  - [ ] Status: `Active`
- [ ] Click "Save Teacher"
- [ ] Teacher appears in table
- [ ] Success message shown
- [ ] Modal closes automatically

### Test 4.3: Add Teacher - Validation
- [ ] Click "Add New Teacher"
- [ ] Leave First Name empty
- [ ] Try to submit
- [ ] Error appears: "First name is required"
- [ ] Form doesn't submit
- [ ] Enter first name with 1 character
- [ ] Error: "First name must be at least 2 characters"

### Test 4.4: Add Teacher - Email Validation
- [ ] Click "Add New Teacher"
- [ ] Enter email: `invalid-email`
- [ ] Try to submit
- [ ] Error: "Invalid email format"
- [ ] Enter valid email: `test@school.edu`
- [ ] Submits successfully

### Test 4.5: Edit Teacher
- [ ] Click Edit button on any teacher
- [ ] Modal opens with current data
- [ ] Fields are pre-filled
- [ ] Change first name to: `Updated`
- [ ] Click "Save Teacher"
- [ ] Teacher data updated in table
- [ ] Success message shown

### Test 4.6: Delete Teacher
- [ ] Click Delete button on any teacher
- [ ] Confirmation dialog appears
- [ ] Click "Yes, delete it!"
- [ ] Teacher removed from table
- [ ] Success message shown
- [ ] Deleted teacher no longer in list

### Test 4.7: Search & Filter (DataTables)
- [ ] Type in search box
- [ ] Table filters in real-time
- [ ] Shows only matching teachers
- [ ] Clear search - all teachers return

### Test 4.8: Sort by Column
- [ ] Click "Name" column header
- [ ] Table sorts alphabetically
- [ ] Click again - reverses sort
- [ ] Department sorting works
- [ ] Status sorting works

### Test 4.9: Department Statistics
- [ ] Left sidebar shows departments
- [ ] Each department shows count
- [ ] Numbers match teachers in each dept
- [ ] Stats update after adding/deleting

---

## Phase 5: Questions Management (15 minutes)

### Test 5.1: Questions Page Load
- [ ] Navigate to Questions page
- [ ] 5 sample questions display
- [ ] Questions have: text, category, order, status
- [ ] No page reload needed

### Test 5.2: Add Question
- [ ] Click "Add New Question"
- [ ] Modal appears with form
- [ ] Fill in:
  - [ ] Question: `How does the teacher provide feedback?`
  - [ ] Category: `Feedback`
  - [ ] Order: `6`
- [ ] Click "Save"
- [ ] Modal closes
- [ ] Question appears in list
- [ ] Success message shown

### Test 5.3: Question Validation
- [ ] Click "Add New Question"
- [ ] Leave question text empty
- [ ] Try submit - error shown
- [ ] Enter 3 characters only
- [ ] Error: "Question must be at least 5 characters"
- [ ] Enter proper question - submits

### Test 5.4: Edit Question
- [ ] Click Edit on any question
- [ ] Modal opens with current data
- [ ] Pre-filled fields visible
- [ ] Change category
- [ ] Click "Save"
- [ ] Updates in list without reload

### Test 5.5: Delete Question
- [ ] Click Delete on any question
- [ ] Confirmation shown
- [ ] Click "Yes, delete it!"
- [ ] Question removed immediately
- [ ] No page reload

### Test 5.6: Toggle Status
- [ ] Click status toggle button
- [ ] Status changes from Active to Inactive (or vice versa)
- [ ] Change happens instantly
- [ ] No page reload
- [ ] No duplicate entry

### Test 5.7: Question Sorting
- [ ] Questions sorted by order (ascending)
- [ ] Then by created_at (descending)
- [ ] Order is consistent

---

## Phase 6: Results & Analytics (15 minutes)

### Test 6.1: Results Page Load
- [ ] Navigate to Results page
- [ ] Statistics cards display
- [ ] Shows: total_evaluations, avg_overall, questions_evaluated, total_teachers
- [ ] Page loads completely

### Test 6.2: Filter by Teacher
- [ ] Click teacher dropdown
- [ ] "All Teachers" option visible
- [ ] Individual teacher names listed
- [ ] Select a teacher
- [ ] Page updates with filtered data
- [ ] Statistics recalculated
- [ ] URL parameter shows: `?teacher_id={id}`

### Test 6.3: Results Table
- [ ] Questions table displays
- [ ] Columns: Question, Responses, Average, 1★, 2★, 3★, 4★, 5★
- [ ] Data is accurate
- [ ] Average rounded to 2 decimals

### Test 6.4: Charts
- [ ] Rating distribution chart displays (bar chart)
- [ ] Question averages chart displays (line chart)
- [ ] Both charts are responsive
- [ ] Charts update when filtering

### Test 6.5: Statistics Calculation
- [ ] Manual verification of stats
- [ ] Average rating correctly calculated
- [ ] Total evaluations count accurate
- [ ] Question averages correct

---

## Phase 7: Users Management (10 minutes - Super Admin Only)

### Test 7.1: Users Page Access
- [ ] Login as Admin account
- [ ] Try to access `/admin/users.php`
- [ ] Redirected to dashboard (no access)
- [ ] Logout

### Test 7.2: Users Page (Super Admin)
- [ ] Login as Super Admin
- [ ] Navigate to Users page
- [ ] 2 sample users visible: superadmin, admin
- [ ] Table shows: username, email, role, status, last_login

### Test 7.3: Add User
- [ ] Click "Add New User"
- [ ] Modal appears with form fields
- [ ] Fill in:
  - [ ] Username: `testadmin`
  - [ ] Email: `testadmin@school.edu`
  - [ ] Password: `testpass123`
  - [ ] Role: `Admin`
  - [ ] Status: `Active`
- [ ] Click "Save User"
- [ ] User appears in table

### Test 7.4: Add User - Validation
- [ ] Try to add username < 3 characters
- [ ] Error: "Username must be at least 3 characters"
- [ ] Try username that already exists
- [ ] Error: "Username already taken"
- [ ] Invalid email format
- [ ] Error: "Invalid email format"

### Test 7.5: Edit User
- [ ] Click Edit on test user
- [ ] Modal opens with data
- [ ] Change password field
- [ ] New password field: `newpass123`
- [ ] Click "Save User"
- [ ] User updated

### Test 7.6: Delete User
- [ ] Click Delete on test user
- [ ] Confirmation shown
- [ ] Click "Yes, delete it!"
- [ ] User removed from table

### Test 7.7: Cannot Delete Self
- [ ] Try to delete currently logged-in user
- [ ] Error: "Cannot delete yourself"
- [ ] Delete button disabled or shows warning

---

## Phase 8: Security Testing (10 minutes)

### Test 8.1: CSRF Token Validation
- [ ] Open browser console (F12)
- [ ] Try to POST to API without CSRF token:
```javascript
fetch('/api/questions.php', {
  method: 'POST',
  body: new FormData()
}).then(r => r.json()).then(d => console.log(d));
```
- [ ] Request should fail
- [ ] Response should indicate CSRF token missing

### Test 8.2: Password Hashing
- [ ] In MongoDB, check admin collection
- [ ] Passwords are hashed (not plain text)
- [ ] Hashes start with `$2y$` (bcrypt)
- [ ] All passwords are different hashes

### Test 8.3: Input Sanitization
- [ ] In teacher name, enter: `<script>alert('xss')</script>`
- [ ] Submit form
- [ ] Script tags displayed as text (not executed)
- [ ] No JavaScript execution

### Test 8.4: Session Expiration
- [ ] Login to admin panel
- [ ] Close browser completely
- [ ] Reopen browser
- [ ] Try to access protected page
- [ ] Redirected to login (session expired)

### Test 8.5: Logout
- [ ] Click Logout button
- [ ] Redirected to login page
- [ ] Try "Back" button
- [ ] Cannot access protected pages
- [ ] Session cleared

---

## Phase 9: API Endpoints (10 minutes)

### Test 9.1: Questions API
- [ ] Open browser Network tab (F12)
- [ ] Add a question from UI
- [ ] Verify POST to `/api/questions.php`
- [ ] Check request body includes:
  - [ ] action: add_question
  - [ ] question_text
  - [ ] csrf_token
- [ ] Response JSON includes success=true

### Test 9.2: Questions API - Update
- [ ] Edit a question
- [ ] Network tab shows POST to `/api/questions.php`
- [ ] Action: update_question
- [ ] Response: success=true

### Test 9.3: Questions API - Delete
- [ ] Delete a question
- [ ] Network tab shows POST
- [ ] Action: delete_question
- [ ] Response: success=true, message with confirmation

### Test 9.4: Teachers API
- [ ] Add teacher from UI
- [ ] Network tab shows POST to `/api/teachers.php`
- [ ] Includes CSRF token
- [ ] Response successful

### Test 9.5: Users API (Super Admin Only)
- [ ] As super admin, add user
- [ ] Network shows POST to `/api/admin_users.php`
- [ ] Action: add_user
- [ ] Response includes formatted data

### Test 9.6: API Error Handling
- [ ] Try to access API without CSRF token
- [ ] API returns error response
- [ ] Message: "CSRF token mismatch" or similar
- [ ] No data processed

---

## Phase 10: Performance & Responsive Design (5 minutes)

### Test 10.1: Page Load Speed
- [ ] Dashboard loads in < 2 seconds
- [ ] Teachers page loads in < 1 second
- [ ] Questions page loads in < 1 second
- [ ] Charts render smoothly

### Test 10.2: Mobile Responsiveness
- [ ] Open any page in mobile view (F12 → Toggle device toolbar)
- [ ] Sidebar collapses to hamburger menu
- [ ] Tables remain readable
- [ ] Forms stack vertically
- [ ] Buttons remain clickable

### Test 10.3: Tablet View
- [ ] Set viewport to tablet size (768px)
- [ ] Layout adjusts appropriately
- [ ] Navigation accessible
- [ ] Content readable

### Test 10.4: Desktop View
- [ ] Set back to desktop (1920px+)
- [ ] Full sidebar visible
- [ ] Multi-column layout works
- [ ] Charts display properly

---

## Phase 11: Data Persistence (5 minutes)

### Test 11.1: Data Survives Logout/Login
- [ ] Add a teacher: `Persistence Test`
- [ ] Logout
- [ ] Login again
- [ ] Teacher still in list
- [ ] Data persisted in database

### Test 11.2: Edit Persists
- [ ] Edit the teacher's name
- [ ] Logout and login
- [ ] Changes still there

### Test 11.3: Delete Persists
- [ ] Delete the test teacher
- [ ] Logout and login
- [ ] Teacher is gone
- [ ] Delete persisted in database

---

## Phase 12: Activity Logging (5 minutes)

### Test 12.1: Check Activity Logs
- [ ] Admin performs actions: add, edit, delete
- [ ] Check MongoDB: `db.activity_logs.find()`
- [ ] Each action logged with:
  - [ ] admin_username
  - [ ] action_type (ADD_TEACHER, etc.)
  - [ ] description
  - [ ] timestamp
  - [ ] ip_address (may be local)

### Test 12.2: Disable Logs
- [ ] Temporarily comment out logActivity() calls (for testing only)
- [ ] Verify logs stop appearing
- [ ] Re-enable logging

---

## Summary

**Total Test Cases: 112**

### Pass/Fail Checklist

- [ ] Phase 1 Setup (7 checks)
- [ ] Phase 2 Authentication (21 checks)
- [ ] Phase 3 Dashboard (10 checks)
- [ ] Phase 4 Teachers (15 checks)
- [ ] Phase 5 Questions (10 checks)
- [ ] Phase 6 Results (5 checks)
- [ ] Phase 7 Users (7 checks)
- [ ] Phase 8 Security (5 checks)
- [ ] Phase 9 API (6 checks)
- [ ] Phase 10 Performance (4 checks)
- [ ] Phase 11 Data Persistence (3 checks)
- [ ] Phase 12 Activity Logging (2 checks)

### Overall Result

**Status:** [ ] All Pass [ ] Some Fail [ ] Major Issues

---

## Issue Report Template

If any test fails, document:

```
**Test Name:** [Phase X.Y - Test Name]
**Expected Result:** [What should happen]
**Actual Result:** [What actually happened]
**Steps to Reproduce:**
1. [Step 1]
2. [Step 2]
**Browser:** [Chrome/Firefox/Safari]
**Screenshot:** [Attach if possible]
**Solution Attempted:** [What you tried]
```

---

## Sign-Off

- [ ] All tests completed
- [ ] All tests passed
- [ ] System ready for production
- [ ] Documentation complete
- [ ] Team aware of features
- [ ] Backup of database taken

**Date Tested:** _____________  
**Tested By:** _____________  
**Approved By:** _____________

---

🎉 **Congratulations!** Your admin system is fully functional and tested!

Next steps:
1. Connect to frontend evaluation system
2. Set up production environment
3. Configure email notifications (optional)
4. Deploy to live server
