# 🚂 Deploy Backend บน Railway - คู่มือแบบละเอียด

## ขั้นตอนที่ 1: สร้างบัญชี Railway

1. ไปที่ [railway.app](https://railway.app)
2. คลิก **Login** → เลือก **Login with GitHub**
3. อนุญาตให้ Railway เข้าถึง GitHub

---

## ขั้นตอนที่ 2: สร้าง Project ใหม่

1. คลิก **New Project**
2. เลือก **Deploy from GitHub repo**
3. เลือก repository ของคุณ (เช่น `fightereieis-projects/booking`)
4. **สำคัญ**: ตั้งค่า Root Directory เป็น `backend`

---

## ขั้นตอนที่ 3: สร้าง Database

### วิธี A: ใช้ MySQL บน Railway
1. ในหน้า Project คลิก **New** → **Database** → **MySQL**
2. รอให้ MySQL สร้างเสร็จ
3. คลิกที่ MySQL service → **Variables**
4. Copy ค่าต่อไปนี้:
   - `MYSQL_HOST`
   - `MYSQL_PORT`
   - `MYSQL_DATABASE`
   - `MYSQL_USER`
   - `MYSQL_PASSWORD`

### วิธี B: ใช้ PostgreSQL บน Railway (แนะนำ - ฟรี)
1. ในหน้า Project คลิก **New** → **Database** → **PostgreSQL**
2. รอให้ PostgreSQL สร้างเสร็จ
3. คลิกที่ PostgreSQL service → **Variables**
4. Copy ค่าต่อไปนี้:
   - `PGHOST`
   - `PGPORT`
   - `PGDATABASE`
   - `PGUSER`
   - `PGPASSWORD`

---

## ขั้นตอนที่ 4: ตั้งค่า Environment Variables

1. คลิกที่ **Web Service** (backend)
2. ไปที่ **Variables**
3. เพิ่มตัวแปรต่อไปนี้:

### ตัวแปรหลัก (จำเป็น)

```
APP_NAME=BookingSystem
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR_RAILWAY_URL.railway.app

# Generate ด้วย: php artisan key:generate --show
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

### Database (MySQL)
```
DB_CONNECTION=mysql
DB_HOST=${MYSQL_HOST}
DB_PORT=${MYSQL_PORT}
DB_DATABASE=${MYSQL_DATABASE}
DB_USERNAME=${MYSQL_USER}
DB_PASSWORD=${MYSQL_PASSWORD}
```

### Database (PostgreSQL)
```
DB_CONNECTION=pgsql
DB_HOST=${PGHOST}
DB_PORT=${PGPORT}
DB_DATABASE=${PGDATABASE}
DB_USERNAME=${PGUSER}
DB_PASSWORD=${PGPASSWORD}
```

### CORS (สำหรับ Frontend)
```
CORS_ALLOWED_ORIGINS=https://booking-mpim3w2n4-fightereieis-projects.vercel.app,https://booking-ten-rosy.vercel.app
```

### อื่นๆ
```
LOG_CHANNEL=stack
LOG_LEVEL=error
SANCTUM_STATEFUL_DOMAINS=booking-ten-rosy.vercel.app,booking-mpim3w2n4-fightereieis-projects.vercel.app
SESSION_DRIVER=cookie
```

---

## ขั้นตอนที่ 5: Generate APP_KEY

รันคำสั่งนี้ใน terminal (local):

```bash
cd backend
php artisan key:generate --show
```

Copy ค่าที่ได้ (เช่น `base64:abc123...`) ไปใส่ใน `APP_KEY` บน Railway

---

## ขั้นตอนที่ 6: Deploy

1. Railway จะ auto-deploy เมื่อตั้งค่าเสร็จ
2. รอให้ build เสร็จ (ประมาณ 3-5 นาที)
3. ดู logs ใน **Deployments** tab
4. เมื่อ deploy สำเร็จ จะได้ URL เช่น `https://booking-backend-production.up.railway.app`

---

## ขั้นตอนที่ 7: รัน Migrations

1. ไปที่ **Settings** → **Deploy**
2. เปิด **Railway Shell** หรือใช้ Railway CLI:
   ```bash
   railway run php artisan migrate --force
   ```

หรือ migrations จะรันอัตโนมัติจาก `nixpacks.toml` ที่เราตั้งไว้

---

## ขั้นตอนที่ 8: อัปเดต Frontend (Vercel)

1. ไปที่ **Vercel Dashboard** → Project → **Settings** → **Environment Variables**
2. แก้ไข `NEXT_PUBLIC_API_URL`:
   ```
   https://YOUR_RAILWAY_URL.railway.app
   ```
3. คลิก **Redeploy** หรือ push code ใหม่

---

## ขั้นตอนที่ 9: ทดสอบ

1. เปิด `https://YOUR_RAILWAY_URL.railway.app/api/stats`
   - ถ้าได้ JSON = Backend ทำงาน
2. เปิด Frontend `https://booking-ten-rosy.vercel.app`
   - ถ้าโหลดข้อมูลได้ = เชื่อมต่อสำเร็จ

---

## 🔧 Troubleshooting

### Build Failed
- ตรวจสอบ `composer.json` และ `composer.lock`
- ดู logs ใน Deployments tab

### Database Connection Error
- ตรวจสอบ DB_* variables
- ใช้ `${VARIABLE_NAME}` syntax สำหรับ reference ค่าจาก database service

### CORS Error
- ตรวจสอบ `CORS_ALLOWED_ORIGINS`
- เพิ่ม Vercel URLs ให้ครบ

### 500 Error
- ตั้ง `APP_DEBUG=true` ชั่วคราวเพื่อดู error
- ดู logs ใน Railway

---

## 📋 Checklist

- [ ] สร้าง Project บน Railway
- [ ] เลือก Root Directory: `backend`
- [ ] สร้าง Database (MySQL หรือ PostgreSQL)
- [ ] ตั้งค่า APP_KEY
- [ ] ตั้งค่า Database variables
- [ ] ตั้งค่า CORS_ALLOWED_ORIGINS
- [ ] รอ build เสร็จ
- [ ] ทดสอบ API endpoint
- [ ] อัปเดต NEXT_PUBLIC_API_URL บน Vercel
- [ ] ทดสอบ Frontend เชื่อมต่อ Backend

---

## 💰 Railway Pricing

- **Free Tier**: $5 credit/เดือน (พอใช้สำหรับ small projects)
- **Hobby**: $5/เดือน + usage
- **Pro**: $20/เดือน + usage

---

## 📚 Links

- [Railway Dashboard](https://railway.app/dashboard)
- [Railway Documentation](https://docs.railway.app)
- [Laravel on Railway](https://docs.railway.app/guides/laravel)

