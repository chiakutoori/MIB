# MIB PHOTOMAN PH - 25% System Structure Prototype
**A Web-Based Package Customization and Scheduling Management System with Email Notification for MIB Photoman (under CSLB Events Planning)**

---

## 📸 1. System Overview & Tech Stack
This prototype implements the **25% operational scope** established in Chapter 1 of the thesis study. It eliminates the business's reliance on manual Facebook Messenger group chats, paper forms, and unorganized notes by providing an integrated web-based architecture.

- **Backend:** PHP 8.3 (Native Built-in Server / Apache / Nginx)
- **Database:** SQLite 3 with PDO (Self-initializing, zero external database setup required)
- **Frontend:** Responsive HTML5, Modern CSS3 with Custom Properties & Glassmorphism, Vanilla ES6 JavaScript
- **Aesthetic Theme:** Strict **Inverted Monochromatic (Black, White, Grey)** consistent with the MIB suit-and-tie logo.
- **Background:** High-resolution team cover photo (`assets/images/mib-cover.jpg`) applied with a dark translucent scrim for legibility and cinematic branding.

---

## 🎯 2. Thesis Alignment (25% Foundation Scope)

| Thesis Specific Objective | System Implementation | Location |
| :--- | :--- | :--- |
| **1. Web-Based Customization & Scheduling** | Interactive 4-step wizard: duration (2–8 hrs), suited shooters (1–4), print stations (1–3), add-ons, live cost computation, fixed ₱2,000 down payment. | [`booking.php`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/booking.php), [`packages.php`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/packages.php) |
| **2. Centralized Database** | Consolidated SQLite database via PDO linking clients, event bookings, staff schedules, journals, reviews, and notifications without duplication. | [`config/db.php`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/config/db.php) |
| **3. Staff Assignment & Conflict Engine** | Admin delegates Lead Photographers and Operators; real-time double-booking conflict detection flags overlapping assignments. | [`admin/schedules.php`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/admin/schedules.php), [`staff/index.php`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/staff/index.php) |
| **4. Post-Event Journaling** | Suited crew log total 4R prints distributed, estimated guest attendance, equipment status, and operational highlights/issues. | [`staff/journal.php`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/staff/journal.php), [`admin/journals.php`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/admin/journals.php) |
| **5. Customer Service Reviews** | Clients submit 1–5 star ratings (overall, photographer presentation, print speed/magnet quality) and qualitative feedback for staff evaluations. | [`client/review.php`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/client/review.php), [`admin/reviews.php`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/admin/reviews.php) |
| **6. Centralized Notification Engine** | System dispatches and audits simulated email notifications (Booking Confirmations, ₱2,000 DP receipts, Schedule Reminders, Staff Alerts). | [`admin/notifications.php`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/admin/notifications.php) |

---

## 👥 3. User Roles & Evaluation Credentials

You can test any role instantly using the **Quick 1-Click Demo Buttons** on [`login.php`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/login.php) or with manual credentials:

| Role | Email | Password | Access Privileges |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@mibphotoman.com` | `password123` | Master overview, verify ₱2k DP, assign crew, check conflicts, audit journals & notifications |
| **Staff (Lead Photographer)** | `marcus@mibphotoman.com` | `password123` | View assigned call times, venue directives, submit post-event journals |
| **Staff (Dye-Sub Operator)** | `isabella@mibphotoman.com` | `password123` | View printing station assignments, submit post-event journal logs |
| **Client / Event Host** | `client@gmail.com` | `password123` | View reservation status, ₱2,000 DP record, assigned team, submit service review |

---

## 🚀 4. How to Run

### Method A: Quick Double-Click (Windows)
Double-click [`run_server.bat`](file:///C:/Users/Christian/Downloads/MIB_PHP_Prototype/run_server.bat). It automatically starts the PHP web server on `http://localhost:8000`.

### Method B: Terminal / PowerShell
```powershell
cd C:\Users\Christian\Downloads\MIB_PHP_Prototype
php -S localhost:8000
```
Open `http://localhost:8000` in any web browser.
