# 🚀 คู่มือการ Deploy ระบบจองห้อง Sci 9

## 📋 สารบัญ
1. [Deploy Frontend บน Vercel](#deploy-frontend-บน-vercel)
2. [Deploy Backend บน Railway/Render](#deploy-backend-บน-railwayrender)
3. [ตั้งค่า CORS](#ตั้งค่า-cors)
4. [ตั้งค่า Environment Variables](#ตั้งค่า-environment-variables)
5. [Troubleshooting](#troubleshooting)

---

## 🎨 Deploy Frontend บน Vercel

### ขั้นตอนที่ 1: เตรียมโปรเจกต์

```bash
# เข้าไปที่โฟลเดอร์ frontend
cd frontend

# ทดสอบ build ก่อน
npm run build
```

### ขั้นตอนที่ 2: Deploy ด้วย Vercel CLI

```bash
# ติดตั้ง Vercel CLI (ถ้ายังไม่มี)
npm i -g vercel

# Login เข้า Vercel
vercel login

# Deploy (ครั้งแรก - จะถามคำถาม)
vercel

# Deploy production
vercel --prod
```

### ขั้นตอนที่ 3: Deploy ด้วย GitHub Integration (แนะนำ)

1. **Push โค้ดไป GitHub**
   ```bash
   git add .
   git commit -m "Prepare for Vercel deployment"
   git push origin main
   ```

2. **เชื่อมต่อกับ Vercel**
   - ไปที่ [vercel.com](https://vercel.com)
   - คลิก "Add New Project"
   - เลือก GitHub repository ของคุณ
   - ตั้งค่า:
     - **Framework Preset**: Next.js
     - **Root Directory**: `frontend` (ถ้า repo อยู่ที่ root)
     - **Build Command**: `npm run build` (default)
     - **Output Directory**: `.next` (default)

3. **ตั้งค่า Environment Variables**
   - ไปที่ Project Settings → Environment Variables
   - เพิ่ม:
     ```
     Name: NEXT_PUBLIC_API_URL
     Value: https://your-backend-url.com (ใส่ URL ของ backend ที่ deploy แล้ว)
     Environment: Production, Preview, Development
     ```

4. **Deploy**
   - คลิก "Deploy"
   - รอให้ build เสร็จ (ประมาณ 2-3 นาที)

---

## 🔧 Deploy Backend บน Railway (แนะนำ)

### ขั้นตอนที่ 1: เตรียมโปรเจกต์

1. **สร้างไฟล์ `Procfile`** ในโฟลเดอร์ `backend/`:
   ```
   web: php artisan serve --host=0.0.0.0 --port=$PORT
   ```

2. **สร้างไฟล์ `railway.json`** (optional):
   ```json
   {
     "build": {
       "builder": "NIXPACKS"
     },
     "deploy": {
       "startCommand": "php artisan serve --host=0.0.0.0 --port=$PORT",
       "restartPolicyType": "ON_FAILURE",
       "restartPolicyMaxRetries": 10
     }
   }
   ```

### ขั้นตอนที่ 2: Deploy บน Railway

1. **ไปที่ [railway.app](https://railway.app)**
2. **Login** ด้วย GitHub
3. **New Project** → **Deploy from GitHub repo**
4. **เลือก repository** และ **โฟลเดอร์ `backend`**
5. **ตั้งค่า Environment Variables**:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:... (generate ด้วย: php artisan key:generate --show)
   APP_URL=https://your-backend-url.railway.app
   
   DB_CONNECTION=mysql (หรือ pgsql)
   DB_HOST=...
   DB_PORT=...
   DB_DATABASE=...
   DB_USERNAME=...
   DB_PASSWORD=...
   
   CORS_ALLOWED_ORIGINS=https://your-vercel-app.vercel.app
   ```

6. **รัน migrations**:
   - ไปที่ Deployments → View Logs
   - หรือใช้ Railway CLI:
     ```bash
     railway run php artisan migrate --force
     ```

### ตัวเลือกอื่น: Render

1. **ไปที่ [render.com](https://render.com)**
2. **New** → **Web Service**
3. **Connect GitHub** และเลือก repository
4. **ตั้งค่า**:
   - **Name**: booking-backend
   - **Environment**: PHP
   - **Build Command**: `composer install --no-dev --optimize-autoloader`
   - **Start Command**: `php artisan serve --host=0.0.0.0 --port=$PORT`
   - **Root Directory**: `backend`

---

## 🌐 ตั้งค่า CORS

### ใน Backend (`backend/config/cors.php`)

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'https://your-vercel-app.vercel.app',
        'https://your-custom-domain.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
```

### หรือใช้ Environment Variable

ใน `.env`:
```
CORS_ALLOWED_ORIGINS=https://your-vercel-app.vercel.app,https://your-domain.com
```

---

## 🔐 ตั้งค่า Environment Variables

### Frontend (Vercel)

| Variable | Value | ตัวอย่าง |
|----------|-------|----------|
| `NEXT_PUBLIC_API_URL` | URL ของ backend | `https://your-backend.railway.app` |

### Backend (Railway/Render)

| Variable | Value | ตัวอย่าง |
|----------|-------|----------|
| `APP_ENV` | `production` | `production` |
| `APP_DEBUG` | `false` | `false` |
| `APP_KEY` | Laravel app key | `base64:...` |
| `APP_URL` | URL ของ backend | `https://your-backend.railway.app` |
| `DB_CONNECTION` | Database type | `mysql` หรือ `pgsql` |
| `DB_HOST` | Database host | จาก Railway/Render |
| `DB_PORT` | Database port | `3306` (MySQL) |
| `DB_DATABASE` | Database name | จาก Railway/Render |
| `DB_USERNAME` | Database user | จาก Railway/Render |
| `DB_PASSWORD` | Database password | จาก Railway/Render |
| `CORS_ALLOWED_ORIGINS` | Frontend URLs | `https://your-app.vercel.app` |

---

## ✅ Checklist ก่อน Deploy

### Frontend
- [ ] ทดสอบ build: `npm run build`
- [ ] ตรวจสอบว่าไม่มี error
- [ ] ตั้งค่า `NEXT_PUBLIC_API_URL` บน Vercel
- [ ] Deploy และทดสอบ

### Backend
- [ ] สร้าง `Procfile` หรือ `railway.json`
- [ ] Generate `APP_KEY`: `php artisan key:generate --show`
- [ ] ตั้งค่า CORS ให้รองรับ frontend URL
- [ ] ตั้งค่า Database (MySQL/PostgreSQL)
- [ ] รัน migrations: `php artisan migrate --force`
- [ ] ตั้งค่า storage link: `php artisan storage:link`
- [ ] ทดสอบ API endpoints

---

## 🔧 Troubleshooting

### Frontend Build Error

```bash
# ลบ node_modules และ build cache
rm -rf node_modules .next
npm install
npm run build
```

### Backend Connection Error

1. **ตรวจสอบ CORS settings**
2. **ตรวจสอบ `NEXT_PUBLIC_API_URL`** ถูกต้อง
3. **ตรวจสอบ backend logs** บน Railway/Render
4. **ทดสอบ API** ด้วย Postman/curl

### Database Connection Error

1. **ตรวจสอบ Database credentials**
2. **ตรวจสอบว่า Database service ทำงานอยู่**
3. **ตรวจสอบ network settings** (ถ้าใช้ external database)

### Image Loading Error

1. **ตั้งค่า storage link**: `php artisan storage:link`
2. **ตรวจสอบ file permissions**
3. **ใช้ Cloud Storage** (S3, Cloudinary) แทน local storage

---

## 📚 เอกสารเพิ่มเติม

- [Vercel Documentation](https://vercel.com/docs)
- [Railway Documentation](https://docs.railway.app)
- [Render Documentation](https://render.com/docs)
- [Laravel Deployment](https://laravel.com/docs/deployment)

---

## 🆘 ต้องการความช่วยเหลือ?

ถ้ามีปัญหาหรือคำถาม:
1. ตรวจสอบ logs บน Vercel/Railway
2. ตรวจสอบ browser console
3. ตรวจสอบ network tab ใน DevTools
4. อ่าน error messages อย่างละเอียด

