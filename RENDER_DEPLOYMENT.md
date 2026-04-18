# Deploy to Render.com - Step by Step

## 🎯 What We're Deploying

- **Backend**: teacher-eval (PHP + MongoDB Atlas)
- **Frontend**: flutter-app (PWA for students)

---

## 📋 Prerequisites

✅ GitHub account (you have this!)
✅ Code pushed to GitHub (DONE!)
✅ MongoDB Atlas connection string (you have this!)

---

## 🚀 STEP 1: Deploy Backend (teacher-eval)

### 1.1 Go to Render.com
- Visit: https://render.com
- Click **"Sign Up"** → Connect with **GitHub**
- Authorize Render to access your GitHub repos

### 1.2 Create New Web Service
1. After signing in, click **"New +"**
2. Select **"Web Service"**
3. Search for **"Caredsu/teacher-eval"** repo
4. Click **"Connect"**

### 1.3 Configure Deployment

Fill in these details:

| Field | Value |
|-------|-------|
| **Name** | teacher-eval-api |
| **Environment** | Docker |
| **Branch** | main |
| **Build Command** | `composer install` |
| **Start Command** | Leave blank (Render auto-detects for PHP) |

### 1.4 Add Environment Variables

Click **"Advanced"** and add these variables:

```
DB_HOST=mongodb+srv://Fullbright:fCKPOHka7QbGIq1X@cluster0.d5w3hon.mongodb.net/?retryWrites=true&w=majority
DB_NAME=teacher_eval
APP_ENV=production
APP_DEBUG=false
JWT_SECRET=teacher_eval_secret_key_2024_april_secure
```

### 1.5 Deploy!
- Click **"Create Web Service"**
- Wait for build (5-10 minutes)
- You'll get URL like: `https://teacher-eval-api.onrender.com`

---

## 🚀 STEP 2: Deploy Frontend (flutter-app)

### 2.1 Create Static Site
1. Click **"New +"** → **"Static Site"**
2. Search for **"Caredsu/teacher_eval_app"**
3. Click **"Connect"**

### 2.2 Configure
| Field | Value |
|-------|-------|
| **Name** | teacher-eval-student |
| **Branch** | main |
| **Build Command** | (leave blank - no build needed) |
| **Publish Directory** | . (root directory) |

### 2.3 Deploy!
- Click **"Create Static Site"**
- Wait for deployment (1-2 minutes)
- You'll get URL like: `https://teacher-eval-student.onrender.com`

---

## 🔧 Update Flutter App Configuration

After backend is deployed, update the API URL:

1. In your `eval-status-check.js`, change:
```javascript
// FROM:
const url = 'http://localhost/teacher-eval/...';

// TO:
const url = 'https://teacher-eval-api.onrender.com/...';
```

2. Commit and push to GitHub
3. Render automatically redeploys!

---

## ✅ Testing

Once deployed:

1. **Admin Panel**: `https://teacher-eval-api.onrender.com/admin`
   - Login with your admin credentials

2. **Student App**: `https://teacher-eval-student.onrender.com`
   - Open survey form
   - Test submission

3. **Check Submissions**: Go to admin → Results

---

## ⚠️ Render.com Free Tier

| Feature | Free | Paid |
|---------|------|------|
| **Deploy** | ✅ | ✅ |
| **Uptime** | Spins down after 15 min inactivity | Always on |
| **Storage** | 1 GB | More |
| **Price** | Free | $7/month+ |

**For testing**: Free tier is perfect!

---

## 🆘 Common Issues

**Q: Deployment failed?**
- Check logs in Render dashboard
- Usually missing environment variables

**Q: Flutter app not loading API?**
- Update the API URL in eval-status-check.js
- Make sure backend URL is correct

**Q: Can't login?**
- Check if admin user exists in MongoDB
- Run your admin creation script locally first

---

## 📞 Need Help?

After deployment, let me know if:
- ❌ Backend won't deploy
- ❌ Frontend won't load
- ❌ Can't connect to MongoDB
- ❌ Can't submit evaluations

I'll fix it! 🔧

---

## 🎯 Quick Checklist

- [ ] Code pushed to GitHub
- [ ] Sign up on Render.com
- [ ] Deploy teacher-eval backend
- [ ] Deploy flutter-app frontend  
- [ ] Update API URLs
- [ ] Test admin login
- [ ] Test student survey
- [ ] Check data in MongoDB

Ready? Go to render.com now! 🚀
