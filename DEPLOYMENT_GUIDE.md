# Teacher Eval - Complete Deployment Guide (Free)

**Target**: Deploy Flutter Mobile/Web + PHP Backend + MongoDB to Philippines

---

## **PHASE 1: MongoDB Atlas Setup (5 minutes)**

### Step 1: Create MongoDB Atlas Account
1. Go to https://www.mongodb.com/cloud/atlas
2. Click **"Sign Up for Free"**
3. Create account (email/password)
4. Verify your email

### Step 2: Create a Cluster
1. Click **"Create" → "Build a Cluster"**
2. Select **"Free Tier"** (0.5 GB storage)
3. **Cloud Provider**: AWS
4. **Region**: Choose **Singapore (ap-southeast-1)** ← closest to PH
5. Click **"Create Cluster"** (takes ~3-5 minutes)

### Step 3: Setup Database Access
1. In Atlas dashboard, click **"Security" → "Database Access"**
2. Click **"Add New Database User"**
3. Enter:
   - **Username**: `teacher_eval_user`
   - **Password**: Generate strong password (save this!)
   - **Role**: `readWriteAnyDatabase`
4. Click **"Add User"**

### Step 4: Setup Network Access
1. Click **"Security" → "Network Access"**
2. Click **"Add IP Address"**
3. Select **"Allow access from anywhere"** (0.0.0.0/0)
4. Click **"Confirm"**

### Step 5: Get Connection String
1. Click **"Database"** (left menu)
2. Click your cluster **"Connect"** button
3. Choose **"Drivers"**
4. Select **"PHP"** and **"Latest"**
5. Copy the connection string, looks like:
   ```
   mongodb+srv://teacher_eval_user:PASSWORD@cluster0.xxxxx.mongodb.net/teacher_eval?retryWrites=true&w=majority
   ```
   ⚠️ **Replace PASSWORD with your actual password!**

---

## **PHASE 2: Prepare Your PHP Code (15 minutes)**

### Step 1: Create `.env` File
In your project root (`c:\xampp\htdocs\teacher-eval\`), create **`.env`** file:

```ini
# Database
DB_HOST=mongodb+srv://teacher_eval_user:YOUR_PASSWORD@cluster0.xxxxx.mongodb.net/teacher_eval?retryWrites=true&w=majority
DB_NAME=teacher_eval

# App
APP_ENV=production
APP_URL=https://your-app-name.onrender.com
APP_DEBUG=false

# JWT (generate a random secret key)
JWT_SECRET=your_random_secret_key_here_min_32_chars

# CORS
ALLOWED_ORIGINS=https://your-flutter-web-domain.com,https://localhost:3000
```

### Step 2: Update Database Config
Edit `config/database.php`:

```php
<?php
return [
    'mongodb' => [
        'uri' => $_ENV['DB_HOST'] ?? 'mongodb://localhost:27017',
        'database' => $_ENV['DB_NAME'] ?? 'teacher_eval',
    ],
    'app_url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'app_debug' => $_ENV['APP_DEBUG'] ?? false,
];
?>
```

### Step 3: Verify Composer Files
Ensure `composer.json` includes:
```json
{
    "require": {
        "php": ">=7.4",
        "mongodb/mongodb": "^1.14",
        "vlucas/phpdotenv": "^5.5"
    }
}
```

Run in terminal:
```bash
composer install
```

---

## **PHASE 3: Deploy to Render (20 minutes)**

### Step 1: Prepare GitHub Repository
1. Install **Git** (if not already)
2. In your project folder:
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   ```
3. Create **GitHub account** (https://github.com/signup)
4. Create new repository (e.g., `teacher-eval`)
5. Push your code:
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/teacher-eval.git
   git branch -M main
   git push -u origin main
   ```

### Step 2: Create Render Deploy
1. Go to https://render.com
2. Sign up (choose GitHub auth)
3. Click **"New +"** → **"Web Service"**
4. Select your `teacher-eval` GitHub repo
5. Fill in:
   - **Name**: `teacher-eval-api`
   - **Region**: Singapore (ap-southeast-1)
   - **Branch**: `main`
   - **Runtime**: PHP
   - **Build Command**: 
     ```
     composer install
     ```
   - **Start Command**:
     ```
     php -S 0.0.0.0:8080
     ```

6. **Environment Variables** (click "Add"):
   ```
   DB_HOST=mongodb+srv://teacher_eval_user:YOUR_PASSWORD@cluster0.xxxxx.mongodb.net/teacher_eval?retryWrites=true&w=majority
   DB_NAME=teacher_eval
   APP_ENV=production
   APP_DEBUG=false
   JWT_SECRET=your_random_key
   ```

7. Click **"Create Web Service"** (deploys in ~5 minutes)

### Step 3: Get Your Live API URL
After deployment completes:
- Your API will be at: `https://teacher-eval-api.onrender.com`
- Save this URL!

⚠️ **Note**: Free Render tier spins down after 15 minutes of inactivity. Upgrade to paid ($7/month) to keep it always running.

---

## **PHASE 4: Deploy Flutter Web (10 minutes)**

### Step 1: Build Flutter Web
```bash
# In C:\flutter_projects\teacher_eval_app
flutter pub get
flutter build web --release --web-renderer html
```

### Step 2: Deploy to Render (or Vercel)
**Option A: Render**
1. Create `render.yaml` in project root:
   ```yaml
   services:
   - type: web
     name: teacher-eval-web
     plan: free
     env: static
     staticPublishPath: build/web
   ```
2. Push to GitHub
3. Connect to Render (same as PHP backend)

**Option B: Vercel (Easier for web)**
1. Go to https://vercel.com
2. Sign in with GitHub
3. Click "Add New..." → "Project"
4. Select your Flutter project
5. Click "Deploy"
6. URL: `https://teacher-eval-web.vercel.app`

---

## **PHASE 5: Flutter Mobile Deployment**

### Step 1: Update API Endpoint
In your Flutter code, update API base URL:

```dart
// In your API service file
final String apiBaseUrl = 'https://teacher-eval-api.onrender.com';
```

### Step 2: Build APK (Android)
```bash
flutter build apk --release
```
File will be at: `build/app/outputs/flutter-app-release.apk`

### Step 3: Testing
- Share APK for testing via **Firebase App Distribution** (free):
  1. Go to https://console.firebase.google.com
  2. Create project
  3. Add Firebase to Flutter app
  4. Upload APK for testers

### Step 4: Release (When Ready)
- **Google Play Store**:
  - Create developer account ($25 one-time)
  - Upload signed APK
  - More info: https://flutter.dev/deployment/android

---

## **PHASE 6: Test Everything**

### Connection Tests
1. **Test MongoDB connection**:
   - Go to `https://teacher-eval-api.onrender.com/health` or `/api/login`
   - Should return JSON (not HTML error)

2. **Test Flutter Web**:
   - Open `https://teacher-eval-web.vercel.app`
   - Try login with test teacher credentials

3. **Test Flutter Mobile**:
   - Install APK on Android phone
   - Try login, create/view evaluations

---

## **SUMMARY: Your Live URLs**

After deployment:
- 🎯 **PHP Backend API**: https://teacher-eval-api.onrender.com
- 🌐 **Flutter Web**: https://teacher-eval-web.vercel.app
- 📱 **Flutter Mobile**: Install APK from app store or test link
- 💾 **MongoDB**: Hosted on Atlas Singapore

---

## **COSTS BREAKDOWN (Monthly)**

| Service | Free Tier | Paid |
|---------|-----------|------|
| MongoDB Atlas | ✅ 512MB | $57/month (10GB) |
| Render PHP | ✅ (sleeps after 15min) | $7/month (always on) |
| Vercel Web | ✅ | $20/month (custom domain) |
| **Total** | **FREE** | **~$84/month** (if all paid) |

For starting: **Keep everything FREE!** Upgrade when you have users.

---

## **Troubleshooting**

### "Cannot connect to MongoDB"
- Check connection string has correct password
- Verify IP is whitelisted in Atlas Network Access
- Check DATABASE NAME matches

### "Render deployment fails"
- Check `composer.json` exists
- Verify PHP code has no syntax errors
- Check environment variables are set

### "Flutter can't reach API"
- Check API URL is correct in Flutter code
- Test API URL directly in browser: `https://teacher-eval-api.onrender.com/health`
- Check CORS headers in PHP backend

---

**Ready to start? Pick which step to begin with!**
