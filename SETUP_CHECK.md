# ✅ Checklist การตั้งค่า Google Login

## ก่อนเริ่มใช้งาน ให้ตรวจสอบ:

### Backend
- [ ] Migration `add_google_id_to_users_table` รันแล้ว
- [ ] ไฟล์ `backend/.env` มี `GOOGLE_CLIENT_ID`
- [ ] Laravel server ทำงาน (`php artisan serve`)
- [ ] Route `/api/login/google` พร้อมใช้งาน

### Frontend
- [ ] ไฟล์ `frontend/.env.local` มี `NEXT_PUBLIC_GOOGLE_CLIENT_ID`
- [ ] Next.js server ทำงาน (`npm run dev`)
- [ ] ปุ่ม "เข้าสู่ระบบด้วย Google" แสดงในหน้า login

### Google Cloud Console
- [ ] สร้าง OAuth 2.0 Client ID แล้ว
- [ ] ตั้งค่า Authorized JavaScript origins:
  - `http://localhost:3000`
  - `http://127.0.0.1:3000`
- [ ] Client ID และ Client Secret ถูกต้อง

---

## 🔍 ตรวจสอบว่าทุกอย่างพร้อม:

### 1. ตรวจสอบ Migration
```bash
cd backend
php artisan migrate:status
```
ต้องเห็น: `2025_11_02_132038_add_google_id_to_users_table ... [X] Ran`

### 2. ตรวจสอบ Route
```bash
cd backend
php artisan route:list | findstr google
```
ต้องเห็น: `POST api/login/google ... Api\AuthController@googleLogin`

### 3. ตรวจสอบ Environment Variables
```bash
# Backend
cd backend
# ตรวจสอบว่า .env มี GOOGLE_CLIENT_ID

# Frontend
cd frontend
# ตรวจสอบว่า .env.local มี NEXT_PUBLIC_GOOGLE_CLIENT_ID
```

### 4. ทดสอบ Google Login
1. เปิด: `http://localhost:3000/login`
2. ดูว่ามีปุ่ม "เข้าสู่ระบบด้วย Google" หรือไม่
3. คลิกปุ่มและทดสอบ login

---

## ⚡ ถ้าทุกอย่างพร้อม:

✅ **พร้อมใช้งานแล้ว!** ดู `QUICK_START.md` สำหรับคำแนะนำการใช้งาน

