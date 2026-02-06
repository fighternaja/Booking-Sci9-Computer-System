# 🚀 Deploy Backend บน Render (Step-by-Step)

## ขั้นตอนที่ 1: Push โค้ดไป GitHub

```bash
cd D:\booking
git add .
git commit -m "Prepare for Render deployment"
git push origin main
```

---

## ขั้นตอนที่ 2: สร้างบัญชี Render

1. ไปที่ [render.com](https://render.com)
2. คลิก **Get Started for Free**
3. Sign up ด้วย **GitHub** (แนะนำ)

---

## ขั้นตอนที่ 3: สร้าง PostgreSQL Database (ฟรี)

1. คลิก **New** → **PostgreSQL**
2. ตั้งค่า:
   - **Name**: `booking-db`
   - **Database**: `booking`
   - **User**: `booking_user`
   - **Region**: Singapore (ใกล้ไทย)
   - **Plan**: Free
3. คลิก **Create Database**
4. รอสักครู่ให้สร้างเสร็จ
5. **Copy ค่าเหล่านี้** (จะใช้ในขั้นตอนถัดไป):
   - `Internal Database URL` หรือ `External Database URL`
   - `Hostname`, `Port`, `Database`, `Username`, `Password`

---

## ขั้นตอนที่ 4: สร้าง Web Service

1. คลิก **New** → **Web Service**
2. เลือก **Build and deploy from a Git repository**
3. เชื่อมต่อ GitHub และเลือก repository `booking`
4. ตั้งค่า:
   - **Name**: `booking-backend`
   - **Region**: Singapore
   - **Branch**: `main`
   - **Root Directory**: `backend`
   - **Runtime**: `PHP`
   - **Build Command**:
     ```
     composer install --no-dev --optimize-autoloader
     ```
   - **Start Command**:
     ```
     php artisan serve --host=0.0.0.0 --port=$PORT
     ```
   - **Plan**: Free

---

## ขั้นตอนที่ 5: ตั้งค่า Environment Variables

ใน Web Service Settings → Environment → Add Environment Variable:

| Key | Value |
|-----|-------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:xxxxxxxx` (ดูขั้นตอนที่ 6) |
| `APP_URL` | `https://booking-backend.onrender.com` (URL ของ Render) |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | (จาก PostgreSQL ที่สร้าง) |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `booking` |
| `DB_USERNAME` | `booking_user` |
| `DB_PASSWORD` | (จาก PostgreSQL ที่สร้าง) |
| `CORS_ALLOWED_ORIGINS` | `https://booking-mpim3w2n4-fightereieis-projects.vercel.app,https://booking-ten-rosy.vercel.app` |

---

## ขั้นตอนที่ 6: Generate APP_KEY

รันคำสั่งนี้ใน local เพื่อสร้าง APP_KEY:

```bash
cd D:\booking\backend
php artisan key:generate --show
```

จะได้ค่าประมาณ:
```
base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
```

Copy ค่านี้ไปใส่ใน `APP_KEY` บน Render

---

## ขั้นตอนที่ 7: Deploy

1. คลิก **Create Web Service**
2. รอให้ build และ deploy เสร็จ (ประมาณ 5-10 นาที)
3. จะได้ URL เช่น `https://booking-backend.onrender.com`

---

## ขั้นตอนที่ 8: รัน Migrations

หลัง deploy เสร็จ:

1. ไปที่ Web Service → **Shell** tab
2. รันคำสั่ง:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

หรือเพิ่มใน Build Command:
```
composer install --no-dev --optimize-autoloader && php artisan migrate --force && php artisan db:seed --force
```

---

## ขั้นตอนที่ 9: อัปเดต Vercel Environment Variable

1. ไปที่ [Vercel Dashboard](https://vercel.com/dashboard)
2. เลือก project `booking`
3. ไปที่ **Settings** → **Environment Variables**
4. แก้ไข `NEXT_PUBLIC_API_URL`:
   ```
   https://booking-backend.onrender.com
   ```
5. คลิก **Save**
6. ไปที่ **Deployments** → คลิก **Redeploy** บน deployment ล่าสุด

---

## ขั้นตอนที่ 10: ทดสอบ

1. เปิด `https://booking-backend.onrender.com/api/stats`
   - ควรเห็น JSON response
2. เปิด `https://booking-ten-rosy.vercel.app`
   - ควรโหลดข้อมูลได้ปกติ

---

## 🔧 Troubleshooting

### Build Failed
- ตรวจสอบ `composer.json` และ PHP version
- ดู logs ใน Render Dashboard

### Database Connection Error
- ตรวจสอบ `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- ใช้ `Internal Database URL` ถ้าอยู่ใน Render

### CORS Error
- ตรวจสอบ `CORS_ALLOWED_ORIGINS` ใน Environment Variables
- ต้องใส่ URL ของ Vercel ให้ถูกต้อง (ไม่มี `/` ต่อท้าย)

### 502 Bad Gateway
- รอ Render spin up (Free tier จะ sleep หลัง 15 นาที)
- ตรวจสอบ logs

---

## 📋 สรุป URLs

| Service | URL |
|---------|-----|
| Frontend (Vercel) | `https://booking-ten-rosy.vercel.app` |
| Backend (Render) | `https://booking-backend.onrender.com` |
| API Stats | `https://booking-backend.onrender.com/api/stats` |

---

## ⚠️ หมายเหตุ Free Tier

- Render Free Tier: Service จะ sleep หลัง 15 นาทีไม่มี request
- Cold start: ประมาณ 30 วินาที - 1 นาที
- ถ้าต้องการ always-on: ใช้ paid plan ($7/เดือน)

