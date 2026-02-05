# 📋 คู่มือตั้งค่า Environment Variables บน Railway

## 🎯 ขั้นตอนที่ 1: เข้าไปที่ Service

1. **ไปที่ [railway.app](https://railway.app)** และ Login
2. **เลือก Project** ที่มี backend service ของคุณ (เช่น `divine-creativity`)
3. **คลิกที่ service** `Booking-Sci9-Computer...` (กล่องสีเข้มที่มี GitHub icon)

---

## 🎯 ขั้นตอนที่ 2: ไปที่แท็บ Variables

1. **ด้านบนของหน้า service** จะมีแท็บหลายอัน:
   - Deployments
   - Database
   - Backups
   - **Variables** ← คลิกที่นี่
   - Metrics
   - Settings

2. **คลิกที่แท็บ "Variables"**

---

## 🎯 ขั้นตอนที่ 3: เพิ่ม Environment Variables

ในหน้า Variables คุณจะเห็น:
- **ด้านซ้าย**: รายการตัวแปรที่มีอยู่ (ถ้ามี)
- **ด้านขวา**: ปุ่ม **"+ New Variable"** หรือ **"Add Variable"**

### 3.1 คลิกปุ่ม "+ New Variable"

### 3.2 เพิ่มตัวแปรทีละตัว (ทำซ้ำ 8 ครั้ง):

#### ตัวแปรที่ 1: DB_CONNECTION
- **Name**: `DB_CONNECTION`
- **Value**: `mysql`
- **Environment**: Production (หรือเลือกทั้งหมด)
- คลิก **"Add"** หรือ **"Save"**

#### ตัวแปรที่ 2: DB_HOST
- **Name**: `DB_HOST`
- **Value**: `centerbeam.proxy.rlwy.net`
- **Environment**: Production
- คลิก **"Add"**

#### ตัวแปรที่ 3: DB_PORT
- **Name**: `DB_PORT`
- **Value**: `18790`
- **Environment**: Production
- คลิก **"Add"**

#### ตัวแปรที่ 4: DB_DATABASE
- **Name**: `DB_DATABASE`
- **Value**: `railway`
- **Environment**: Production
- คลิก **"Add"**

#### ตัวแปรที่ 5: DB_USERNAME
- **Name**: `DB_USERNAME`
- **Value**: `root`
- **Environment**: Production
- คลิก **"Add"**

#### ตัวแปรที่ 6: DB_PASSWORD
- **Name**: `DB_PASSWORD`
- **Value**: `uNMpceEVTALERWRcffcovLrhSprcFJyd`
- **Environment**: Production
- คลิก **"Add"**

#### ตัวแปรที่ 7: APP_ENV
- **Name**: `APP_ENV`
- **Value**: `production`
- **Environment**: Production
- คลิก **"Add"**

#### ตัวแปรที่ 8: APP_DEBUG
- **Name**: `APP_DEBUG`
- **Value**: `false`
- **Environment**: Production
- คลิก **"Add"**

#### ตัวแปรที่ 9: APP_KEY (สำคัญมาก!)
- **Name**: `APP_KEY`
- **Value**: `<ค่าที่ได้จาก php artisan key:generate --show>`
- **Environment**: Production
- คลิก **"Add"**

**วิธีหา APP_KEY:**
```bash
cd D:\booking\backend
php artisan key:generate --show
```
ก็อปค่าที่ได้มา (เช่น `base64:vjysU3WHfdhnpiFDlBX1wrqR4U+yo8QOSrNqk+FFDiM=`) แล้ววางใน Value

---

## 🎯 ขั้นตอนที่ 4: ตรวจสอบว่าเพิ่มครบแล้ว

ในหน้า Variables ควรจะมีตัวแปรเหล่านี้ทั้งหมด:
- ✅ DB_CONNECTION
- ✅ DB_HOST
- ✅ DB_PORT
- ✅ DB_DATABASE
- ✅ DB_USERNAME
- ✅ DB_PASSWORD
- ✅ APP_ENV
- ✅ APP_DEBUG
- ✅ APP_KEY

---

## 🎯 ขั้นตอนที่ 5: Restart/Redeploy Service

หลังจากตั้งค่า Environment Variables แล้ว:

1. **กลับไปที่หน้า Architecture** (คลิกชื่อ project ด้านบน)
2. **คลิกที่ service** `Booking-Sci9-Computer...`
3. **กดปุ่ม "Deploy"** หรือ **"Redeploy"** (ถ้ามี)
4. **รอให้ service restart** (สถานะจะเปลี่ยนเป็น Running)

---

## 🎯 ขั้นตอนที่ 6: ตรวจสอบว่า Service รันได้

1. **ดูสถานะ service:**
   - ต้องเป็น **Running** (สีเขียว) ✅
   - ถ้ายังเป็น **Failed** (สีแดง) → ดู Logs

2. **ทดสอบ Backend:**
   - ไปที่แท็บ **Settings** → **Domains**
   - Copy URL (เช่น `https://xxx.up.railway.app`)
   - เปิดใน browser: `https://xxx.up.railway.app/api/stats`
   - ถ้าได้ JSON → Backend ทำงาน ✅
   - ถ้าไม่ได้ → ดู Logs

---

## 🔍 ถ้าไม่เจอแท็บ Variables

บางครั้งแท็บ Variables อาจจะอยู่ที่:
- **Settings** → **Variables**
- หรือ **Settings** → **Environment Variables**
- หรือ **Configuration** → **Variables**

ลองหาดูใน Settings หรือ Configuration

---

## 📸 ตัวอย่างหน้าจอ

```
┌─────────────────────────────────────┐
│ Booking-Sci9-Computer...           │
├─────────────────────────────────────┤
│ [Deployments] [Database] [Backups] │
│ [Variables] ← คลิกที่นี่            │
│ [Metrics] [Settings]                │
├─────────────────────────────────────┤
│                                     │
│  + New Variable  ← คลิกปุ่มนี้      │
│                                     │
│  Name: [DB_CONNECTION        ]      │
│  Value: [mysql              ]       │
│  Environment: [Production ▼]        │
│                                     │
│  [Add] [Cancel]                     │
└─────────────────────────────────────┘
```

---

## ⚠️ สิ่งสำคัญ

1. **APP_KEY ต้องมี** - ถ้าไม่มี Laravel จะไม่ทำงาน
2. **DB_* ต้องถูกต้อง** - ต้องใช้ค่าจาก MySQL service บน Railway
3. **Environment ต้องเลือก Production** - เพื่อให้ใช้ใน production
4. **หลังจากตั้งค่าแล้วต้อง Redeploy** - เพื่อให้ใช้ค่าใหม่

---

## 🆘 ถ้ายังไม่ได้

1. **ตรวจสอบ Logs:**
   - ไปที่แท็บ **Deployments** หรือ **Logs**
   - ดู error messages

2. **ตรวจสอบว่า MySQL service รันอยู่:**
   - ดูที่ Architecture
   - MySQL service ต้องเป็น Running

3. **ทดสอบ Database connection:**
   - ใช้ Railway Shell:
     ```bash
     railway connect MySQL
     ```
   - หรือใช้ MySQL client:
     ```bash
     mysql -h centerbeam.proxy.rlwy.net -u root -p --port 18790 railway
     ```

---

## ✅ Checklist

- [ ] เข้าไปที่ service บน Railway
- [ ] ไปที่แท็บ Variables
- [ ] เพิ่มตัวแปรทั้งหมด 9 ตัว
- [ ] ตรวจสอบว่าเพิ่มครบแล้ว
- [ ] Redeploy service
- [ ] ตรวจสอบสถานะ Running
- [ ] ทดสอบ Backend URL

---

## 📞 ต้องการความช่วยเหลือ?

บอกผมว่า:
1. คุณเห็นแท็บ Variables หรือไม่?
2. คุณสามารถเพิ่มตัวแปรได้หรือไม่?
3. Service รันได้หรือยัง? (สถานะ Running?)
4. Error message อะไร (ถ้ามี)?

ผมจะช่วยแก้ไขให้ตรงจุด!

