# STEP 1: GitHub Preparation Guide

This guide will help you prepare both projects for deployment to Render.com.

## 📋 What We've Done Already

✅ Created `.gitignore` files (prevents uploading sensitive files like `.env`)
✅ Initialized Git repositories in both projects
✅ Created README.md files for documentation

## 🎯 NEXT STEPS FOR YOU

### **For teacher-eval (Backend PHP)**

Your project already has Git connected to GitHub! Status:
- ✅ Already on GitHub (main branch)
- ✅ Has recent changes to commit

**Commit your latest changes:**
```bash
cd c:\xampp\htdocs\teacher-eval
git add .
git commit -m "Update: Fixed updated_by field in user management"
git push origin main
```

### **For flutter-app (Frontend PWA)**

Need to connect to GitHub:

#### **Option A: If you have an existing GitHub repo for flutter-app**

```bash
cd c:\xampp\htdocs\flutter-app
git config user.name "Your Name"
git config user.email "your-email@gmail.com"
git add .
git commit -m "Initial commit: Flutter PWA for student evaluations"
git remote add origin https://github.com/YOUR-USERNAME/flutter-app.git
git branch -M main
git push -u origin main
```

#### **Option B: If you DON'T have a GitHub repo yet**

1. Go to https://github.com/new
2. Create new repository named `flutter-app`
3. **Don't add README** (we already have one)
4. Copy the HTTPS URL
5. Run these commands:

```bash
cd c:\xampp\htdocs\flutter-app
git config user.name "Your Name"
git config user.email "your-email@gmail.com"
git add .
git commit -m "Initial commit: Flutter PWA for student evaluations"
git remote add origin https://github.com/YOUR-USERNAME/flutter-app.git
git branch -M main
git push -u origin main
```

---

## ⚠️ IMPORTANT: Environment Variables

**NEVER commit your `.env` file!** It contains:
- MongoDB credentials
- API keys
- Sensitive data

✅ Your `.gitignore` already protects this
✅ During Render deployment, you'll add `.env` variables manually

---

## 📝 Summary

You now have:

```
teacher-eval/               → Connected to GitHub
├── .gitignore             ✅ Protects .env
└── Latest code            ✅ Ready to push

flutter-app/              → Ready to connect to GitHub
├── .gitignore            ✅ Protects .env
└── Initial code          ✅ Ready to push
```

---

## 🚀 Next: Step 2 - Deploy to Render.com

Once you've pushed both projects to GitHub:
1. Go to render.com
2. Sign up with GitHub
3. Create Web Service from teacher-eval repo
4. Set environment variables
5. Deploy!

---

## ❓ Questions?

**Replace with YOUR GitHub username and email before running commands!**

Example:
```bash
git config user.name "John Doe"
git config user.email "john.doe@gmail.com"
```

Ready to push to GitHub? Let me know! 👉
