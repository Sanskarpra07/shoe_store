# StepStyle - Online Shoe Store

A complete **Online Shoe Store Management System** built as a final project for the
BCA 5th-semester course *Management Information System*. It is custom-coded in
**PHP + MySQL** (no framework) using **XAMPP**, and covers both a customer-facing
storefront and a full admin panel.

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Problem Statement](#2-problem-statement)
3. [Objectives](#3-objectives)
4. [Features / Modules](#4-features--modules)
5. [Scope and Limitations](#5-scope-and-limitations)
6. [Tools and Technologies Used](#6-tools-and-technologies-used)
7. [System Requirements](#7-system-requirements)
8. [Installation / How to Run](#8-installation--how-to-run)
9. [Default Accounts](#9-default-accounts)
10. [Testing](#10-testing)
11. [Conclusion and Future Enhancements](#11-conclusion-and-future-enhancements)

---

## 1. Introduction

**StepStyle** is a web-based e-commerce system for buying and selling shoes online.
Customers can browse shoes by category, brand or keyword, add items to a wishlist
and shopping cart, place orders, and pay using **Cash on Delivery (COD)**, **eSewa**
or **Khalti**. New accounts are verified with a **6-digit OTP sent to the email**,
and passwords can be recovered through the same OTP mechanism.

The system also has a secure **admin panel** where the administrator manages
products, categories, brands, inventory (stock log), delivery slots, customer and
staff users, product reviews, orders, and generates sales reports.

All payment gateways run in **sandbox/test mode**, so the project can be
demonstrated safely without real transactions.

---

## 2. Problem Statement

Traditional shoe shops face several problems:

- Manual record keeping makes stock, sales and customer information hard to track.
- Customers must visit the store physically and have no way to see what is available.
- There is no order tracking, and recording sales is time-consuming and error-prone.

This project solves these problems by providing an **online catalogue**, an
**automated cart and order system**, **digital payments**, **delivery slot
booking**, and an **admin dashboard** with reports — all in one place.

---

## 3. Objectives

### General Objective
To develop a user-friendly online shoe store that allows customers to browse,
order and pay for shoes online while giving the admin full control over products,
inventory and orders.

### Specific Objectives
- To provide **customer registration with OTP email verification** and **secure login**.
- To provide **password recovery** through OTP verification.
- To allow browsing and searching shoes by **category, brand and keyword**.
- To provide a **wishlist** and a **shopping cart**.
- To manage **orders with status tracking** (Pending, Processing, Shipped, Delivered, Cancelled).
- To support **multi-payment** options (eSewa, Khalti, Cash on Delivery).
- To provide **delivery slot selection** during checkout.
- To provide an **admin panel** to manage products, stock, delivery slots, users, reviews and generate reports.

---

## 4. Features / Modules

### 4.1 User Registration, Login and OTP Verification
- Customers register with name, email, phone and password.
- A **6-digit OTP** is generated and "sent" to the email; the account is activated
  only after the correct OTP is entered.
- Passwords are stored as **hashed** values; login is session-based.

### 4.2 Password Recovery (Forgot Password)
- A customer can enter a registered email and receive an **OTP**.
- After OTP verification the customer sets a **new password** and logs in again.

### 4.3 Product Browsing and Search
- Home page shows featured products plus **categories and brands with their assigned icons**; the shop page lists all products.
- Products can be filtered by **category and brand** and searched by **keyword**.
- Product detail page shows description, price, discount, stock, size, color, image
  and **customer reviews**.

### 4.4 Wishlist
- Logged-in customers can keep a wishlist of favourite shoes.
- Items can be **moved to the cart** or removed from the wishlist.

### 4.5 Shopping Cart
- Session-based cart: add / update quantity / remove / clear.
- Cart page shows subtotal and total with discount pricing.

### 4.6 Order Management and Tracking
- Checkout captures name, email, phone, address and **delivery slot**.
- Orders are stored with statuses; customers can view order history and a
  **printable invoice**.
- Public **tracking page** shows status by Order ID + email.

### 4.7 Delivery Slots
- The admin defines **time slots** (e.g. Morning 8-11 AM, Afternoon 12-3 PM, Evening 4-7 PM).
- Customers pick a slot at checkout; the slot is saved on the order and shown in
  order details, admin order view and invoice.

### 4.8 Payment Gateway (eSewa, Khalti, COD)
- **Cash on Delivery**: order is placed and stock reduced immediately.
- **eSewa (ePay V2)**: order is created as `pending` and the customer is sent to
  the eSewa **UAT (test)** gateway via a signed HMAC-SHA256 form; on return the
  `esewa_success.php` callback verifies the signature and confirms the transaction
  with eSewa's status API before completing the order and reducing stock.
- **Khalti (KPG-2)**: order is created as `pending` and Khalti **sandbox** checkout
  is initiated; the `khalti_callback.php` callback verifies the payment with the
  Lookup API, completes the order and reduces stock.
- **Checkout requires login**: customers must be logged in to place an order
  (see section 9 test customer).
- Future work: **Fonepay** support can be added easily.

### 4.9 Admin Panel Modules
- **Dashboard** — statistics (total products, stock, orders, revenue, customers) with quick links.
- **Products** — add / edit / delete products with image upload and automatic **stock log** entries.
- **Categories & Brands** — add / edit / delete (a brand can be deleted only when unused). Each entry supports a **Font Awesome icon** picked from fontawesome.com — the icon code is pasted into a text field with a live preview and is displayed next to the name in the admin tables and on the storefront homepage.
- **Delivery Slots** — add / edit / activate / deactivate / delete slots.
- **Orders** — search orders, view full detail (items, payment, delivery slot) and update status.
- **Reports** — date-range sales reports (orders, revenue, payment methods, top products) with **CSV export**.
- **Reviews** — approve / reject / delete customer reviews.
- **Stock Log** — add or remove stock and keep a history of every adjustment.
- **Users** — admin can create admin/staff users and view registered customers.
- **Admin UI** — a custom modern theme (`assets/css/admin.css`) with a dark navy + orange design, icon-based sidebar navigation and stat cards; all success / error / info flash messages auto-dismiss after 4 seconds via `assets/js/notify.js` (applied on both admin pages and the storefront).

### 4.10 Security
- Passwords are **hashed** (`password_hash` / `password_verify`).
- Admin pages require a **session** with the correct role.
- User input is validated; database queries mostly use **prepared statements**.

---

## 5. Scope and Limitations

### Scope
- Works as a complete small-business shoe store with customer + admin sides.
- Payments are integrated in sandbox mode (safe for demonstration).

### Limitations
- OTP email verification runs in **demo mode** — no real mail server is configured,
  so the OTP is displayed on screen. Replace `@mail()`/SMTP with a live service for production.
- Payment gateways are in **test mode**; live credentials are needed for real money.
- Vanilla PHP codebase (no PHP framework) — suited for a classroom project.

---

## 6. Tools and Technologies Used

| Purpose        | Technology                      |
|----------------|---------------------------------|
| Frontend       | HTML5, CSS3, JavaScript, **Bootstrap 5, Bootstrap Icons, Font Awesome** |
| Backend        | **PHP**                         |
| Database       | **MySQL / MariaDB**             |
| Server         | **Apache (XAMPP)**              |
| Development    | VS Code, XAMPP Control Panel, phpMyAdmin |
| Payments       | eSewa UAT, Khalti Sandbox (via cURL) |
| Version control| Git, GitHub                      |

---

## 7. System Requirements

- [XAMPP](https://www.apachefriends.org/) (Apache 2.4+, PHP 7.4+, MariaDB/MySQL)
- PHP extensions (bundled with XAMPP):
  - `mysqli` (database)
  - `curl` (Khalti API; the app still works without it via sandbox fallback)
- The app detects its own URL (folder name, host, port) automatically, so it runs
  in any folder under `htdocs`.

---

## 8. Installation / How to Run

### Step 1 — Start the server
Open **XAMPP Control Panel** and start **Apache** and **MySQL**.

### Step 2 — Copy the project
Copy the `shoe_store` folder into the web root:

| OS      | Location                    |
|---------|-----------------------------|
| Windows | `C:\xampp\htdocs\shoe_store` |
| Linux   | `/opt/lampp/htdocs/shoe_store` |

### Step 3 — Create the database (choose one)

**Option A — Import the ready-made dump (recommended):**
Log in to **phpMyAdmin** → create a database named `shoe_store_db`
(charset `utf8mb4`) → click **Import** → choose
`database/shoe_store.sql` → Go. The dump creates the database if missing.

**Option B — Run the setup script (command line):**
```bash
# Windows
C:\xampp\mysql\bin\mysql.exe -u root < database\setup.sql

# Linux / macOS
/opt/lampp/bin/mysql -u root < database/setup.sql
```
> Add `-p` and type the root password if your MySQL root user has one.

### Step 4 — Open the application

| Page        | URL                                                    |
|-------------|--------------------------------------------------------|
| Storefront  | `http://localhost/shoe_store/`                         |
| Admin panel | `http://localhost/shoe_store/admin/login.php`          |

> The storefront is served from the project root; URLs never show the `frontend/`
> folder (an `.htaccess` rewrite maps root pages to the `frontend/` directory).

### Step 5 — Configure payments (optional)
Edit `backend/payment_config.php` to add your own eSewa / Khalti **sandbox** credentials.
Callback URLs (eSewa success/failure, Khalti callback) are generated automatically
from the current site URL.

---

## 9. Default Accounts

| Role     | Username / Email   | Password      |
|----------|--------------------|---------------|
| **Admin**| `admin`            | `password`    |
| Staff    | `staff1`           | `password`    |
| Customer | `sita@example.com` | `customer123` |

- **Admin / Staff login:** `/admin/login.php` (username + password).
- **Customer login:** `/login.php` (email + password). For a **new** registration
  the 6-digit OTP is shown on the verification page (demo mode).

---

## 9A. Payment Gateway Testing (Sandbox)

> Both gateways are sandbox / UAT and **credit no real money**. Payment pages are
> protected by the provider's own login (Khalti wallet PIN, eSewa reCAPTCHA), so the
> gateway login must be done manually in a browser.
>
> **Checkout now requires a store login first** — log in at `/login.php` with
> `sita@example.com` / `customer123` before paying.

### eSewa (UAT)

1. Add products to the cart and go to **Checkout**.
2. Choose **eSewa** and submit the order.
3. You are redirected automatically to the eSewa UAT gateway
   (`rc-epay.esewa.com.np`, booked via the signed ePay V2 form).
4. On the eSewa login page enter:
   - **eSewa ID:** `9711111111` (also `9711111112` / `9711111113` / `9711111114`)
   - **Password:** `Test@123`
   - **OTP token:** `123456`
   - MPIN `1122` is for the phone app only — not needed on the web page.
5. After success, the site's `esewa_success.php` verifies the signed callback and
   confirms via the status API, then shows the order-success page.

> Older docs also list `9806800001`–`9806800005` / `Nepal@123`. On the current UAT
> build these are rejected, so prefer the `9711111111` / `Test@123` set above.

### Khalti (Sandbox / KPG-2)

1. Add products to the cart and go to **Checkout**.
2. Choose **Khalti** and submit the order.
3. You are redirected to Khalti's sandbox wallet page (`test-pay.khalti.com`).
4. On the wallet login enter:
   - **Khalti ID:** `9800000000` (or `9800000002` – `9800000005`)
   - **MPIN:** `1111`
   - **OTP:** `987654`
5. After success, the site's `khalti_callback.php` verifies the payment via the
   Khalti Lookup API and completes the order.

Test-wallet behaviour notes:
- `9800000000` → **successful** payment (has balance).
- `9800000001` → reserved for testing the **"insufficient balance"** error path.
- Entering a wrong MPIN locks that sandbox ID for a while — wait, then retry in a
  private window with the correct `1111` / `987654`, or simply use another ID.

### Where the credentials live

- `backend/payment_config.php` holds the eSewa merchant code (`EPAYTEST`), secret
  key and UAT URLs, and the Khalti sandbox secret/public keys. Callback URLs are
  generated automatically from the current site URL.

---

## 10. Project Structure

```
shoe_store/
├── frontend/          # Customer-facing storefront pages (shop, cart, checkout, auth, callbacks)
├── backend/           # Shared back-end / config code (db, db_config, auth_helper, payment_config)
├── admin/             # Admin panel (dashboard, products, orders, reports, users, ...)
├── assets/
│   ├── css/           # frontend.css, admin.css, style.css
│   ├── js/            # notify.js
│   └── img/           # Product / slider images
├── database/          # SQL schema and seed data (setup.sql)
├── docs/              # Project documentation (proposal, SRS, system design)
├── index.php          # Root entry point rendering the storefront homepage
└── .htaccess          # Security headers + storefront URL rewriting
```

## 10. Testing

| Test Case                          | Expected Result                                        | Status |
|------------------------------------|--------------------------------------------------------|--------|
| Register + OTP                      | OTP shown, account verified, auto login               | Pass   |
| Wrong OTP / expired OTP             | Rejected with clear message                           | Pass   |
| Login valid / invalid password      | Login success / error message                        | Pass   |
| Forgot password via OTP             | New password set, old OTP discarded                  | Pass   |
| Add / update / remove cart item     | Cart total updates correctly                         | Pass   |
| Place COD order with delivery slot  | Order saved with slot, stock reduced                 | Pass   |
| eSewa / Khalti handover             | Order pending until callback, then completed         | Pass   |
| Admin: approve review               | Shows on product page                                | Pass   |
| Admin: stock adjustment             | Stock updated and logged                            | Pass   |
| Set Font Awesome icon on category/brand | Icon shows in admin table and on homepage       | Pass   |
| Flash messages auto-dismiss         | Success/error/alert fades out after 4 seconds      | Pass   |
| Reports + CSV export                | Filtered tables exported                            | Pass   |

---

## 11. Conclusion and Future Enhancements

### Conclusion
The project successfully fulfills its objectives: an online shoe store with email
**OTP verification**, **password recovery**, **wishlist**, **cart**, **order
management with delivery slots**, **eSewa / Khalti / COD payments**, and a complete
**admin panel** with inventory logs and reports. The system is easy to run on XAMPP
and demonstrates the full MIS concepts of data collection, processing and reporting.

### Future Enhancements
- **Live email/SMS** for OTP delivery.
- **Fonepay** and other payment gateways.
- **Stock-out notifications** and low-stock alerts.
- **Coupons / discount codes** and shipping cost calculation.
- **Charts and graphs** on the admin dashboard.
- **Mobile app / responsive design improvements**.