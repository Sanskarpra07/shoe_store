# System Design — MegaFoot Online Shoe Store Management System

**BCA 5th Semester — E-Business Course**

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [ER Diagram (Entity-Relationship Diagram)](#2-er-diagram)
3. [DFD Level 0 — Context Diagram](#3-dfd-level-0)
4. [DFD Level 1 (Level 1 Data Flow Diagram)](#4-dfd-level-1)
5. [Use Case Diagram](#5-use-case-diagram)
6. [Class Diagram (Conceptual)](#6-class-diagram)
7. [Methodology Notes](#7-notes-on-rendering)

---

## 1. Introduction

This document presents the system design for the MegaFoot Online Shoe Store Management System. All diagrams are provided in **textual/ASCII notation** so they can be rendered as images later using any diagram tool (draw.io, Lucidchart, StarUML) or embedded directly as monospace text blocks in the final report.

The design follows a three-tier architecture:

- **Presentation Layer** (HTML + Bootstrap + JS in the browser)
- **Business Logic Layer** (PHP application code)
- **Data Layer** (MariaDB/MySQL `shoe_store_db`)

The database schema contains **10 tables**: `users`, `customers`, `categories`, `brands`, `products`, `orders`, `order_items`, `reviews`, `wishlists`, and `stock_log`. All are defined in `database/setup.sql`.

---

## 2. ER Diagram

### 2.1 Entity and Attribute Notation

Notation used:

- `PK` — Primary Key
- `FK` — Foreign Key
- `UK` — Unique Key
- `NN` — Not Null
- `(n)` — indicates a relationship multiplicity
- Entities are shown as attribute tables with relationship lines described after each.

### 2.2 Entity: users

| Attribute   | Type            | Key/Constraints        | Description                         |
| ----------- | --------------- | ---------------------- | ----------------------------------- |
| id          | INT UNSIGNED    | **PK**, AUTO_INCREMENT | User ID                             |
| username    | VARCHAR(50)     | **UK**, NN             | Login username                      |
| password_eg | VARCHAR(200)    | NN                     | bcrypt-hashed password              |
| role        | ENUM(...)       | NN, default 'staff'    | `admin` or `staff`                  |
| created_at  | TIMESTAMP       | default now            | Record creation time                |

### 2.3 Entity: customers

| Attribute       | Type          | Key/Constraints        | Description                  |
| --------------- | ------------- | ---------------------- | ---------------------------- |
| id              | INT UNSIGNED  | **PK**, AUTO_INCREMENT | Customer ID                  |
| full_name       | VARCHAR(100)  | NN                     | Customer full name           |
| email           | VARCHAR(150)  | **UK**, NN             | Login email                  |
| phone           | VARCHAR(20)   | —                      | Contact number               |
| password_eg     | VARCHAR(200)  | NN                     | bcrypt-hashed password       |
| otp_code        | VARCHAR(6)    | —                      | Registration OTP             |
| otp_expires_at  | DATETIME      | —                      | OTP expiry timestamp         |
| is_verified     | TINYINT(1)    | NN, default 0          | Email verified flag (0/1)    |
| address         | TEXT          | —                      | Default shipping address     |
| created_at      | TIMESTAMP     | default now            | Registration timestamp       |

### 2.4 Entity: categories

| Attribute   | Type          | Key/Constraints        | Description        |
| ----------- | ------------- | ---------------------- | ------------------ |
| id          | INT UNSIGNED  | **PK**, AUTO_INCREMENT | Category ID        |
| name        | VARCHAR(100)  | **UK**, NN             | Category name      |
| description | TEXT          | —                      | Category text      |
| created_at  | TIMESTAMP     | default now            | Creation timestamp |

### 2.5 Entity: brands

| Attribute   | Type          | Key/Constraints        | Description     |
| ----------- | ------------- | ---------------------- | --------------- |
| id          | INT UNSIGNED  | **PK**, AUTO_INCREMENT | Brand ID        |
| name        | VARCHAR(100)  | **UK**, NN             | Brand name      |
| description | TEXT          | —                      | Brand text      |
| created_at  | TIMESTAMP     | default now            | Creation time   |

### 2.6 Entity: products

| Attribute       | Type            | Key/Constraints                  | Description                 |
| --------------- | --------------- | -------------------------------- | --------------------------- |
| id              | INT UNSIGNED    | **PK**, AUTO_INCREMENT           | Product ID                  |
| product_name    | VARCHAR(255)    | NN                               | Product name                |
| description     | TEXT            | —                                | Product description         |
| price           | DECIMAL(10,2)   | NN                               | Regular price               |
| discount_price  | DECIMAL(10,2)   | —                                | Discounted price (nullable) |
| stock           | INT             | NN, default 0                    | Available quantity          |
| size            | VARCHAR(50)     | —                                | Size range (e.g. "8-12")    |
| color           | VARCHAR(50)     | —                                | Color                       |
| image           | VARCHAR(500)    | —                                | Image file path             |
| category_id     | INT UNSIGNED    | **FK** → categories.id           | Category (SET NULL on delete)|
| brand_id        | INT UNSIGNED    | **FK** → brands.id               | Brand (SET NULL on delete)  |
| created_at      | DATETIME        | default now                      | Creation timestamp          |
| updated_at      | DATETIME        | ON UPDATE CURRENT_TIMESTAMP      | Last modified timestamp     |

### 2.7 Entity: orders

| Attribute       | Type            | Key/Constraints                    | Description                    |
| --------------- | --------------- | ---------------------------------- | ------------------------------ |
| id              | INT UNSIGNED    | **PK**, AUTO_INCREMENT             | Order ID                       |
| customer_id     | INT UNSIGNED    | **FK** → customers.id              | Customer (order placement requires login; SET NULL on delete) |
| customer_name   | VARCHAR(100)    | NN                                 | Customer name at order time    |
| customer_email  | VARCHAR(150)    | NN                                 | Customer email at order time   |
| customer_phone  | VARCHAR(20)     | —                                  | Customer phone at order time   |
| customer_address| TEXT            | NN                                 | Shipping address               |
| total_amount    | DECIMAL(10,2)   | NN                                 | Grand total                    |
| payment_method  | ENUM(...)       | default 'cod'                      | `cod`, `esewa`, `khalti`       |
| payment_status  | ENUM(...)       | default 'pending'                  | `pending`, `completed`, `failed`|
| transaction_id  | VARCHAR(100)    | —                                  | Payment gateway reference      |
| status          | ENUM(...)       | default 'pending'                  | `pending`, `processing`, `shipped`, `delivered`, `cancelled` |
| created_at      | TIMESTAMP       | default now                        | Order timestamp                |

### 2.8 Entity: order_items

| Attribute  | Type           | Key/Constraints                   | Description             |
| ---------- | -------------- | --------------------------------- | ----------------------- |
| id         | INT UNSIGNED   | **PK**, AUTO_INCREMENT            | Line item ID            |
| order_id   | INT UNSIGNED   | **FK** → orders.id (CASCADE)      | Parent order            |
| product_id | INT UNSIGNED   | **FK** → products.id (CASCADE)    | Purchased product       |
| quantity   | INT            | NN, default 1                     | Quantity                |
| price      | DECIMAL(10,2)  | NN                                | Unit price at purchase  |

### 2.9 Entity: reviews

| Attribute   | Type          | Key/Constraints                  | Description              |
| ----------- | ------------- | -------------------------------- | ------------------------ |
| id          | INT UNSIGNED  | **PK**, AUTO_INCREMENT           | Review ID                |
| product_id  | INT UNSIGNED  | **FK** → products.id (CASCADE)   | Reviewed product         |
| customer_id | INT UNSIGNED  | **FK** → customers.id (CASCADE)  | Reviewing customer       |
| rating      | TINYINT       | NN, default 5                    | Star rating (1–5)        |
| comment     | TEXT          | —                                | Review text              |
| status      | ENUM(...)     | default 'pending'                | `pending`, `approved`, `rejected` |
| created_at  | TIMESTAMP     | default now                      | Submission timestamp     |

### 2.10 Entity: wishlists

| Attribute   | Type         | Key/Constraints                  | Description       |
| ----------- | ------------ | -------------------------------- | ----------------- |
| id          | INT UNSIGNED | **PK**, AUTO_INCREMENT           | Wishlist entry ID |
| customer_id | INT UNSIGNED | **FK** → customers.id (CASCADE)  | Customer          |
| product_id  | INT UNSIGNED | **FK** → products.id (CASCADE)   | Saved product     |
| created_at  | TIMESTAMP    | default now                      | Save timestamp    |

### 2.11 Entity: stock_log

| Attribute     | Type         | Key/Constraints                  | Description               |
| ------------- | ------------ | -------------------------------- | ------------------------- |
| id            | INT UNSIGNED | **PK**, AUTO_INCREMENT           | Log entry ID              |
| product_id    | INT UNSIGNED | **FK** → products.id (CASCADE)   | Affected product          |
| change_amount | INT          | NN                               | Signed change (+/−)       |
| reason        | TEXT         | —                                | Reason for change         |
| changed_by    | VARCHAR(50)  | NN                               | Admin/staff username      |
| created_at    | TIMESTAMP    | default now                      | Change timestamp          |

### 2.12 Relationship Summary

| # | Relationship                          | Multiplicity | Description                                                              |
| - | ------------------------------------- | ------------ | ------------------------------------------------------------------------ |
| R1| categories → products                 | 1 : N        | One category can contain many products; product category nullable (SET NULL on category delete) |
| R2| brands → products                    | 1 : N        | One brand can own many products; product brand nullable (SET NULL on brand delete) |
| R3| customers → orders                   | 1 : N        | One customer can place many orders; order placement requires login (customer_id always set) |
| R4| orders → order_items                 | 1 : N        | One order has many line items (CASCADE delete)                           |
| R5| products → order_items               | 1 : N        | One product can appear in many order line items (CASCADE delete)         |
| R6| customers → reviews                  | 1 : N        | One customer can write many reviews (CASCADE delete)                     |
| R7| products → reviews                  | 1 : N        | One product can receive many reviews (CASCADE delete)                    |
| R8| customers → wishlists               | 1 : N        | One customer can save many products to wishlist (CASCADE delete)         |
| R9| products → wishlists                | 1 : N        | One product can be saved by many customers (CASCADE delete)              |
| R10| products → stock_log               | 1 : N        | One product has many stock log entries (CASCADE delete)                  |

### 2.13 ER Diagram (Textual Crow's-foot style)

```
                          shoe_store_db
+---------------------+           +------------------------+
|       users         |           |       customers        |
+---------------------+           +------------------------+
| PK  id              |           | PK  id                 |
|     username   (UK) |           |     full_name          |
|     password_eg     |           |     email         (UK) |
|     role            |           |     phone              |
|     created_at      |           |     password_eg        |
+---------------------+           |     otp_code           |
          (no direct FK)          |     otp_expires_at     |
                                  |     is_verified        |
                                  |     address            |
                                  |     created_at         |
                                  +------------------------+
                                            |
                                            | 1
                                            |
                                            v N
                        +---------------------+-----------------------+
                        |                  orders                     |
                        +--------------------------------------------+
                        | PK  id                                      |
                        | FK  customer_id ─────────────► customers.id |
                        |     customer_name                           |
                        |     customer_email                          |
                        |     customer_phone                          |
                        |     customer_address                        |
                        |     total_amount                            |
                        |     payment_method                          |
                        |     payment_status                          |
                        |     transaction_id                          |
                        |     status                                  |
                        |     created_at                              |
                        +--------------------------------------------+
                                            |
                                            | 1
                                            |
                                            v N
                        +--------------------------------------------+
                        |                  order_items                |
                        +--------------------------------------------+
                        | PK  id                                      |
                        | FK  order_id   ───────────► orders.id      |
                        | FK  product_id ──────────► products.id     |
                        |     quantity                                |
                        |     price                                   |
                        +--------------------------------------------+

+------------------------+   N 1:...:N N   +------------------------+
|      categories        |                  |        brands         |
+------------------------+                  +------------------------+
| PK  id                 |                  | PK  id                |
|     name        (UK)   |                  |     name       (UK)   |
|     description        |                  |     description       |
|     created_at         |                  |     created_at        |
+------------------------+                  +------------------------+
        | 1                                       | 1
        |                                         |
        v N                                       v N
                        +--------------------------------------------+
                        |                 products                   |
                        +--------------------------------------------+
                        | PK  id                                      |
                        |     product_name                           |
                        |     description                            |
                        |     price                                  |
                        |     discount_price                         |
                        |     stock                                  |
                        |     size                                   |
                        |     color                                  |
                        |     image                                  |
                        | FK  category_id ────► categories.id        |
                        | FK  brand_id    ────► brands.id            |
                        |     created_at / updated_at                |
                        +--------------------------------------------+
                            |                 |                 |
                            | 1               | 1               | 1
                            |                 |                 |
                            v N               v N               v N
              +--------------+     +--------------+     +--------------+
              |  order_items |     |   reviews    |     |  wishlists   |
              |   (R5)       |     |   (R7)       |     |   (R9)       |
              +--------------+     +--------------+     +--------------+
                                    | 1                | 1
                                    |                  |
                                    v N                v N
                             +--------------+     +--------------+
                             |  customers   |     |  customers   |
                             |  (R6)        |     |  (R8)        |
                             +--------------+     +--------------+

              +---------------------------------------------+
              |                 stock_log                   |
              +---------------------------------------------+
              | PK  id                                       |
              | FK  product_id ──────────► products.id      |
              |     change_amount                            |
              |     reason                                   |
              |     changed_by                               |
              |     created_at                               |
              +---------------------------------------------+
```

---

## 3. DFD Level 0

### 3.1 Context Diagram (Textual)

Two external entities interact with the system: **Customer** and **Admin/Staff**.

```
                      +---------------------------------------------+
                      |                                             |
                      |     MEGAFOOT ONLINE SHOE STORE SYSTEM     |
                      |            (Level 0 / Context)            |
                      |                                             |
                      +---------------------------------------------+

 External Entity: CUSTOMER
 ---------------------------------
 ( - ) Browse products / view catalog
 ( - ) Search & filter products
 ( - ) Manage shopping cart
 ( - ) Place order (Checkout)
 ( - ) Make payment (COD / eSewa / Khalti)
 ( - ) Track order (Order ID + email)
 ( - ) Register / login / verify OTP
 ( - ) Manage wishlist
 ( - ) Submit product reviews
 ( - ) Download/print invoice

 External Entity: ADMIN / STAFF
 ---------------------------------
 ( + ) Login to admin panel (role check)
 ( + ) View dashboard KPIs
 ( + ) Manage products (CRUD)
 ( + ) Manage orders & update status
 ( + ) Manage customers (activate/deactivate)
 ( + ) Manage categories & brands (CRUD)
 ( + ) Manage stock (view log, adjust stock)
 ( + ) Moderate reviews (approve/reject/delete)
 ( + ) View reports & export CSV
 ( + ) Manage admin/staff users (admin only)


                     ASCII Context Diagram
                     ======================

        +------------------+          +------------------+
        |    CUSTOMER      |          |   ADMIN/STAFF    |
        +--------+---------+          +--------+---------+
                 |                               |
   requests/info |                               | requests/info
   +-------------+                               +-------------+
   |                                              |
   v                                              v
+----------------------------------------------------------+
|                       MEGAFOOT                           |
|                ONLINE SHOE STORE SYSTEM                   |
|                                                            |
|   1.0   Customer Storefront        2.0   Admin Console    |
|   (Browse, Cart, Checkout,         (Dashboard, CRUD,     |
|    Payment, Track, Reviews)         Orders, Reports)      |
|                                                            |
|   Data Stores                                                |
|   D1 customers   D2 products     D3 categories              |
|   D4 brands      D5 orders       D6 order_items             |
|   D7 reviews     D8 wishlists    D9 stock_log               |
|   D10 users                                                   |
+----------------------------------------------------------+
```

### 3.2 External Entities (Tabular)

| Entity        | Inputs to System                                  | Outputs from System                          |
| ------------- | ------------------------------------------------- | -------------------------------------------- |
| Customer      | Browsing/filter/search requests; cart updates; order details; payment credentials; reviews; OTP; login data | Product catalog; cart state; order confirmation; tracking status; invoice; wishlist state |
| Admin / Staff | Admin credentials; product CRUD forms; order status updates; stock adjustments; review moderation; report filters | Dashboard KPIs; management lists; updated inventory; exported CSV reports |

### 3.3 External Systems (Terminators)

| External System   | Exchange with System                             |
| ----------------- | ------------------------------------------------ |
| eSewa Gateway     | Signed ePay V2 form POST → gateway (HMAC-SHA256); success callback verifies signature + status API |
| Khalti Gateway    | Initiate payment API request; payment URL return  |
| Email Server      | OTP email, order confirmation email (via `mail()`)|

---

## 4. DFD Level 1

### 4.1 Level 1 Processes

| Process ID | Process Name          | Description                                                        |
| ---------- | --------------------- | ------------------------------------------------------------------ |
| 1.0        | User Management       | Registration, OTP verification, login/logout, profile management   |
| 2.0        | Product Management    | Catalog browsing, search, filter, product detail, admin CRUD, stock management |
| 3.0        | Order Processing      | Cart display, checkout, order creation, order tracking, admin status updates |
| 4.0        | Payment Processing    | COD completion, eSewa signed V2 form redirect, Khalti API call, callback verification, payment status handling |
| 5.0        | Review Management     | Review submission, rating aggregation, admin moderation            |
| 6.0        | Wishlist Management   | Add/remove wishlist items, display wishlist                         |
| 7.0        | Reporting             | Date-range sales reports, top products, revenue trends, CSV export  |

### 4.2 DFD Level 1 (Textual) — User Management (1.0)

```
      Customer                         D1 customers        D10 users
     ┌─────────┐      register / data      ┌────┐            ┌────┐
     │ Guest   │ ─────────────────────────►│1.0 │───────────►│    │
     │         │                          │User│ Email (OTP) │    │
     └─────────┘  login/verify            │Mgt │ -----------►(mail)
            │         ┌──────────────────►────┤
            │   otp /  │                       
            v   email  │                       
     session data      │                       
     (logged in) ◄─────┘
```

### 4.3 DFD Level 1 — Product Management (2.0)

```
 Customer                     D2 products   D3 categories  D4 brands
 ┌─────────┐   catalog request ┌──────────┐
 │Customer │──────────────────►│  2.0     │
 │(browse/ │  product display  │ Product  │──► D2, D3, D4  (reads)
 │ search) │◄──────────────────┤ Mgt.     │──► stock_log D9 (writes,
 └─────────┘                   └──────────┘      on adjust)
 Admin
 ┌─────────┐   add/edit/delete   ┌──────────┐
 │Admin    │──────────────────► │  2.0     │
 │         │   updated list ◄───│  (CRUD)  │──► D2 (writes)
 └─────────┘                    └──────────┘──► D9 stock_log (writes)
```

### 4.4 DFD Level 1 — Order Processing (3.0)

```
 Customer                        D5 orders       D6 order_items
 ┌─────────┐  add to cart      ┌──────────┐
 │Customer │─────────────────► │ 3.0      │
 │ (cart)  │  cart view/total  │ Order    │──► D2 products (stock read)
 └─────────┘◄──────────────────│ Processing│
 ┌─────────┐  checkout data    │          │          created order
 │Customer │─────────────────► │          │────────► D5  (INSERT)
 │(logged  │  confirm ◄─────── │          │────────► D6  (INSERT items)
 │ in)     │                   │          │
 └─────────┘                   └──────────┘
 Order tracking:
 Customer ── Order ID + email ──► 3.0 ──(read D5, D6)──► status display
 Admin ── status update ──► 3.0 ──(write D5)──► updated status
```

### 4.5 DFD Level 1 — Payment Processing (4.0)

```
 Customer                    D5 orders         D2 products (stock)
 ┌─────────┐  payment choice  ┌──────────┐
 │Customer │────────────────►│ 4.0      │
 └─────────┘                 │ Payment  │
                             │ Processing├───► eSewa gateway (signed V2 form POST)
                                 │       ├───► Khalti API (cURL)
                                 │       ├───► mark payment_status
                                 │       └───► reduce stock (COD)
                                 │
                             (result / redirect back to site)
```

### 4.6 DFD Level 1 — Review Management (5.0)

```
 Customer                    D7 reviews        D2 products (rating)
 ┌─────────┐  submit review   ┌──────────┐
 │Customer │────────────────►│ 5.0      │
 └─────────┐                 │ Review   │──► D7 (INSERT status=pending)
           │ approved list ◄─│ Mgt.     │──► D2 (avg rating reads)
 Admin     │                 └──────────┘
 ┌─────────┐  approve/reject ┌──────────┐
 │Admin    │────────────────►│ 5.0      │
 └─────────┘                 │          │──► D7 (UPDATE status)
                             └──────────┘
```

### 4.7 DFD Level 1 — Wishlist Management (6.0)

```
 Customer                        D8 wishlists
 ┌─────────┐  save/unsave       ┌──────────┐
 │Customer │──────────────────► │ 6.0      │
 └─────────┘                    │ Wishlist │──► D8 (INSERT/DELETE)
     │   wishlist page ◄────────│ Management│──► D2 products (reads)
     └─────────────────────────►└──────────┘
```

### 4.8 DFD Level 1 — Reporting (7.0)

```
 Admin                          D5 orders        D6 order_items
 ┌─────────┐  date-range filter ┌──────────┐
 │Admin    │──────────────────► │ 7.0      │
 └─────────┘                    │ Reporting│──► reads D5 (orders)
     │  reports / CSV ◄─────────│          │──► reads D6 (items)
     └─────────────────────────►│          │──► reads D2 (products)
                                └──────────┘
     │  CSV file (download)
     ▼
  CSV export (reports.csv)
```

---

## 5. Use Case Diagram

### 5.1 Actors

| Actor           | Description                                                          |
| --------------- | -------------------------------------------------------------------- |
| **Guest**       | Unauthenticated visitor who can browse, search, filter, and manage a cart; must log in / register before checkout |
| **Customer**    | Registered, verified user (inherits all Guest actions + account features) |
| **Staff**       | Store operations user with the `staff` role                          |
| **Admin**       | Full-privilege user with the `admin` role (inherits Staff actions + user management) |

### 5.2 Use Cases

#### Guest Use Cases

| ID      | Use Case Name        | Actors | Description                                            |
| ------- | -------------------- | ------ | ------------------------------------------------------ |
| UC-01   | Browse Catalog       | Guest, Customer | View homepage, shop page, product listings      |
| UC-02   | Search Products      | Guest, Customer | Full-text search on product name/description    |
| UC-03   | Filter Products      | Guest, Customer | Filter by category and brand                     |
| UC-04   | View Product Detail  | Guest, Customer | View full product info, related and up-sell items|
| UC-05   | Manage Cart          | Guest, Customer | Add/update/remove/clear cart items                |
| UC-06   | Authenticate at Checkout  | Guest         | Prompted to log in / register at checkout; `redirect_after_login` returns the guest to checkout |
| UC-07   | Track Order          | Guest, Customer | Track by Order ID + email                         |
| UC-08   | Register             | Guest         | Create a customer account                         |

#### Customer Use Cases

| ID      | Use Case Name             | Actors  | Description                                            |
| ------- | ------------------------- | ------- | ------------------------------------------------------ |
| UC-09   | Verify OTP (Email)        | Customer | Enter emailed 6-digit OTP to activate account    |
| UC-10   | Login / Logout            | Customer | Authenticate with email + password               |
| UC-11   | Manage Profile            | Customer | View/update personal information                  |
| UC-12   | View My Orders            | Customer | See order history + status                        |
| UC-13   | Download Invoice          | Customer | Print/download invoice for an order               |
| UC-14   | Manage Wishlist           | Customer | Add/remove products to wishlist                   |
| UC-15   | Submit Review             | Customer | Rate (1-5) + comment on a purchased product       |
| UC-16   | Checkout (Logged-in)      | Customer | Checkout with pre-filled address and linked order |

#### Staff Use Cases

| ID      | Use Case Name            | Actors | Description                                            |
| ------- | ------------------------ | ------ | ------------------------------------------------------ |
| UC-17   | Admin Login              | Staff, Admin | Authenticate into the admin panel               |
| UC-18   | View Dashboard           | Staff, Admin | View KPI cards, recent products & orders         |
| UC-19   | Manage Products          | Staff, Admin | Add / edit / delete / activate-deactivate products |
| UC-20   | Manage Orders            | Staff, Admin | View orders, update status, cancel                |
| UC-21   | Manage Customers         | Staff, Admin | View & activate/deactivate customer accounts      |
| UC-22   | Manage Categories        | Staff, Admin | CRUD product categories                           |
| UC-23   | Manage Brands            | Staff, Admin | CRUD product brands                               |
| UC-24   | Manage Stock Log         | Staff, Admin | View audit trail, log manual adjustments          |
| UC-25   | Moderate Reviews         | Staff, Admin | Approve / reject / delete reviews                 |
| UC-26   | View Reports & Export    | Staff, Admin | Filter by date range, view stats, export CSV     |

#### Admin-Only Use Cases

| ID      | Use Case Name            | Actors | Description                                            |
| ------- | ------------------------ | ------ | ------------------------------------------------------ |
| UC-27   | Manage Users             | Admin  | Create admin/staff accounts                          |
| UC-28   | View All KPIs            | Admin  | Dashboard includes Total Users KPI (staff hidden)    |

### 5.3 Use Case Relationships

| Relationship Type | Base Use Case     | Related Use Case            | Rule/Reason                                    |
| ----------------- | ----------------- | --------------------------- | ---------------------------------------------- |
| includes          | Checkout          | Manage Cart                 | Cart must be non-empty before checkout         |
| includes          | Checkout          | View Product Detail (→ Order Summary) | Order summary rendered from cart data |
| includes          | Place Order       | Validate Payment Method     | Server-side validation of COD/eSewa/Khalti     |
| includes          | Checkout          | Authenticate (required)     | Login/registration enforced at checkout (`checkout.php`, `process_order.php`) |
| includes          | Place Order       | Reduce Stock                | Stock decremented after successful order       |
| includes          | Place Order       | Send Order Email            | Order confirmation emailed after COD order     |
| includes          | Submit Review     | Authenticate                | Review requires customer login                 |
| includes          | Track Order       | Validate Order ID + Email   | Both fields must match order record            |
| include           | Manage Stock Log  | Authenticate (Admin)        | Requires admin session                         |
| extends           | Manage Orders     | Cancel Order                | Optional action, granted to staff/admin        |
| extends           | View Dashboard    | View User KPI               | Extra KPI shown only when role = admin         |
| extends           | Register          | Verify OTP                  | Registration is incomplete until OTP verified  |
| extends           | Manage Products   | Set Discount Price          | Optional attribute on product                  |
| includes          | Generate Reports  | Filter by Date Range        | Reports must be constrained by dates           |
| includes          | Generate Reports  | Export CSV                  | CSV export uses the same filtered result set   |

### 5.4 Use Case Diagram (Textual)

```
                          USE CASE DIAGRAM — MEGAFOOT
                          ============================

        +---------------- SYSTEM BOUNDARY ------------------+
        |                                                    |
        |   +----------------------+                         |
        |   |  BROWSE CATALOG      | (Guest, Customer)       |
        |   +----------------------+                         |
        |        ^ include          \ include                 |
        |   +----------------------+   +-------------------+ |
        |   |  SEARCH PRODUCTS     |   | FILTER PRODUCTS   | |
        |   +----------------------+   +-------------------+ |
        |                                                    |
        |   +----------------------+                         |
        |   |  MANAGE CART         |                         |
        |   +----------------------+                         |
        |        | include (must be non-empty)               |
        |        v                                            |
        |   +----------------------+                         |
        |   |  CHECKOUT            |---includes----------+   |
        |   +----------------------+                     |   |
        |        | includes                               |   |
        |        v                                        |   |
        |   +----------------------+   +---------------+ |   |
        |   | VALIDATE PAYMENT     |   | REDUCE STOCK  | |   |
        |   +----------------------+   +---------------+ |   |
        |        |                                       |   |
        |        v                                       |   |
        |   +----------------------+   +---------------+ |   |
        |   | eSEWA / KHALTI / COD |   | SEND EMAIL    | |   |
        |   +----------------------+   +---------------+ |   |
        |        +------------------------------+--------+    |
        |                                                   |
        |   +----------------------+   +------------------+  |
        |   | TRACK ORDER          |   | VIEW MY ORDERS   |  |
        |   +----------------------+   +------------------+  |
        |   +----------------------+   +------------------+  |
        |   | MANAGE WISHLIST      |   | SUBMIT REVIEW    |<-+-- includes AUTHENTICATE
        |   +----------------------+   +------------------+  |
        |   +----------------------+   +------------------+  |
        |   | REGISTER             |   | LOGIN / LOGOUT   |  |
        |   +----------------------+   +------------------+  |
        |   +----------------------+                        |
        |   | DOWNLOAD INVOICE     |                        |
        |   +----------------------+                        |
        +----------------------------------------------------+
                                   ^
        +----------------------------------+
        |   ACTORS:                       |
        |   GUEST ─────▶ UC-01..UC-08     |
        |   CUSTOMER ──▶ UC-01..UC-16     |  (extends GUEST)
        +----------------------------------+

        +----------------- ADMIN SYSTEM BOUNDARY ----------------+
        |                                                         |
        |   +----------------+  +-------------------+             |
        |   | ADMIN LOGIN    |  | VIEW DASHBOARD    | (extends full |
        |   +----------------+  +-------------------+  user KPI —admin)
        |   +----------------+  +-------------------+             |
        |   | MANAGE PRODUCTS|  | MANAGE ORDERS     |             |
        |   +----------------+  +-------------------+             |
        |   +----------------+  +-------------------+             |
        |   | MANAGE CUSTOMERS| | MANAGE CATEGORIES |             |
        |   +----------------+  +-------------------+             |
        |   +----------------+  +-------------------+             |
        |   | MANAGE BRANDS  |  | MANAGE STOCK LOG  |             |
        |   +----------------+  +-------------------+             |
        |   +----------------+  +-------------------+             |
        |   | MODERATE REVIEWS | | VIEW REPORTS + CSV |            |
        |   +----------------+  +-------------------+             |
        |   +----------------+                                    |
        |   | MANAGE USERS  |   (admin only)                      |
        |   +----------------+                                    |
        +----------------------------------------------------------+
                                   ^
        +----------------------------------+
        |   STAFF ────▶ UC-17..UC-26       |
        |   ADMIN ─────▶ UC-17..UC-28   (extends STAFF, admin only UC-27/28)
        +----------------------------------+
```

---

## 6. Class Diagram (Conceptual)

Although MegaFoot uses procedural PHP (not OOP), the following conceptual class diagram documents the domain model behind the system for design clarity.

```
+----------------+          +----------------+          +----------------+
|    Customer    |          |     Product    |          |     Cart       |
+----------------+          +----------------+          +----------------+
| id: int        |1        N| id: int        |1       1| items[]: array |
| full_name: str |----------| product_name:  |---------| count(): int   |
| email: str     |          |   str          |         | add(id, qty)   |
| phone: str     |          | price: dec     |         | update(id,qty) |
| address: str   |          | discount: dec  |         | remove(id)     |
| is_verified: 0/1|         | stock: int     |         | clear()        |
+----------------+          | brand: Brand   |         | total(): dec   |
       |                    | category: Cat  |         +----------------+
       |                    +----------------+              |
       | N                         | N                      |
       |                           |                        |
       |              +------------+------------+           |
       |              |                         |           |
       |              | 1                     1 |           |
+------+-----+  +-----+------+          +-------+-----+
|  Review    |  |  Order     |          |  Wishlist   |
+------------+  +------------+          +------------+
| id: int    |  | id: int    |     N    | customer_id |
| rating: 1-5|N | customer:  |----------| product_id  |
| comment:str|  |   Customer |          +------------+
| status: enum|  | total: dec |
+------------+  | payment:…  |
                | status: …  |
                +------------+
                        | 1
                        |
                        | N
                +-------v------+     +-----------+
                | OrderItem    |N     | StockLog  |
                +--------------+  +------------+
                | product: Prod|     product_id |
                | qty: int     |     change: int|
                | price: dec   |     reason: str|
                +--------------+     changed_by:str
                                     +------------+
```

---

## 7. Notes on Rendering These Diagrams

Each textual diagram in this document was designed so that it can be reproduced as a proper image with any of the following tools:

1. **draw.io (diagrams.net)** — Import the ER relationship table and redraw with Crow's-foot notation; use the DFD shape libraries for context and level-1 diagrams.
2. **Lucidchart** — Same approach; supported in its DFD/ERD stencils.
3. **StarUML / PlantUML** — Convert the ER attribute tables into PlantUML `entity` blocks.
4. **Microsoft Visio** — Use the "Data Flow Diagram" and "Database Model" stencils.
5. **Mermaid** (in VS Code / Markdown) — `erDiagram` and `flowchart` syntax can be written from the relationship summary in section 2.12 and the process flows in section 4.

### Mapping to Mermaid (example snippet)

```
erDiagram
    CUSTOMERS ||--o{ ORDERS : places
    ORDERS ||--|{ ORDER_ITEMS : contains
    PRODUCTS ||--o{ ORDER_ITEMS : "sold in"
    PRODUCTS ||--o{ REVIEWS : "has"
    CUSTOMERS ||--o{ REVIEWS : writes
    CATEGORIES ||--o{ PRODUCTS : classifies
    BRANDS ||--o{ PRODUCTS : manufactures
    CUSTOMERS ||--o{ WISHLISTS : saves
    PRODUCTS ||--o{ WISHLISTS : saved_by
    PRODUCTS ||--o{ STOCK_LOG : audited
```

---

*End of System Design Document*