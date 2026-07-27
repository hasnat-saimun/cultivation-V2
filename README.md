<div align="center">
  <img src="/docs/images/logo.png" height="90" />
  <h1><strong>Cultivation – The Education Manager</strong></h1>
  <p>The most complete school, college & institute management system.</p>
</div>

---

## 📌 Overview
Cultivation is a modern & powerful Education Management System designed for schools, colleges, madrasas, coaching centers, training institutes, and academies.  
It includes Admission, Attendance, Accounts, Academics, HR, Result Management & more—combined into one clean dashboard.

---

## 🚀 Features (v1.0.1)

### 🧾 **Admission Manager**
- Admission workflow  
- Application → Registration → Approval  
- Document upload  

### 📊 **Result Manager**
- Result entry  
- Publishing system  
- Marksheet generator  

### 💵 **Cash Manager**
- Fee collection  
- Invoice generator  
- Cash flow report  

### 🕒 **Attendance Manager**
- Students, teachers & staff  
- Device-ready attendance mapping  
- Attendance export  

### 🏫 **Academic Manager**
Includes:  
- Transfer Certificate  
- Testimonial  
- Class Routine  
- Exam Routine  
- Syllabus  
- Semester Plan  

### 👨‍🏫 **Teacher & Staff Manager**
- Staff profiles  
- Role & subject assignment  
- Attendance record  

---

## 🖼 Screenshot Preview

> 📌 Place screenshots inside `/docs/images/screenshots/`.

| Dashboard | Admission | Result Manager |
|----------|-----------|----------------|
| ![Dashboard](/docs/images/screenshots/dashboard.png) | ![Admission](/docs/images/screenshots/admission.png) | ![Result](/docs/images/screenshots/result.png) |

---

## 🛠 Installation

### Requirements
- PHP 8.x (XAMPP recommended on Windows)
- Composer
- MySQL/MariaDB
- Node.js 18+ (for asset build via Vite)

### Clone & Backend Setup
```bash
git clone https://github.com/Virtual-IT-Professional/cultivation-v.2.0.0.git
cd cultivation-v.2.0.0
composer install
```

Copy environment file:
- PowerShell (Windows):
```powershell
Copy-Item .env.example .env
```
- macOS/Linux:
```bash
cp .env.example .env
```

Configure DB in `.env` and then:
```bash
php artisan key:generate
php artisan migrate
php artisan storage:link
```

### Frontend (Vite)
```bash
npm install
npm run build   # production build
# or during development
npm run dev
```

### Run
- Laravel dev server:
```bash
php artisan serve
```
- XAMPP/Apache: point DocumentRoot to `public/` or visit `http://localhost/cultivation-v.2.0.0/public` if placed under `htdocs`.

---

## 🔗 Helpful Links
- User Guide (in-app): `/user-guide`
- Result Archive: `/result-archive`
- Bulk Student ID Cards: `/student/idcards/bulk`

---

## 🧩 Modules Overview
- Admission, Academics, Exam & Result, Attendance, Accounts
- Staff/Teacher management with bulk upload & photo tools
- Institute CMS: sliders, galleries, principal speech, committees
- Placement Cell & Needy Student panels

---

## 📝 Notes
- Screenshots should be kept under `docs/images/screenshots/`.
- For logo/signature used on certificates: upload via server configuration screens; files are stored under `public/upload/image/cultivation/`.
