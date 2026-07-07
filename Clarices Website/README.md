# Injili Apparel — E-Commerce Webstore
> A made-to-order faith-inspired T-shirt webstore built for Clarice.  
> Developed by **Isaac Isaboke / Kūgeria Ltd** · June – July 2026

---

## Overview

Injili Apparel is a Nairobi-based faith-inspired T-shirt brand operating on a made-to-order model. This repository contains the full prototype webstore built as Phase 1 of a two-phase project.

The store allows customers to browse designs, place orders, pay via M-Pesa STK Push, and track their delivery end-to-end via Pickup Mtaani. Clarice manages the business through a dedicated admin dashboard that handles order processing, status updates, product management, and sales analytics.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Backend | PHP 8.2 |
| Database | MySQL 8.0 |
| Local Environment | XAMPP |
| Production Server | DigitalOcean Droplet (Ubuntu 22.04, LAMP) |
| Payments | Safaricom Daraja API (M-Pesa STK Push) |
| Email | PHPMailer + Gmail SMTP |
| SMS | Africa's Talking API |
| WhatsApp | CallMeBot API |
| Delivery | Pickup Mtaani API (mock for prototype) |
| Version Control | Git / GitHub |

---

## Project Structure

```
injili/
│
├── index.html                    # Main storefront (SPA)
├── track.php                     # Public order tracking page
├── track-lookup.php              # Order number search page
├── contact.php                   # Contact page
├── size-guide.php                # Size chart page
├── shipping.php                  # Shipping & returns policy
├── app.js                        # Frontend API connector (real backend calls)
├── .env                          # ⚠️ Credentials — NOT committed to Git
├── .htaccess                     # Apache security rules
│
├── config/
│   ├── config.php                # .env loader + PHP constants
│   └── db.php                    # PDO singleton connection
│
├── sql/
│   └── schema.sql                # Full MySQL schema + seed data
│
├── services/
│   ├── mpesa.php                 # Daraja STK Push + token caching
│   ├── mailer.php                # PHPMailer email templates
│   ├── sms.php                   # Africa's Talking SMS wrapper
│   ├── whatsapp.php              # CallMeBot WhatsApp wrapper
│   ├── notifier.php              # Notification orchestrator
│   └── pickupmtaani.php          # PM mock/live delivery integration
│
├── admin/
│   ├── login.php                 # Admin login
│   ├── logout.php                # Session destroy
│   ├── auth_check.php            # Session guard
│   ├── dashboard.php             # KPI cards + recent orders
│   ├── orders.php                # Order list with filters
│   ├── order_detail.php          # Full order view + status manager
│   ├── update_status.php         # Status update handler
│   ├── products.php              # Product catalogue manager
│   ├── product_edit.php          # Add / edit product form
│   ├── analytics.php             # Revenue + design + delivery stats
│   └── partials/
│       └── sidebar.php           # Shared nav sidebar
│
├── assets/
│   └── css/
│       └── admin.css             # Admin dashboard stylesheet
│
├── uploads/
│   └── products/                 # Uploaded product images
│
├── lib/
│   └── PHPMailer/                # PHPMailer library (not committed)
│       ├── PHPMailer.php
│       ├── SMTP.php
│       └── Exception.php
│
└── api/
    ├── auth/
    │   ├── register.php          # Customer sign-up
    │   ├── login.php             # Customer sign-in
    │   ├── logout.php            # Customer session destroy
    │   └── session.php           # Session state check
    ├── orders/
    │   ├── create.php            # Checkout → order creation
    │   ├── get.php               # Single order fetch + polling
    │   └── list.php              # Customer order history
    ├── products/
    │   └── list.php              # Product catalogue for frontend
    ├── admin/
    │   └── save_product.php      # Add / edit product API
    ├── payment/
    │   ├── mpesa_stk.php         # STK Push initiator
    │   └── mpesa_callback.php    # Daraja payment callback
    ├── delivery/
    │   ├── agents.php            # List PM agents
    │   └── track.php             # Get PM tracking status
    └── newsletter/
        └── subscribe.php         # Email sign-up
```

---

## Features

### Customer-Facing Store
- Product catalogue loaded dynamically from MySQL
- Product detail panel with size and colour selection
- Shopping bag with quantity management
- Multi-step checkout — Details → Delivery → Payment
- M-Pesa STK Push payment integration
- Order confirmation email and SMS on payment
- Customer account registration, login, and order history
- Public order tracking page with full status timeline
- Pickup Mtaani delivery tracking widget

### Admin Dashboard
- Separate admin session — fully isolated from customer sessions
- KPI cards — today's orders, week revenue, awaiting production
- Orders list with filters by status, payment, design, date
- Order detail view — customer info, items, payment receipt, timeline
- Sequential status control — one step forward at a time
- Admin locked out of status changes once handed to Pickup Mtaani
- Pickup Mtaani tracking with simulate button (mock mode)
- Product editor — add, edit, show/hide designs with photo upload
- Analytics — revenue, top designs, delivery modes, daily orders
- Mobile-responsive sidebar with slide-in drawer

### Notification System
- Customer: Email + SMS on order placed, in production, dispatched, delivered
- Admin: Email + WhatsApp on every new paid order
- All notification attempts logged to `notifications_log` table

---

## Local Development Setup

### Prerequisites
- XAMPP (PHP 8.2, MySQL 8.0, Apache)
- ngrok (for M-Pesa callbacks on localhost)
- PHPMailer 6.x library files

### Steps

**1. Clone the repository**
```bash
git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git
cd clients/injili-apparel
```

**2. Copy the environment file**
```bash
cp .env.example .env
```
Fill in your credentials — Daraja sandbox keys, Gmail App Password,
Africa's Talking key, CallMeBot key, and DB details.

**3. Import the database**

Open phpMyAdmin, create a database called `injili_db`, then import:
```
sql/schema.sql
```

**4. Install PHPMailer**

Download PHPMailer 6.x from https://github.com/PHPMailer/PHPMailer/releases
and place the three files in `lib/PHPMailer/`:
- `PHPMailer.php`
- `SMTP.php`
- `Exception.php`

**5. Start ngrok for M-Pesa callbacks**
```bash
ngrok http 80
```
Copy the HTTPS URL and set it as `MPESA_CALLBACK_URL` in `.env`.

**6. Browse**
- Store: `http://localhost/injili/`
- Admin: `http://localhost/injili/admin/login.php`

---

## Production Deployment (DigitalOcean)

The live prototype is deployed on a DigitalOcean Droplet.

**Live URLs:**
- Store: `http://143.110.250.155/injili/`
- Admin: `http://143.110.250.155/injili/admin/login.php`

**Server:** Ubuntu 22.04 LTS, Apache 2.4, PHP 8.2, MySQL 8.0

**Note:** M-Pesa sandbox is blocked on DigitalOcean IPs by Safaricom's
Incapsula WAF. This resolves with live Daraja credentials (Phase 2).

---

## Environment Variables

Copy `.env.example` to `.env` and fill in:

```env
# Application
APP_URL=http://localhost/injili
APP_ENV=sandbox

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=injili_db
DB_USER=root
DB_PASS=

# M-Pesa Daraja
MPESA_ENV=sandbox
MPESA_CONSUMER_KEY=
MPESA_CONSUMER_SECRET=
MPESA_SHORTCODE=174379
MPESA_PASSKEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
MPESA_CALLBACK_URL=

# Email (PHPMailer + Gmail SMTP)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=Injili Apparel

# Admin Notifications
ADMIN_EMAIL=
ADMIN_PHONE=

# Africa's Talking SMS
AT_API_KEY=
AT_USERNAME=sandbox
AT_SENDER_ID=INJILI

# CallMeBot WhatsApp
CALLMEBOT_PHONE=
CALLMEBOT_API_KEY=

# Pickup Mtaani
PM_USE_MOCK=true
PM_BASE_URL=https://api.pickupmtaani.com/v1
PM_API_KEY=
PM_SENDER_NAME=Injili Apparel
```

---

## Security

- All DB queries use PDO prepared statements — no SQL injection
- Passwords hashed with `password_hash()` (bcrypt)
- Admin and customer sessions are completely isolated
- `.env` file blocked from browser access via `.htaccess`
- PHP errors suppressed from browser output — logged only
- File uploads validated by MIME type, not filename extension

---

## Project Status

### Phase 1 — Prototype (current)
- ✅ Full storefront with 7 product designs
- ✅ M-Pesa STK Push payment (sandbox, works on localhost)
- ✅ Order management and status tracking
- ✅ Admin dashboard with analytics
- ✅ Mock Pickup Mtaani delivery tracking
- ✅ Email notifications
- ✅ Deployed on DigitalOcean
- ⏳ SMS and WhatsApp notifications (pending API keys)
- ⏳ Real product photography (pending from client)

### Phase 2 — Going Live (pending business registration)
- Live M-Pesa Paybill / Till number
- Live Pickup Mtaani API
- Custom domain
- SSL certificate
- WhatsApp Business API
- Visa / Mastercard card payments
- Design Lab feature
- Member Drops system

---

## Client

**Business:** Injili Apparel  
**Owner:** Clarice  
**Location:** Nairobi, Kenya  
**Model:** Made-to-order faith-inspired T-shirts  

---

## Developer

**Isaac Isaboke**  
Founder, Kūgeria Ltd  
Nairobi, Kenya  
