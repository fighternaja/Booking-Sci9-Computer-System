# ⚡ Quick Start: Deploy บน Vercel

## 🎯 ขั้นตอนแบบย่อ (5 นาที)

### 1️⃣ Deploy Frontend บน Vercel

```bash
# เข้าไปที่โฟลเดอร์ frontend
cd frontend

# ติดตั้ง Vercel CLI (ครั้งแรกเท่านั้น)
npm i -g vercel

# Login และ Deploy
vercel login
vercel --prod
```

**หรือใช้ GitHub:**
1. Push โค้ดไป GitHub
2. ไปที่ [vercel.com](https://vercel.com)
3. Import Project → เลือก repo → Root Directory: `frontend`
4. เพิ่ม Environment Variable: `NEXT_PUBLIC_API_URL` = URL ของ backend
5. Deploy!

### 2️⃣ Deploy Backend บน Railway

1. ไปที่ [railway.app](https://railway.app)
2. New Project → Deploy from GitHub
3. เลือก repo → Root Directory: `backend`
4. เพิ่ม Environment Variables (ดูใน `DEPLOYMENT_GUIDE.md`)
5. รอ deploy เสร็จ → Copy URL

### 3️⃣ อัปเดต Frontend

1. ไปที่ Vercel Dashboard → Project Settings → Environment Variables
2. แก้ไข `NEXT_PUBLIC_API_URL` = URL จาก Railway
3. Redeploy

### 4️⃣ ตั้งค่า CORS

แก้ไข `backend/config/cors.php`:
- เพิ่ม Vercel URL ใน `allowed_origins`
- หรือใช้ environment variable `CORS_ALLOWED_ORIGINS`

---

## 📝 สิ่งที่ต้องทำ

- ✅ สร้างไฟล์ `vercel.json` แล้ว
- ✅ สร้างไฟล์ `Procfile` สำหรับ Railway แล้ว
- ✅ สร้างไฟล์ `railway.json` แล้ว
- ⚠️ ต้องตั้งค่า Environment Variables
- ⚠️ ต้องตั้งค่า CORS
- ⚠️ ต้อง deploy backend ก่อน frontend

---

## 🔗 ลิงก์สำคัญ

- [Vercel Dashboard](https://vercel.com/dashboard)
- [Railway Dashboard](https://railway.app/dashboard)
- [คู่มือฉบับเต็ม](./DEPLOYMENT_GUIDE.md)

