<div align="center">
  <img src="/docs/logo.png" height="90" />
  <h1><strong>Cultivation – The Education Manager</strong></h1>
  <p>The most complete school, college & institute management system.</p>
  
  <img src="./assets/banner.png" width="90%" />
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

> 📌 *Replace these PNG files inside `/screenshots` folder*

| Dashboard | Admission | Result Manager |
|----------|-----------|----------------|
| ![Dashboard](./screenshots/dashboard.png) | ![Admission](./screenshots/admission.png) | ![Result](./screenshots/result.png) |

---

## 🛠 Installation

```bash
git clone https://github.com/hasnat-saimun/cultivation-V2.git
cd cultivation-V2
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
