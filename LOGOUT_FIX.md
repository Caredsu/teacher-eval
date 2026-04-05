# 🔧 Logout 404 Fix Guide

## Problem
When you click "Logout", you get:
```
Not Found - The requested URL was not found on this server
404
```

Instead of being redirected to the login page.

---

## Root Cause

You're likely accessing the admin panel via the **wrong URL path**:

❌ **Wrong:**
```
http://localhost/admin/login.php
http://localhost/admin/logout.php
```

✅ **Correct:**
```
http://localhost/teacher-eval/admin/login.php
http://localhost/teacher-eval/admin/logout.php
```

---

## Why This Happens

Your files are located at:
```
c:\xampp\htdocs\teacher-eval\admin\
```

But you're trying to access:
```
http://localhost/admin/
(which looks in c:\xampp\htdocs\admin\ ← DOESN'T EXIST!)
```

---

## ✅ Solutions

### Solution 1: Use Correct URL (Recommended)
Simply add `/teacher-eval/` to your URLs:

**Before:**
```
http://localhost/admin/login.php
```

**After:**
```
http://localhost/teacher-eval/admin/login.php
```

**After logout, you'll go to:**
```
http://localhost/teacher-eval/admin/login.php ✅
```

---

### Solution 2: Set Manual Base Path (Advanced)

If you want to use `http://localhost/admin/login.php` (without /teacher-eval/), edit:

**File:** `config/constants.php`

**Add this at the very top (after `<?php`):**
```php
<?php
// Force base path to teacher-eval subfolder
define('APP_BASE_PATH', '/teacher-eval');

// ... rest of file continues
```

Then:
1. Save the file
2. Try `http://localhost/admin/login.php` again
3. It should now automatically use the correct paths

---

### Solution 3: Auto-Redirect (Now Built-in!)

I've added automatic redirects to `login.php`:
- If you access  `/admin/login.php` but files are in `/teacher-eval/admin/`
- The system will automatically redirect you to the correct URL ✅

Just make sure you use the **complete correct path** after that!

---

## 🎯 Quick Checklist

- [ ] I'm accessing via `http://localhost/teacher-eval/admin/login.php`
- [ ] I can see the beautiful login page
- [ ] I successfully log in
- [ ] When I click "Logout", I'm redirected to the login page (not 404)
- [ ] I can log back in again

**If all checked:** You're done! 🎉

---

## 🚨 Still Getting 404?

### Check Option 1: Your Files Location
Open File Explorer and verify:
```
Does this folder exist?
c:\xampp\htdocs\teacher-eval\admin\
```

Yes → You should use `/teacher-eval/admin/` in URL
No → Files in wrong location, move them!

### Check Option 2: XAMPP DocumentRoot
1. Open `c:\xampp\apache\conf\httpd.conf`
2. Look for: `DocumentRoot`
3. If it says `DocumentRoot "c:/xampp/htdocs"`, then:
   - Use: `http://localhost/teacher-eval/admin/login.php`

### Check Option 3: Browser Cache
1. Open Developer Tools (F12)
2. Go to Network tab
3. Check "Disable cache"
4. Try logout again

This clears out old cached redirects!

---

## 📱 Access URLs Reference

| Installation | Login URL | After Logout |
|---|---|---|
| `/xampp/htdocs/teacher-eval/` | `http://localhost/teacher-eval/admin/login.php` | `→ http://localhost/teacher-eval/admin/login.php` ✅ |
| `/xampp/htdocs/` | `http://localhost/admin/login.php` | `→ http://localhost/admin/login.php` ✅ |

---

## 💡 Pro Tip

**Bookmark the correct URL** so you don't have to remember it!

Or create a shortcut on your desktop:
```
http://localhost/teacher-eval/admin/login.php
```

---

## 🆘 Still Having Issues?

Try this diagnostic:
1. Navigate to: `http://localhost/teacher-eval/admin/diagnostics.php`
2. Screenshot the "Path Detection Results" section
3. Check if paths look correct
4. If not, follow the APP_BASE_PATH solution above

---

## Technical Details (Optional)

The logout now has **3 fallback methods**:
1. Try base path detection
2. Check SCRIPT_NAME for `/teacher-eval/`
3. Try relative path as last resort

This makes logout work in almost all scenarios.

---

**After implementing the fix, logout should work perfectly!** 🚀

Need help? Run the diagnostics page! 🔍
