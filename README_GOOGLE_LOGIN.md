# 🔐 Google Login System

ระบบเข้าสู่ระบบด้วย Google OAuth 2.0 สำหรับ Booking System

## 📁 ไฟล์ที่เกี่ยวข้อง

### Backend
- `app/Http/Controllers/Api/AuthController.php` - Google login endpoint
- `app/Models/User.php` - เพิ่ม `google_id` field
- `database/migrations/2025_11_02_132038_add_google_id_to_users_table.php` - Migration
- `routes/api.php` - Route `/api/login/google`
- `config/services.php` - Google OAuth config

### Frontend
- `app/layout.js` - GoogleOAuthProvider setup
- `app/login/page.js` - Google login button
- `app/contexts/AuthContext.js` - Google login function

## 🚀 เริ่มใช้งาน

### วิธีที่ 1: Quick Start (แนะนำ)
```bash
# อ่านคำแนะนำสั้นๆ
cat QUICK_START.md
```

### วิธีที่ 2: Setup Check
```bash
# ตรวจสอบว่าทุกอย่างพร้อมหรือยัง
cat SETUP_CHECK.md
```

### วิธีที่ 3: คู่มือฉบับเต็ม
```bash
# อ่านคำแนะนำแบบละเอียด
cat GOOGLE_LOGIN_SETUP.md
```

## ⚙️ ต้องการความช่วยเหลือ?

1. **ยังไม่ได้ตั้งค่า?** → ดู `QUICK_START.md`
2. **มีปัญหา redirect_uri_mismatch?** → ดู `FIX_REDIRECT_URI_ERROR.md` ⚠️ **สำคัญ!**
3. **มีปัญหาอื่น?** → ดู `SETUP_CHECK.md` และ `GOOGLE_LOGIN_SETUP.md` (Troubleshooting)
4. **ต้องการรายละเอียด?** → ดู `GOOGLE_LOGIN_SETUP.md`

## ✅ สถานะระบบ

- ✅ Backend API พร้อมใช้งาน
- ✅ Frontend UI พร้อมใช้งาน
- ✅ Migration รันแล้ว
- ✅ Error handling ครบถ้วน
- ✅ Conditional rendering (แสดงปุ่มเมื่อมี Client ID)

## 📝 สิ่งที่ต้องทำ

1. สร้าง Google OAuth Credentials
2. เพิ่ม `GOOGLE_CLIENT_ID` ใน `backend/.env`
3. เพิ่ม `NEXT_PUBLIC_GOOGLE_CLIENT_ID` ใน `frontend/.env.local`
4. รีสตาร์ท servers

---

**🎉 พร้อมใช้งานแล้ว!** เพียงเพิ่ม Google Client ID ก็สามารถใช้งานได้ทันที

