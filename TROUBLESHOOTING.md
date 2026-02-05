# 🔧 แก้ไขปัญหา: Frontend เชื่อมต่อ Backend ไม่ได้

## ปัญหา: "Failed to fetch" เมื่อ login

### ✅ Checklist แก้ไขปัญหา

#### 1. ตรวจสอบ Backend บน Railway

**1.1 ตรวจสอบว่า Backend รันอยู่:**
- ไปที่ Railway → service `Booking-Sci9-Computer...`
- ดูสถานะ: ต้องเป็น **Running** (สีเขียว)
- ถ้ายังเป็น **Failed** → ดู Logs และแก้ไข

**1.2 ตรวจสอบ Environment Variables บน Railway:**
- ไปที่ service → แท็บ **Variables**
- ต้องมีตัวแปรเหล่านี้:
  ```
  DB_CONNECTION=mysql
  DB_HOST=centerbeam.proxy.rlwy.net
  DB_PORT=18790
  DB_DATABASE=railway
  DB_USERNAME=root
  DB_PASSWORD=uNMpceEVTALERWRcffcovLrhSprcFJyd
  APP_ENV=production
  APP_DEBUG=false
  APP_KEY=<ค่าที่ได้จาก php artisan key:generate --show>
  ```

**1.3 ตรวจสอบ URL ของ Backend:**
- ไปที่ service → แท็บ **Settings** → **Domains**
- Copy URL (เช่น `https://xxx.up.railway.app`)
- ทดสอบเปิด URL ใน browser:
  - `https://xxx.up.railway.app/api/stats`
  - ถ้าได้ JSON → Backend ทำงาน
  - ถ้าไม่ได้ → Backend มีปัญหา

---

#### 2. ตั้งค่า Frontend บน Vercel

**2.1 ตั้งค่า Environment Variable:**
- ไปที่ Vercel → Project Settings → **Environment Variables**
- เพิ่มตัวแปร:
  - **Name**: `NEXT_PUBLIC_API_URL`
  - **Value**: URL จาก Railway (เช่น `https://xxx.up.railway.app`)
  - **Environment**: Production, Preview, Development (เลือกทั้งหมด)
- กด **Save**

**2.2 Redeploy Frontend:**
- ไปที่ Vercel → Project → **Deployments**
- คลิก **...** (สามจุด) → **Redeploy**
- หรือ push code ใหม่ไป GitHub (Vercel จะ auto-deploy)

---

#### 3. ตรวจสอบ CORS บน Backend

**3.1 ตรวจสอบ `backend/config/cors.php`:**
- ต้องมี pattern รองรับ Vercel:
  ```php
  'allowed_origins_patterns' => [
      '/^https:\/\/.*\.vercel\.app$/',
  ],
  ```

**3.2 ถ้ายังไม่มี → Commit และ Push:**
- ไฟล์ `cors.php` ควรจะมี pattern นี้อยู่แล้ว
- ถ้ายังไม่มี → Push code ใหม่ไป GitHub

---

#### 4. ทดสอบการเชื่อมต่อ

**4.1 ทดสอบ Backend โดยตรง:**
- เปิด browser → ไปที่ `https://xxx.up.railway.app/api/stats`
- ถ้าได้ JSON → Backend ทำงาน
- ถ้าไม่ได้ → Backend มีปัญหา

**4.2 ทดสอบ Frontend:**
- เปิด browser → ไปที่ `https://your-app.vercel.app`
- เปิด Developer Tools (F12) → แท็บ **Console**
- ดู error messages
- ไปที่แท็บ **Network** → ดู requests ไป backend
  - ถ้าเป็นสีแดง → CORS หรือ connection error
  - ถ้าเป็นสีเขียว → เชื่อมต่อได้แล้ว

---

## 🔍 Debugging Tips

### ตรวจสอบใน Browser Console:

1. **เปิด Developer Tools (F12)**
2. **ไปที่แท็บ Console**
3. **ดู error messages:**
   - `Failed to fetch` → Backend ไม่สามารถเข้าถึงได้ หรือ CORS error
   - `CORS policy` → CORS ไม่ได้ตั้งค่า
   - `404 Not Found` → URL ไม่ถูกต้อง
   - `500 Internal Server Error` → Backend มีปัญหา

### ตรวจสอบใน Network Tab:

1. **เปิด Developer Tools (F12)**
2. **ไปที่แท็บ Network**
3. **ลอง login หรือ refresh หน้า**
4. **ดู requests ไป `/api/login` หรือ `/api/stats`:**
   - **Status**: ต้องเป็น 200 (OK)
   - **Headers**: ดู `Access-Control-Allow-Origin`
   - **Response**: ดู response data

---

## 📋 สรุปขั้นตอนแก้ไข

1. ✅ **Backend บน Railway รันได้** (สถานะ Running)
2. ✅ **Environment Variables บน Railway ตั้งค่าแล้ว**
3. ✅ **Backend URL ทดสอบได้** (เปิด `/api/stats` ได้ JSON)
4. ✅ **Environment Variable บน Vercel ตั้งค่าแล้ว** (`NEXT_PUBLIC_API_URL`)
5. ✅ **Frontend Redeploy แล้ว**
6. ✅ **CORS ตั้งค่าแล้ว** (รองรับ `*.vercel.app`)

---

## 🆘 ถ้ายังไม่ได้

1. **ตรวจสอบ Logs บน Railway:**
   - ไปที่ service → แท็บ **Logs**
   - ดู error messages

2. **ตรวจสอบ Logs บน Vercel:**
   - ไปที่ Project → **Deployments** → เลือก deployment ล่าสุด
   - ดู **Build Logs** และ **Runtime Logs**

3. **ทดสอบ Backend API โดยตรง:**
   - ใช้ Postman หรือ curl:
     ```bash
     curl https://xxx.up.railway.app/api/stats
     ```

4. **ตรวจสอบ Browser Console:**
   - ดู error messages ที่ชัดเจน

---

## 📞 ต้องการความช่วยเหลือ?

บอกผมว่า:
1. Backend URL บน Railway คืออะไร?
2. Frontend URL บน Vercel คืออะไร?
3. Error message ใน Browser Console คืออะไร?
4. Backend บน Railway รันได้หรือยัง? (สถานะ Running?)

ผมจะช่วยแก้ไขให้ตรงจุด!

