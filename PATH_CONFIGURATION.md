# Path Configuration Guide

## 🔧 Fix Paths for Your Environment

The application supports two folder structures:

### Option 1: Root Installation
If installed at `http://localhost/` (xampp DocumentRoot is `/`):
- Access via: `http://localhost/admin/login.php`
- Paths: `/assets/`, `/admin/`, `/api/` ✅ (Already correct)

### Option 2: Subfolder Installation
If installed at `http://localhost/teacher-eval/`:
- Access via: `http://localhost/teacher-eval/admin/login.php`
- Paths: `/teacher-eval/assets/`, `/teacher-eval/admin/`, etc.

---

## ✅ Automatic Path Detection

The path helpers automatically detect your installation:

```php
getBasePath()  // Returns '' or '/teacher-eval' based on current path
assetPath('css/admin.css')      // Returns correct '/assets/css/admin.css' or '/teacher-eval/assets/css/admin.css'
adminPath('dashboard.php')      // Returns correct '/admin/dashboard.php' or '/teacher-eval/admin/dashboard.php'
apiPath('questions.php')        // Returns correct '/api/questions.php' or '/teacher-eval/api/questions.php'
```

---

## 🚀 How to Access

### Method 1: Direct Access (Recommended)
```
http://localhost/admin/login.php
```

### Method 2: Subfolder Access
```
http://localhost/teacher-eval/admin/login.php
```

### Method 3: Port-specific
```
http://localhost:3000/admin/login.php
http://localhost:8080/teacher-eval/admin/login.php
```

---

## 📝 Updated Files

The following files now use automatic path detection:

✅ **includes/helpers.php** - Path helper functions added
✅ **includes/layout.php** - Uses path helpers for CSS/JS
✅ **admin/login.php** - Updated redirects
✅ **admin/logout.php** - Will use path helpers automatically

---

## 🔍 Testing Path Detection

To verify paths are correct, add this to any page:

```php
<?php
echo "Base Path: " . getBasePath() . "<br>";
echo "Asset Path: " . assetPath('css/admin.css') . "<br>";
echo "Admin Path: " . adminPath('dashboard.php') . "<br>";
echo "API Path: " . apiPath('questions.php') . "<br>";
?>
```

---

## 🎯 Configuration Option (Advanced)

If automatic detection doesn't work, manually override in `config/constants.php`:

```php
// At the top of config/constants.php:
define('APP_BASE_PATH', '/teacher-eval');  // or '' for root
```

Then in `includes/helpers.php`, modify `getBasePath()`:

```php
function getBasePath() {
    static $basePath = null;
    
    if ($basePath === null) {
        // Use manual override if defined
        if (defined('APP_BASE_PATH')) {
            $basePath = APP_BASE_PATH;
        } else {
            // Auto-detect
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($scriptName, '/teacher-eval/') !== false) {
                $basePath = '/teacher-eval';
            } else {
                $basePath = '';
            }
        }
    }
    
    return $basePath;
}
```

---

## 🐛 Troubleshooting

### Issue: Pages show 404 or CSS not loading
**Solution:** Check which URL structure matches your installation:
- If files are in `/xampp/htdocs/teacher-eval/`, try both:
  - `http://localhost/admin/login.php` (if DocumentRoot is `/teacher-eval`)
  - `http://localhost/teacher-eval/admin/login.php` (if DocumentRoot is `/xampp/htdocs`)

### Issue: Links are broken after login
**Solution:** Verify paths by checking browser network tab (F12 → Network)
- Look for 404 errors on CSS/JS files
- Check if CSS file path is `/assets/css/admin.css` or `/teacher-eval/assets/css/admin.css`

### Issue: Automatic detection not working
**Solution:** Manually set `APP_BASE_PATH` in `config/constants.php`:
```php
define('APP_BASE_PATH', '/teacher-eval');
```

---

## ✨ Smart Links

All navigation links now work correctly:
- Dashboard link: automatically adjusted
- Teachers page link: automatically adjusted
- API calls: automatically adjusted
- Asset includes: automatically adjusted

**No manual path configuration needed!** 🎉

---

## 📊 Path Resolution Examples

| Scenario | getBasePath() | assetPath('js/main.js') | adminPath('dashboard.php') |
|----------|---------------|------------------------|---------------------------|
| `http://localhost/admin/` | '' | `/assets/js/main.js` | `/admin/dashboard.php` |
| `http://localhost/teacher-eval/admin/` | `/teacher-eval` | `/teacher-eval/assets/js/main.js` | `/teacher-eval/admin/dashboard.php` |
| Custom port `localhost:3000/admin/` | '' | `/assets/js/main.js` | `/admin/dashboard.php` |

---

## 🎓 Best Practice

Always use the path helpers in your code:

```php
// ❌ Don't do this (hardcoded):
<a href="/admin/dashboard.php">Dashboard</a>
<link href="/assets/css/admin.css" rel="stylesheet">

// ✅ Do this (automatic):
<a href="<?php echo adminPath('dashboard.php'); ?>">Dashboard</a>
<link href="<?php echo assetPath('css/admin.css'); ?>" rel="stylesheet">
```

---

**Result:** Works correctly regardless of installation location! 🚀
