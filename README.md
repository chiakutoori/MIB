# MIB PHOTOMAN PH - 25% System Structure Prototype
**A Web-Based Package Customization and Scheduling Management System with Email Notification for MIB Photoman (under CSLB Events Planning)**

## System Overview & Tech Stack
An integrated web-based platform for package customization, event scheduling, staff assignment, and email notifications, replacing manual Facebook Messenger group chats, paper forms, and unorganized notes.

- **Backend:** PHP 8.3 (Native Built-in Server / Apache / Nginx)
- **Database:** SQLite 3 with PDO (Self-initializing, zero external database setup required)
- **Frontend:** Responsive HTML5, CSS3, Vanilla ES6 JavaScript
- **Theme:** Inverted Monochromatic (Black, White, Grey) with team cover photo background

## Key Features
- 4-step booking wizard (duration 2-8 hrs, 1-4 shooters, 1-3 print stations, add-ons) with live cost computation and fixed ₱2,000 down payment
- Centralized SQLite database linking clients, event bookings, staff schedules, and notifications
- Admin staff assignment with real-time double-booking conflict detection
- Email notifications for booking confirmations, DP receipts, schedule reminders, and staff alerts

## User Roles
- **Administrator** — Master overview, verify DP, assign crew, check conflicts
- **Staff (Lead Photographer / Dye-Sub Operator)** — View assigned call times and station assignments
- **Client / Event Host** — View reservation status, DP record, and assigned team

## Roadmap to 100% System
The prototype implements the core scope; the following modules complete the full system:

1. **Real Email Notification Engine** — SMTP delivery (e.g., PHPMailer) replacing the simulated dispatch, with automated schedule reminders.
2. **Payment & Billing Module** — Online payment channels (GCash/Maya), PDF invoices and receipts, remaining-balance tracking, and refund handling.
3. **Post-Event Journal Interface** — Staff screens for logging prints distributed, guest counts, and equipment status (database table already exists).
4. **Customer Review Submission Flow** — Client-facing rating and feedback form (table exists; reviews are currently display-only).
5. **Reports & Analytics** — Revenue, booking-trend, and staff-utilization reports with CSV/PDF export. *(Optional: financial reporting is outside the system's documented scope.)*
6. **Master Calendar View** — Month-by-month calendar of locked vs. available event dates.
7. **Package Management CRUD** — Admin screens to add, edit, and archive packages and pricing.
8. **Staff/User Management** — Screens to add, edit, and deactivate staff accounts.
9. **Client Booking Self-Service** — Cancel/reschedule requests and full booking-status lifecycle tracking.
10. **Inquiry/Contact Form** — Working contact form routed to an admin inbox.
11. **REST API Layer** — Endpoints for calendar sync and mobile/third-party integrations (the empty `api/` directory).
12. **Production Deployment** — MySQL/PostgreSQL migration, HTTPS, CSRF protection, and real hosting.

## How to Run
Double-click `run_server.bat`, or run `php -S localhost:8000` from the project folder, then open `http://localhost:8000` in any web browser.
