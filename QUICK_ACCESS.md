# 🚀 Quick Start - Fixed Paths

## ✨ What Was Fixed

Your application now has **automatic path detection**! No matter how you install or access it, paths will work correctly.

---

## 🎯 Step 1: Initialize Database

Open PowerShell in your project folder and run:

```powershell
php scripts/init-db.php
```

Expected output:
```
✅ Collections dropped
✅ Admins collection created with sample data
✅ Teachers collection created with sample data
✅ Questions collection created with sample data
✅ Indexes created
✅ Database initialization complete!
```

---

## 🌐 Step 2: Access the Login Page

Open your browser and try **ONE** of these URLs:

### Option A (Most Common - XAMPP Root)
```
http://localhost/admin/login.php
```

### Option B (Subfolder)
```
http://localhost/teacher-eval/admin/login.php
```

### Option C (Custom Port)
```
http://localhost:3000/admin/login.php
http://localhost:8080/teacher-eval/admin/login.php
```

**If you don't know which one, try Option A first!**

---

## 🔐 Step 3: Login

Use these credentials:

**Admin Account:**
- Username: `admin`
- Password: `admin123`
- Role: **Admin** (select the tab)

**Super Admin Account:**
- Username: `superadmin`
- Password: `superadmin123`
- Role: **Staff** (select the tab)

---

## ✅ Step 4: Verify It Works

After login, you should see:
- ✅ Dashboard loads with statistics
- ✅ Sidebar navigation shows all pages
- ✅ CSS styling is applied (colorful theme)
- ✅ All links work correctly

---

## 🔍 If Something Breaks

### Problem: Blank page or 404 error
**Solution:** You're using the wrong URL. Try the other option (A or B above).

### Problem: CSS not loading (page looks plain)
**Solution:** 
1. Open browser Developer Tools (F12)
2. Look at the Network tab
3. Check if CSS file has a 404 error
4. This means you're using the wrong base URL

### Problem: Links don't work after login
**Solution:** Same as CSS - try the other URL option.

---

## 📊 How Path Detection Works

The system automatically detects your installation:

```
Your Request: http://localhost/teacher-eval/admin/login.php
                                    ↓
Detected Base: /teacher-eval
                                    ↓
CSS Path: /teacher-eval/assets/css/admin.css ✅
Admin Link: /teacher-eval/admin/dashboard.php ✅
API Call: /teacher-eval/api/questions.php ✅
```

**Your links automatically adjust!** No manual configuration needed.

---

## 🎓 Files Involved in Path Fixing

- **config/constants.php** - App constants
- **includes/helpers.php** - Path helper functions (getBasePath, assetPath, adminPath, apiPath)
- **includes/layout.php** - Master layout using path helpers
- **admin/login.php** - Updated redirects
- **PATH_CONFIGURATION.md** - Detailed guide (this file)

---

## 🚀 You're Ready!

1. ✅ Run: `php scripts/init-db.php`
2. ✅ Go to: `http://localhost/admin/login.php` (or try Option B if blank)
3. ✅ Login with: `admin` / `admin123`
4. ✅ Enjoy! 🎉

---

## 💡 Pro Tips

**Tip 1:** If you're not sure which URL to use:
- Check your XAMPP DocumentRoot setting
- Or try both and see which loads the pretty login page

**Tip 2:** Bookmark the correct URL for future access

**Tip 3:** All pages and APIs now use smart paths - they'll work with any installation method!

---

**Questions?** Check `PATH_CONFIGURATION.md` for detailed troubleshooting.

Happy evaluating! 🎓✨
