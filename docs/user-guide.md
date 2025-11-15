# Cultivation V2 — User Guide

Welcome! This guide will help you install, set up, and use the core features of the Cultivation V2 School Management modules, including creating and printing Testimonial Certificates.

---

## Visual Index
- Login: ![Login Screen](docs/images/login.PNG)
- Full Menu: ![Main Navigation Sidebar](docs/images/full-menu.PNG)
- Student List (search): ![Student List DataTable](docs/images/student-list.png)
- Create Testimonial (form): ![Testimonial Creation Form](docs/images/testimonial-form.png)
- Certificate View: ![Testimonial Certificate Layout](docs/images/certificate-view.png)
- Print Settings (Edge/Chrome): ![Browser Print Dialog Settings](docs/images/print-settings.png)
- Institute Settings: ![Institute Configuration Form](docs/images/institute-settings.png)

> If images don’t display, add screenshots to `docs/images/` with the filenames above. See “How to Capture Screenshots” at the end of this guide.

---

## 1) System Requirements
- Windows 10/11
- XAMPP (PHP 8.x, Apache, MySQL/MariaDB)
- Composer (PHP dependency manager)
- Node.js 18+ (for asset build; optional if assets already built)
- Browser: Microsoft Edge or Google Chrome (for best print results)

---

## 2) Installation (Local with XAMPP)
1. Place the project in your XAMPP `htdocs` folder:
   - Example: `C:\xampp\htdocs\cultivation-V2`
2. Create a database (e.g., `cultivation_v2`) in phpMyAdmin.
3. Copy env file:
   - Duplicate `.env.example` → `.env`
4. Update `.env` with your DB settings:
   - `DB_DATABASE=cultivation_v2`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=` (leave empty if default)
5. Install dependencies (run in project folder):
   - `composer install`
6. Generate app key:
   - `php artisan key:generate`
7. Run migrations and seed (if available):
   - `php artisan migrate`
8. Link storage (for image access):
   - `php artisan storage:link`
9. Build front-end assets (if needed):
   - `npm install`
   - `npm run build` (or `npm run dev` during development)
10. Start Apache and MySQL in XAMPP.
11. Visit in browser:
   - `http://localhost/cultivation-V2/public`

> Note: If your environment serves from the project root via a virtual host, point the DocumentRoot to `public/`.

---

## 3) First-Time Setup
Before using certificates, set the institute details so headers print correctly:
- Institute Name
- Address
- Establishment year (Estd.)
- Official email and mobile
- Institute logo
- Headmaster/Principal signature

Where to set:
- In the admin/settings area (Server/Institute Configuration). If you don’t have access, ask your administrator to update these values.

Logo and signature image path used by the system:
- `public/upload/image/cultivation/<file>`

Example:
![Institute Settings](docs/images/institute-settings.png)

---

## 4) Users and Login
- Open the application URL (see Installation step 11).
- Login with your provided username/password.
- If you don’t have credentials, contact your administrator.

---

## 5) Student List and Testimonials
You can create Testimonial Certificates directly from the student list.

- Open the Student List page.
- Use the search box to quickly find a student (supports partial matches). The table is powered by DataTables; you can search by name, class, etc.
- Eligibility: Testimonials are restricted to Class Ten and Twelve only. If a student is not in these classes, the create/print options are not shown.

Actions:
- Create Testimonial: Opens the testimonial form pre-filled with student details.
- Edit/Update: Update an existing testimonial.
- Print: Opens the print-ready certificate view.

Screens & Key Areas:
1. Student List
   ![Student List DataTable](docs/images/student-list.png)
   - Search: Top-right live filter.
   - Eligibility badges: "Not Eligible" vs create/edit icons for Class Ten/Twelve.
   - Action icons: View (eye), Edit (pen), Delete (trash), plus testimonial status.
2. Create Testimonial Form
   ![Testimonial Creation Form](docs/images/testimonial-form.png)
   - Read-only Student Details panel (top aqua block).
   - Exam fields: Year, Roll, Registration, GPA, Grade, Subject/Department, Exam Name, Board.
   - Auto Ref No: Disabled input with tooltip placeholder.
   - Date pickers: Issue Date & Composed Date.
   - Composed By & Remarks for internal notes.

---

## 6) Creating a Testimonial
1. From Student List, click Create (for eligible students).
2. The form shows student details (read-only) and fields to complete:
   - Exam Name
   - Education Board
   - Year
   - Roll, Registration, Subject, Grade/GPA
   - Issue Date, Composed By, Composed Date, etc.
3. Reference/SL No. is generated automatically – no manual entry required.
4. Save the form.

Placeholders:
- All fields include hints/placeholders. Only enter the requested SSC/HSC exam info; personal data comes from the admission record.

---

## 7) Printing the Certificate
1. Open an existing testimonial and click Print.
2. The certificate shows:
   - Header with logo, institute name, address, Estd., email, and mobile
   - Testimonial body with a watermark (logo) inside the border frame
   - Footer area with Headmaster/Principal signature
3. Browser print settings:
   - Paper: A4
   - Orientation: Landscape
   - Margins: Default or Minimal (fits on one page by design)
   - Background graphics: ON (ensures colored pills/borders look correct)
4. Click Print to produce the final document.

Watermark:
- The watermark appears faintly behind the text and only within the framed body. If you do not see it, see Troubleshooting below.

Certificate & Print:
1. Certificate View
   ![Testimonial Certificate Layout](docs/images/certificate-view.png)
   - Header: Logo (left), Institute Name (bold), Address, Estd, Email, Mobile.
   - Pill: "Testimonial Certificate" badge dark blue.
   - Frame: Outer thick + inner thin border; watermark centered.
   - Body elements: SL/Date line, narrative paragraph, DOB line, conduct paragraph, closing line.
   - Footer: Composed by section (left); Signature line + Head Master name (right).
2. Print Dialog
   ![Browser Print Dialog Settings](docs/images/print-settings.png)
   - Ensure: Paper A4, Orientation Landscape (if needed), Margins Default/Minimal, Background graphics enabled.
   - Preview: Confirms watermark faint and certificate fits single page.

---

## 8) Reference/SL Number
- The system auto-generates a Reference/SL number when you create a testimonial.
- If data was imported from an older system and some references are missing, an admin can backfill.

Backfill command (Admin/IT):
```
php artisan testimonials:backfill-ref
```

---

## 9) Importing Students (Optional)
If your role includes data import:
- Export the student template (if available) or obtain it from admin.
- Fill the template with student data.
- Use the Import Students feature to upload. Ensure the columns match the template exactly.

> If you don’t see Import/Export options, your account may not have permission.

---

## 10) Tips for Best Print Quality
- Use Microsoft Edge or Google Chrome.
- Enable Background graphics in the print dialog.
- Avoid dark mode extensions while printing.
- If text shifts to a second page, ensure the zoom is 100% and margins are Default/Minimal.

Visual reference:
![Print Settings](docs/images/print-settings.png)

---

## 11) Troubleshooting
- Watermark not visible in print:
  - Ensure Background graphics is enabled (for colored elements). The watermark itself is an image and should print, but some filters can affect it. Try a hard refresh (Ctrl+F5) and print again.
  - Use Edge/Chrome latest version.
- Email/Mobile not showing in header:
  - Ask an admin to complete the institute contact fields in settings.
- DataTables search not working:
  - Refresh the page. Make sure scripts fully loaded.
- Reference number format does not match your policy:
  - Contact admin/IT to adjust the reference generator to your desired format.

Watermark visibility example (subtle, centered within the frame):
![Testimonial Certificate Layout](docs/images/certificate-view.png)

---

## 12) Roles and Permissions
- Operators: Create/Edit/Print testimonials for eligible students.
- Admins: Manage institute settings, images (logo/signature), and backfill operations.

If you need additional features or roles, contact your administrator.

---

## 13) Support
- Internal admin or IT first point of contact.
- Provide screenshot, student name, and steps to reproduce any issue.

---

## 14) Change Log (Highlights)
- Testimonial from Student List
- Auto SL/Reference generation + backfill command
- Class eligibility (Ten/Twelve)
- Print-optimized layout with watermark
- DataTables search enabled and styled

---

## How to Capture & Maintain Screenshots (Windows)
1. Press `Win + Shift + S` (Snipping Tool) → Rectangle snip.
2. Capture only the functional area (avoid excessive browser chrome unless relevant like Print dialog).
3. Save with EXACT filenames already used:
   - `login.PNG`
   - `full-menu.PNG`
   - `student-list.png`
   - `testimonial-form.png`
   - `certificate-view.png`
   - `print-settings.png`
   - `institute-settings.png`
4. Resolution target: 1200–1600px width; keep aspect ratio; avoid heavy compression.
5. Privacy: Blur faces/signatures if required by policy before committing.
6. Consistency: If UI changes, recapture affected images and keep historical versions in a dated subfolder (e.g., `docs/images/archive/2025-11/`).
7. Git hygiene: Large images increase repo size—prefer PNG; avoid multi-MB files (optimize via Tinypng if >500KB).

