# Software Requirements Specification (SRS)

## StepStyle — Online Shoe Store Management System

**BCA 5th Semester — E-Business Course**

---

| Item                        | Details                                                        |
| --------------------------- | -------------------------------------------------------------- |
| **Document Version**        | 1.0                                                            |
| **Date**                    | September 2026                                                |
| **Project**                 | StepStyle — Online Shoe Store Management System               |
| **Status**                  | Final                                                          |

---

## Table of Contents

1. [Purpose and Scope](#1-purpose-and-scope)
2. [Overall Description](#2-overall-description)
3. [Functional Requirements](#3-functional-requirements)
4. [Non-Functional Requirements](#4-non-functional-requirements)
5. [Database Requirements](#5-database-requirements)
6. [External Interface Requirements](#6-external-interface-requirements)
7. [Assumptions and Dependencies](#7-assumptions-and-dependencies)

---

## 1. Purpose and Scope

### 1.1 Purpose

This Software Requirements Specification (SRS) document defines the complete functional and non-functional requirements for the **StepStyle — Online Shoe Store Management System**. It serves as the basis for development, testing, and acceptance of the system. The document describes what the system will do, how it will behave, and the constraints within which it must operate.

### 1.2 Scope

The StepStyle system is a web-based e-commerce platform for the buying and selling of footwear. It provides two primary interfaces:

1. **Customer-Facing Storefront**: Product browsing, search and filtering, shopping cart, checkout with multiple payment options, order tracking, account management, wishlist, and product reviews.

2. **Admin-Facing Management Panel**: Dashboard with KPIs, product inventory management, order processing, customer management, category and brand administration, stock audit logging, review moderation, business reporting, and user account management.

The system is a self-contained web application developed using PHP and MySQL, deployed locally via XAMPP for demonstration purposes. It integrates with eSewa and Khalti payment gateways in sandbox mode.

### 1.3 Definitions, Acronyms, and Abbreviations

| Term          | Definition                                                            |
| ------------- | --------------------------------------------------------------------- |
| SRS           | Software Requirements Specification                                  |
| PHP           | Hypertext Preprocessor (server-side scripting language)               |
| MySQL/MariaDB | Open-source relational database management system                     |
| COD           | Cash on Delivery                                                      |
| CSRF          | Cross-Site Request Forgery                                            |
| XSS           | Cross-Site Scripting                                                  |
| KPI           | Key Performance Indicator                                             |
| CRUD          | Create, Read, Update, Delete                                          |
| OTP           | One-Time Password                                                     |
| DBMS          | Database Management System                                            |
| MVC           | Model-View-Controller (architectural pattern)                         |
| WAMP/XAMPP    | Cross-platform web server solution stack packages                     |

---

## 2. Overall Description

### 2.1 Product Perspective

StepStyle is a new, standalone web application built from scratch for the BCA 5th Semester E-Business course. It does not depend on any existing system and is designed to be deployed on a local XAMPP server for demonstration and evaluation purposes. The system follows a three-tier architecture:

- **Presentation Layer**: HTML5, CSS3, JavaScript, and Bootstrap 5.3 rendered in the web browser
- **Business Logic Layer**: PHP scripts executing on the Apache server
- **Data Layer**: MariaDB/MySQL relational database

### 2.2 User Classes and Characteristics

The system serves four distinct user classes:

#### 2.2.1 Guest (Unregistered Visitor)

| Attribute       | Description                                                       |
| --------------- | ----------------------------------------------------------------- |
| Primary Role    | Browse products, search, filter, add to cart                      |
| Auth Required   | No (login required before checkout)                              |
| Capabilities    | View homepage, shop page, product details; add items to cart; log in / register to place an order |

#### 2.2.2 Registered Customer

| Attribute       | Description                                                       |
| --------------- | ----------------------------------------------------------------- |
| Primary Role    | Full shopping experience with saved account data                  |
| Auth Required   | Yes (email + password, verified via OTP)                         |
| Capabilities    | Full guest capabilities plus: checkout and order placement, login/logout, profile management, order history, wishlist, product reviews, address pre-fill at checkout |
| Restrictions    | Checkout (`checkout.php`) and order placement (`process_order.php`) require a logged-in session |

#### 2.2.3 Staff Member

| Attribute       | Description                                                       |
| --------------- | ----------------------------------------------------------------- |
| Primary Role    | Daily store operations                                             |
| Auth Required   | Yes (admin panel credentials, role = `staff`)                     |
| Capabilities    | Product management, order management, customer management, category/brand management, stock log, review moderation |
| Restrictions    | Cannot access user management (admin/staff account creation)      |

#### 2.2.4 Administrator

| Attribute       | Description                                                       |
| --------------- | ----------------------------------------------------------------- |
| Primary Role    | Full control over the entire system                               |
| Auth Required   | Yes (admin panel credentials, role = `admin`)                    |
| Capabilities    | All staff capabilities plus: user management (create admin/staff accounts), full dashboard visibility |
| Restrictions    | None                                                             |

### 2.3 Operating Environment

| Component          | Requirement                                        |
| ------------------ | -------------------------------------------------- |
| Operating System   | Windows 7/8/10/11 (or any OS supporting XAMPP)     |
| Web Server         | Apache 2.4.x (bundled with XAMPP 2.4.53)           |
| Scripting Language | PHP 7.4.29                                         |
| Database           | MariaDB 10.4.24 or MySQL 5.7+                      |
| Browser            | Chrome 100+, Firefox 100+, Edge 100+               |
| Client Hardware    | Any modern computer with a web browser             |
| Network            | Localhost for development; optionally LAN for demo |

### 2.4 Design and Implementation Constraints

1. **Language of Implementation**: Server-side logic must be written in PHP; database queries must use the MySQLi extension.
2. **Database Schema**: Must match the provided `database/setup.sql` schema exactly (10 tables).
3. **Security**: All forms incorporating user input must include CSRF token verification; all queries against the database involving user-supplied values must use prepared statements.
4. **Responsive Design**: The customer-facing frontend must be responsive and functional on mobile, tablet, and desktop viewports.
5. **Local Deployment**: The system must run on a local XAMPP installation without cloud hosting.

### 2.5 Assumptions and Dependencies

- The end user has XAMPP installed and properly configured.
- PHP mail() function availability for email delivery (local test environment limitation — mail may not actually transmit).
- eSewa and Khalti sandbox/test credentials are available (or fallback simulation is used).
- Product images are stored in the `assets/img/` directory.
- Prices displayed in USD as demo currency.

---

## 3. Functional Requirements

### 3.1 FR-01: User Registration & Login

**Priority**: High

**Description**: The system shall allow visitors to register a customer account and subsequently log in.

**Functional requirements**:
- FR-01.1 The system shall provide a registration form capturing full name, email, phone, address, and password.
- FR-01.2 The system shall validate that the email is unique and well-formed.
- FR-01.3 The system shall hash passwords using `password_hash()` with bcrypt before storage.
- FR-01.4 Upon registration, the system shall generate a 6-digit OTP and store it with an expiry timestamp in the `customers` table (`otp_code`, `otp_expires_at`).
- FR-01.5 The system shall send the OTP to the customer's email address via the `mail()` function.
- FR-01.6 The customer account shall remain in `is_verified = 0` state until a valid OTP is submitted.
- FR-01.7 Upon OTP verification, the system shall set `is_verified = 1` and activate the account.
- FR-01.8 The login form shall accept email and password; validation checks password against the stored hash with `password_verify()`.
- FR-01.9 Only verified customers (`is_verified = 1`) shall be allowed to log in.
- FR-01.10 Successful login shall create session variables `customer_id` and `customer_name`.

### 3.2 FR-02: Product Catalog with Search & Filters

**Priority**: High

**Description**: The system shall display the product catalog with search, filtering, and browsing capabilities.

**Functional requirements**:
- FR-02.1 The homepage shall display featured products (products with `discount_price` set), new arrivals, categories, and brands.
- FR-02.2 The shop page shall list all products in a responsive card grid showing image, name, brand, category, price (with discount if applicable), stock status, and a discount percentage badge.
- FR-02.3 The shop page shall support keyword search on product name and description using `LIKE` matching with prepared statements.
- FR-02.4 The shop page shall support filtering by category via query parameter `category`.
- FR-02.5 The shop page shall support filtering by brand via query parameter `brand`.
- FR-02.6 Filters shall be combinable (search + category + brand simultaneously).
- FR-02.7 The system shall display the count of matching results.
- FR-02.8 When no results match, the system shall display a "No products found" message with a link to view all products.
- FR-02.9 The system shall provide a "Clear Filters" button to reset search and filters.
- FR-02.10 Product listings shall display an "Out of Stock" badge for products with zero stock and prevent adding them to the cart.

### 3.3 FR-03: Shopping Cart Management

**Priority**: High

**Description**: The system shall provide a session-based shopping cart allowing customers to manage their selected products.

**Functional requirements**:
- FR-03.1 The cart shall be stored in the PHP session as an associative array of `product_id => quantity`.
- FR-03.2 The "Add to Cart" form on the product detail page shall accept a product ID and quantity (min 1, max = current stock).
- FR-03.3 The system shall reject add-to-cart attempts for out-of-stock products.
- FR-03.4 The navbar shall display the total item count as a badge on the cart icon.
- FR-03.5 The cart page shall display each item with image, name, brand, color, size, unit price, quantity input, and line total.
- FR-03.6 The cart page shall allow updating quantities via a form field (min 1, max = stock) using the `update_cart` POST action.
- FR-03.7 The cart page shall allow removing individual items via a `remove` GET parameter with an `unset()` operation.
- FR-03.8 The cart page shall allow clearing the entire cart via a `clear=1` GET parameter.
- FR-03.9 The system shall calculate and display subtotal, free shipping, and grand total.
- FR-03.10 When the cart is empty, the system shall display an empty-cart message and prevent navigation to checkout.
- FR-03.11 Stock availability shall be validated during checkout; insufficient stock shall result in an error message and redirect back to checkout.

### 3.4 FR-04: Checkout with Shipping Details

**Priority**: High

**Description**: The system shall collect shipping and payment information to place an order.

**Functional requirements**:
- FR-04.1 The checkout page shall only be accessible when the cart is non-empty; otherwise redirect to the cart page.
- FR-04.2 The system shall display a summary of cart items with quantities and line totals on the checkout page.
- FR-04.3 The checkout form shall capture full name (required), email (required), phone, and shipping address (required).
- FR-04.4 For logged-in customers, the form shall pre-fill name, email, phone, and address from their profile.
- FR-04.5 The checkout page shall **require an authenticated customer session**; an unauthenticated visitor with items in the cart is redirected to the login page and, after login, returned to checkout (`redirect_after_login`).
- FR-04.6 The checkout form shall include a hidden CSRF token field.
- FR-04.7 Server-side validation shall be performed in `process_order.php`; errors are stored in the session and displayed on the checkout page with previously entered data preserved.
- FR-04.8 An order total of zero shall be rejected.

### 3.5 FR-05: Payment Processing

**Priority**: High

**Description**: The system shall support three payment methods: Cash on Delivery (COD), eSewa, and Khalti.

**Functional requirements**:
- FR-05.1 **COD**: Creating an order with `payment_method = 'cod'` shall immediately set `payment_status = 'completed'`, reduce product stock, clear the cart, and redirect to the order success page with a confirmation message.
- FR-05.2 **eSewa (ePay V2)**: Creating an order with `payment_method = 'esewa'` shall set `payment_status = 'pending'`, store the order ID in session, generate a unique `transaction_uuid` (`ord-{id}-{nonce}`) and an HMAC-SHA256 `signature` over `total_amount,transaction_uuid,product_code`, and render an auto-submitting hidden form posting those signed fields plus `success_url` / `failure_url` to the eSewa UAT form URL (`ESEWA_URL`).
- FR-05.3 **Khalti**: Creating an order with `payment_method = 'khalti'` shall set `payment_status = 'pending'`, call the Khalti e-payment initiate API via cURL with the test secret key, and redirect the customer to the returned `payment_url`.
- FR-05.4 **Khalti**: The callback (`khalti_callback.php`) verifies the transaction with the Khalti Lookup API; only a `Completed` lookups with a matching amount completes the order. There is no success simulation in the current implementation — unreachable gateway or failed verification marks the order failed and redirects back to the cart/checkout with an error message.
- FR-05.5 The payment method shall be validated server-side; only `cod`, `esewa`, or `khalti` are accepted (anything else defaults to `cod`).
- FR-05.6 eSewa integration shall respect the `ESEWA_URL`, `ESEWA_MERCHANT_CODE`, `ESEWA_SECRET_KEY`, `ESEWA_SUCCESS_URL`, and `ESEWA_FAILURE_URL` configuration values in `backend/payment_config.php`.
- FR-05.7 The eSewa success callback (`esewa_success.php`) shall decode the base64 `data` response, verify the HMAC-SHA256 signature, cross-check the paid amount against the order total, confirm the transaction with the eSewa status API (`ESEWA_STATUS_URL`) returning `COMPLETE`, and only then set `payment_status = 'completed'`, store `transaction_id`, and reduce stock atomically. Callback handling is idempotent (a replayed callback on an already-completed order is ignored).

### 3.6 FR-06: Order Tracking by ID + Email

**Priority**: Medium

**Description**: The system shall allow customers to track order status without logging in.

**Functional requirements**:
- FR-06.1 A `track_order.php` page shall be publicly accessible from the navbar ("Track Order").
- FR-06.2 The tracking form shall accept an Order ID and the customer's email address.
- FR-06.3 The system shall display the order status badge (pending / processing / shipped / delivered / cancelled), payment status, total amount, order date, and item list.
- FR-06.4 If the Order ID and email combination does not match, an appropriate error shall be shown.

### 3.7 FR-07: Product Reviews and Ratings

**Priority**: Medium

**Description**: The system shall allow verified customers to submit product reviews and ratings, subject to admin moderation.

**Functional requirements**:
- FR-07.1 Only logged-in customers shall be able to submit a review; logged-out users see a "Login to share your review" message.
- FR-07.2 A customer may submit only one review per product (checked against existing reviews).
- FR-07.3 The review form shall capture a star rating (1-5, radio inputs) and a comment (minimum 5 characters).
- FR-07.4 New reviews shall be stored with `status = 'pending'`.
- FR-07.5 The product page shall display the average rating, review count, and a star-distribution histogram computed only from `approved` reviews.
- FR-07.6 Approved reviews shall be displayed with reviewer name and date.
- FR-07.7 The admin panel shall list pending reviews with approve, reject, and delete actions.

### 3.8 FR-08: Wishlist Management

**Priority**: Medium

**Description**: The system shall allow logged-in customers to save products to a wishlist for later purchase.

**Functional requirements**:
- FR-08.1 The product detail page shall display a heart button (add/remove from wishlist) only for logged-in customers.
- FR-08.2 A filled heart indicates the product is already in the wishlist; clicking it removes the product; an outline heart adds it.
- FR-08.3 The wishlist page shall list all saved products with image, name, brand, price, and a link to view the product.
- FR-08.4 The customer shall be able to remove items from the wishlist.
- FR-08.5 Wishlist items shall be stored in the `wishlists` table with foreign key references to `customers` and `products`.

### 3.9 FR-09: Admin Dashboard with KPIs

**Priority**: High

**Description**: The admin dashboard shall present a summary of store performance through KPI cards and recent activity tables.

**Functional requirements**:
- FR-09.1 The dashboard shall display KPI cards for: Total Users (admin-only), Total Products, Categories, Low Stock Items, Brands, Total Orders, Revenue (Paid), Pending Orders, and Registered Customers.
- FR-09.2 The "Low Stock Items" card shall count products with stock less than 10 units.
- FR-09.3 The "Revenue (Paid)" card shall sum `total_amount` of orders with `payment_status = 'completed'`.
- FR-09.4 The dashboard shall list the 5 most recently added products with brand, category, price, and stock (with low-stock badge).
- FR-09.5 The dashboard shall list the 5 most recent orders with customer name, total, status badge, and date.
- FR-09.6 KPI cards shall link to their respective management pages.
- FR-09.7 The dashboard shall display the logged-in admin's username with a welcome message.
- FR-09.8 The dashboard shall be protected by session authentication; unauthenticated access redirects to `admin/login.php`.

### 3.10 FR-10: Admin Product CRUD

**Priority**: High

**Description**: The system shall allow authorized staff/admin to fully manage the product catalog.

**Functional requirements**:
- FR-10.1 The product list page shall display all products with name, brand, category, price, stock, and active status.
- FR-10.2 The admin shall be able to add a new product with: product name, description, price, discount price (optional), stock, size, color, image path, assigned brand (dropdown), assigned category (dropdown), and active status.
- FR-10.3 The admin shall be able to edit all product attributes.
- FR-10.4 The admin shall be able to deactivate/reactivate a product without deleting it (`is_active`).
- FR-10.5 The admin shall be able to permanently delete a product (with the resulting cascade to order_items, reviews, stock_log per schema).
- FR-10.6 Discount price, when set, shall be displayed as the selling price with the original price shown as "old price".

### 3.11 FR-11: Admin Order Management

**Priority**: High

**Description**: The admin shall be able to view, filter, and update the status of customer orders.

**Functional requirements**:
- FR-11.1 The orders page shall list all orders showing order ID, customer name, total, payment method/status, order status, and date.
- FR-11.2 The admin shall be able to update the order status through the progression: `pending → processing → shipped → delivered`.
- FR-11.3 The admin shall be able to cancel an order (status = `cancelled`).
- FR-11.4 The system shall display color-coded status badges for each order status.
- FR-11.5 The admin shall be able to view order details including items, quantities, and prices.

### 3.12 FR-12: Admin Customer Management

**Priority**: Medium

**Description**: The admin shall be able to view and manage registered customers.

**Functional requirements**:
- FR-12.1 The customers page shall list all registered customers with name, email, phone, verification and active status.
- FR-12.2 The admin shall be able to activate or deactivate a customer account.
- FR-12.3 Deactivated customers shall not be able to log in.
- FR-12.4 The system shall display a customer's total order count where available.

### 3.13 FR-13: Admin Reports with Date-Range Filtering and CSV Export

**Priority**: Medium

**Description**: The admin shall be able to generate business reports filtered by date range and export them to CSV.

**Functional requirements**:
- FR-13.1 The reports page shall present a date-range filter (start date, end date) and optional status filter.
- FR-13.2 The system shall display sales summaries including total orders, total revenue, and paid revenue within the range.
- FR-13.3 The system shall display top-selling products based on quantity sold within the range.
- FR-13.4 The system shall display revenue trends (optionally per-day sales data).
- FR-13.5 The admin shall be able to export the filtered report data as a CSV file that opens in Excel.
- FR-13.6 CSV export shall include appropriate HTTP headers (`Content-Type: text/csv`, `Content-Disposition: attachment`).

### 3.14 FR-14: Admin Stock Log

**Priority**: Medium

**Description**: The system shall provide an audit trail of all stock changes.

**Functional requirements**:
- FR-14.1 The `stock_log` table shall record every stock change with: product ID, change amount (positive or negative), reason, changed-by username, and timestamp.
- FR-14.2 The stock log page shall list all entries with product name (joined), change amount, reason, admin, and date.
- FR-14.3 The admin shall be able to log a manual stock adjustment (add or subtract stock) with a reason note.
- FR-14.4 Stock values on the `products` table shall be updated to match manual adjustments, and the log entry shall be created atomically in the same request.

### 3.15 FR-15: Email Notifications

**Priority**: Medium

**Description**: The system shall send email notifications for key customer events.

**Functional requirements**:
- FR-15.1 **Order Confirmation**: After a COD order is placed, the system shall send an order confirmation email including order ID, customer name, and total amount (`send_order_email()` in `email_helper.php`).
- FR-15.2 **Welcome/OTP Email**: Upon registration, the system shall send the OTP code to the customer's email address.
- FR-15.3 Email sending shall use PHP's `mail()` function (works locally only if a mail server / SMTP relaying is configured).

### 3.16 FR-16: Printable Invoice Generation

**Priority**: Medium

**Description**: The system shall allow customers and staff to view and print an invoice for an order.

**Functional requirements**:
- FR-16.1 A printable invoice view shall be available for each order (from order success page, order tracking, and admin order details).
- FR-16.2 The invoice shall include order ID, date, customer details, shipping address, itemized list (product, qty, price, line total), subtotal, shipping (free), grand total, payment method, and order status.
- FR-16.3 The invoice view shall be print-friendly (dedicated printable layout).

---

## 4. Non-Functional Requirements

### 4.1 Security Requirements

| Requirement | Description                                                                                    |
| ----------- | ---------------------------------------------------------------------------------------------- |
| NFR-01      | **CSRF Protection**: Every state-changing form (add to cart, review submission, checkout, admin forms) shall include a CSRF token generated via `csrf_field()` and validated by `csrf_check()`. Requests without a valid token shall be rejected. |
| NFR-02      | **SQL Injection Prevention**: All database queries incorporating user-supplied values shall use MySQLi prepared statements with bound parameters. No user input shall be concatenated into SQL strings. |
| NFR-03      | **XSS Prevention**: All user-generated and database content rendered in HTML must be escaped with `htmlspecialchars()` or `htmlentities()`. |
| NFR-04      | **Password Hashing**: Customer and admin/staff passwords must be stored using `password_hash()` (bcrypt). Verification uses `password_verify()`. Never store plain-text passwords. |
| NFR-05      | **Session Security**: Admin panel responses set `Cache-Control: no-store` to prevent sensitive page caching. |
| NFR-06      | **Role-Based Access Control**: Only users whose session contains `username` (and appropriate role) can access the admin panel. Staff cannot access user management. |
| NFR-07      | **Input Validation**: All inputs are validated and sanitized server-side (email format, integer casting, length checks). |

### 4.2 Performance Requirements

| Requirement | Description                                                            |
| ----------- | ---------------------------------------------------------------------- |
| NFR-08      | Product listing pages shall load within 3 seconds on a local server.   |
| NFR-09      | Homepage queries (featured, new arrivals, categories, brands) shall use indexed columns and LIMIT clauses. |
| NFR-10      | The cart and checkout pages shall make at most 1 query for cart items + 1 for order summary. |
| NFR-11      | The admin dashboard shall complete all KPI counts in a single page load. |

### 4.3 Usability Requirements

| Requirement | Description                                                            |
| ----------- | ---------------------------------------------------------------------- |
| NFR-12      | The storefront shall be fully responsive across desktop, tablet, and mobile viewports (Bootstrap 5.3 grid). |
| NFR-13      | The site shall have a consistent navigation bar and footer across all customer pages. |
| NFR-14      | The admin panel shall use a consistent sidebar navigation with clearly labeled sections. |
| NFR-15      | All user-facing messages (validation errors, success messages) shall be displayed clearly with appropriate alert styling. |

### 4.4 Portability Requirements

| Requirement | Description                                                            |
| ----------- | ---------------------------------------------------------------------- |
| NFR-16      | The system shall run on any machine with XAMPP 2.4.x installed (Apache 2.4, PHP 7.4, MariaDB 10.4). |
| NFR-17      | `setup.bat` shall automate database creation (from `database/setup.sql`) via the MariaDB command-line client. |
| NFR-18      | `install.php` shall provide a web-based alternative setup wizard for database initialization. |
| NFR-19      | The system shall be viewable in modern browsers (Chrome 100+, Firefox 100+, Edge, Opera). |
| NFR-20      | No external dependencies beyond CDN-resolved Bootstrap assets (local fallback via included CSS allowed). |

### 4.5 Maintainability Requirements

| Requirement | Description                                                            |
| ----------- | ---------------------------------------------------------------------- |
| NFR-21      | Shared functionality (navbar, footer, CSRF helpers, auth checks) shall be factored into reusable helper files (`auth_helper.php`). |
| NFR-22      | Database credentials and payment configuration shall be centralized in `backend/db.php` and `backend/payment_config.php`. |
| NFR-23      | Code shall follow consistent PHP naming conventions aligned with the existing codebase. |

---

## 5. Database Requirements

The system uses a single database, `shoe_store_db`, containing 10 tables. All tables use `INT UNSIGNED AUTO_INCREMENT` primary keys and appropriate foreign key relationships with `ON DELETE CASCADE` or `ON DELETE SET NULL` semantics.

### 5.1 Table: users

Stores admin and staff login credentials for the admin panel.

| Column        | Type              | Constraints           | Description            |
| ------------- | ----------------- | --------------------- | ---------------------- |
| id            | INT UNSIGNED      | PK, AUTO_INCREMENT    | User ID                |
| username      | VARCHAR(50)       | NOT NULL, UNIQUE      | Login username         |
| password_eg   | VARCHAR(200)      | NOT NULL              | bcrypt-hashed password |
| role          | ENUM('admin','staff') | NOT NULL, DEFAULT 'staff' | User role         |
| created_at    | TIMESTAMP         | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

### 5.2 Table: customers

Stores registered customers with OTP verification fields.

| Column          | Type          | Constraints            | Description                    |
| --------------- | ------------- | ---------------------- | ------------------------------ |
| id              | INT UNSIGNED  | PK, AUTO_INCREMENT     | Customer ID                    |
| full_name       | VARCHAR(100)  | NOT NULL               | Full name                      |
| email           | VARCHAR(150)  | NOT NULL, UNIQUE       | Email (login identifier)       |
| phone           | VARCHAR(20)   | NULL                   | Contact number                 |
| password_eg     | VARCHAR(200)  | NOT NULL               | bcrypt-hashed password         |
| otp_code        | VARCHAR(6)    | NULL                   | Registration OTP               |
| otp_expires_at  | DATETIME      | NULL                   | OTP expiry timestamp           |
| is_verified     | TINYINT(1)    | DEFAULT 0              | 1 = email verified             |
| address         | TEXT          | NULL                   | Default shipping address       |
| created_at      | TIMESTAMP     | DEFAULT CURRENT_TIMESTAMP | Registration timestamp     |

### 5.3 Table: categories

Stores product categories.

| Column       | Type          | Constraints           | Description            |
| ------------ | ------------- | --------------------- | ---------------------- |
| id           | INT UNSIGNED  | PK, AUTO_INCREMENT    | Category ID            |
| name         | VARCHAR(100)  | NOT NULL, UNIQUE      | Category name          |
| description  | TEXT          | NULL                  | Category description   |
| created_at   | TIMESTAMP     | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

### 5.4 Table: brands

Stores product brands.

| Column       | Type          | Constraints           | Description            |
| ------------ | ------------- | --------------------- | ---------------------- |
| id           | INT UNSIGNED  | PK, AUTO_INCREMENT    | Brand ID               |
| name         | VARCHAR(100)  | NOT NULL, UNIQUE      | Brand name             |
| description  | TEXT          | NULL                  | Brand description      |
| created_at   | TIMESTAMP     | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

### 5.5 Table: products

Stores the shoe catalog with pricing, stock, and classification.

| Column         | Type            | Constraints                              | Description                 |
| -------------- | --------------- | ---------------------------------------- | --------------------------- |
| id             | INT UNSIGNED    | PK, AUTO_INCREMENT                       | Product ID                  |
| product_name   | VARCHAR(255)    | NOT NULL                                 | Product name                |
| description    | TEXT            | NULL                                     | Product description         |
| price          | DECIMAL(10,2)   | NOT NULL                                 | Regular price               |
| discount_price | DECIMAL(10,2)   | NULL                                     | Discounted price (optional) |
| stock          | INT             | NOT NULL, DEFAULT 0                      | Available stock             |
| size           | VARCHAR(50)     | NULL                                     | Available size range        |
| color          | VARCHAR(50)     | NULL                                     | Color                       |
| image          | VARCHAR(500)    | NULL                                     | Image path                  |
| category_id    | INT UNSIGNED    | FK → categories.id ON DELETE SET NULL    | Category reference          |
| brand_id       | INT UNSIGNED    | FK → brands.id ON DELETE SET NULL        | Brand reference             |
| created_at     | DATETIME        | DEFAULT CURRENT_TIMESTAMP                | Creation timestamp          |
| updated_at     | DATETIME        | DEFAULT CURRENT_TIMESTAMP ON UPDATE      | Last update                 |

### 5.6 Table: orders

Stores customer orders with payment and fulfillment status.

| Column            | Type            | Constraints                          | Description                  |
| ----------------- | --------------- | ------------------------------------ | ---------------------------- |
| id                | INT UNSIGNED    | PK, AUTO_INCREMENT                   | Order ID                     |
| customer_id       | INT UNSIGNED    | FK → customers.id ON DELETE SET NULL | Customer (order placement requires login) |
| customer_name     | VARCHAR(100)    | NOT NULL                             | Customer name at order time  |
| customer_email    | VARCHAR(150)    | NOT NULL                             | Customer email at order time |
| customer_phone    | VARCHAR(20)     | NULL                                 | Customer phone               |
| customer_address  | TEXT            | NOT NULL                             | Shipping address             |
| total_amount      | DECIMAL(10,2)   | NOT NULL                             | Order grand total            |
| payment_method    | ENUM('cod','esewa','khalti') | DEFAULT 'cod'      | Payment method               |
| payment_status    | ENUM('pending','completed','failed') | DEFAULT 'pending' | Payment status           |
| transaction_id    | VARCHAR(100)    | NULL                                 | Payment transaction ID       |
| status            | ENUM('pending','processing','shipped','delivered','cancelled') | DEFAULT 'pending' | Fulfillment status |
| created_at        | TIMESTAMP       | DEFAULT CURRENT_TIMESTAMP            | Order timestamp              |

### 5.7 Table: order_items

Stores the line items belonging to each order.

| Column     | Type          | Constraints                         | Description          |
| ---------- | ------------- | ----------------------------------- | -------------------- |
| id         | INT UNSIGNED  | PK, AUTO_INCREMENT                  | Line item ID         |
| order_id   | INT UNSIGNED  | FK → orders.id ON DELETE CASCADE    | Parent order         |
| product_id | INT UNSIGNED  | FK → products.id ON DELETE CASCADE  | Product bought       |
| quantity   | INT           | NOT NULL, DEFAULT 1                 | Quantity purchased   |
| price      | DECIMAL(10,2) | NOT NULL                            | Unit price at purchase |

### 5.8 Table: reviews

Stores customer product reviews with moderation status.

| Column      | Type          | Constraints                             | Description              |
| ----------- | ------------- | --------------------------------------- | ------------------------ |
| id          | INT UNSIGNED  | PK, AUTO_INCREMENT                      | Review ID                |
| product_id  | INT UNSIGNED  | FK → products.id ON DELETE CASCADE      | Reviewed product         |
| customer_id | INT UNSIGNED  | FK → customers.id ON DELETE CASCADE     | Reviewing customer       |
| rating      | TINYINT       | NOT NULL, DEFAULT 5                     | Star rating (1-5)        |
| comment     | TEXT          | NULL                                    | Review text              |
| status      | ENUM('pending','approved','rejected') | DEFAULT 'pending' | Moderation status |
| created_at  | TIMESTAMP     | DEFAULT CURRENT_TIMESTAMP               | Submission timestamp     |

### 5.9 Table: wishlists

Stores customer wishlist entries.

| Column      | Type         | Constraints                            | Description          |
| ----------- | ------------ | -------------------------------------- | -------------------- |
| id          | INT UNSIGNED | PK, AUTO_INCREMENT                     | Wishlist entry ID    |
| customer_id | INT UNSIGNED | FK → customers.id ON DELETE CASCADE    | Customer             |
| product_id  | INT UNSIGNED | FK → products.id ON DELETE CASCADE     | Saved product        |
| created_at  | TIMESTAMP    | DEFAULT CURRENT_TIMESTAMP              | Save timestamp       |

### 5.10 Table: stock_log

Stores the inventory audit trail.

| Column        | Type         | Constraints                           | Description                |
| ------------- | ------------ | ------------------------------------- | -------------------------- |
| id            | INT UNSIGNED | PK, AUTO_INCREMENT                    | Log entry ID               |
| product_id    | INT UNSIGNED | FK → products.id ON DELETE CASCADE    | Affected product           |
| change_amount | INT          | NOT NULL                              | Signed stock change (+/-)  |
| reason        | TEXT         | NULL                                  | Reason for the change      |
| changed_by    | VARCHAR(50)  | NOT NULL                              | Admin username responsible  |
| created_at    | TIMESTAMP    | DEFAULT CURRENT_TIMESTAMP             | Change timestamp           |

---

## 6. External Interface Requirements

### 6.1 eSewa Payment Gateway API (Sandbox, ePay V2)

| Item               | Description                                                                                   |
| ------------------ | --------------------------------------------------------------------------------------------- |
| Interface Type     | HTTP POST (HTML form submission) + HMAC-SHA256 signature                                      |
| Gateway URL        | `ESEWA_URL` in `backend/payment_config.php` — `https://rc-epay.esewa.com.np/api/epay/main/v2/form` (classic `uat.esewa.com.np/epay/main` is retired) |
| Parameters Sent    | `amount`, `tax_amount`, `total_amount`, `transaction_uuid`, `product_code`, `product_service_charge`, `product_delivery_charge`, `success_url`, `failure_url`, `signed_field_names`, `signature` |
| Signature          | `base64(hmac_sha256(secret, "total_amount=X,transaction_uuid=Y,product_code=Z"))` |
| Secret Key         | `ESEWA_SECRET_KEY` from `backend/payment_config.php` (sandbox `8gBm/:&EnhH.1/q`)           |
| Success/Failure    | On return `esewa_success.php` receives base64 `data`, verifies the signature and calls `ESEWA_STATUS_URL` (status API) before completing the order; `esewa_failure.php` marks the order failed |
| Mode               | Sandbox (no real money movement); test wallet login `9711111111` / `Test@123`, OTP `123456` |

### 6.2 Khalti Payment Gateway API (Sandbox, KPG-2)

| Item               | Description                                                                                   |
| ------------------ | --------------------------------------------------------------------------------------------- |
| Interface Type     | REST API via cURL (JSON)                                                                      |
| Initiate Endpoint  | `https://dev.khalti.com/api/v2/epayment/initiate/`                                            |
| Lookup Endpoint    | `https://dev.khalti.com/api/v2/epayment/lookup/` (used by `khalti_callback.php` for final verification) |
| Authentication     | `Authorization: Key <secret_key>` header                                                      |
| Payload            | `return_url`, `website_url`, `amount` (paisa), `purchase_order_id`, `purchase_order_name`, `customer_info` (name, email, phone), `product_details` |
| Response (initiate)| JSON containing `payment_url` to which the customer is redirected                             |
| Callback           | `khalti_callback.php?order_id=N&pidx=...` — verifies via Lookup API (`status = Completed`, amount match) before completing the order & stock |
| Test Wallet        | Sandbox wallet login `9800000000` – `9800000005`, MPIN `1111`, OTP `987654`                  |
| Mode               | Sandbox (test secret key). No success simulation — failed verification marks the order failed |

### 6.3 Email (SMTP / mail())

| Item            | Description                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------------- |
| Interface       | PHP `mail()` function (native) or SMTP                                                             |
| Use Cases       | Order confirmation email (`send_order_email`), OTP verification email at registration              |
| Local Constraint| On a local XAMPP machine without a mail server, emails may not be delivered; OTP display fallbacks are used for testing |

### 6.4 Web Browser Interface

| Item       | Description                                                                       |
| ---------- | --------------------------------------------------------------------------------- |
| Frontend   | Served over HTTP (localhost) by Apache; renders standard HTML5/CSS/JS             |
| Assets     | Bootstrap 5.3.3 and Bootstrap Icons served via CDN (jsdelivr); custom CSS local  |

---

## 7. Assumptions and Dependencies

### 7.1 Assumptions

1. The system will be evaluated in a local XAMPP environment; no cloud hosting is involved.
2. The customer database will remain small-to-medium for the academic demo; no sharding or replication is needed.
3. Email delivery may be unavailable in the local environment; for demo purposes, OTP codes are also surfaced to the operator during testing.
4. Product prices are displayed in USD for demonstration, matching the seeded sample data.
5. Payment gateway integrations use sandbox/test credentials; no real financial transactions occur.

### 7.2 Dependencies

1. XAMPP 2.4.x (bundled Apache, PHP 7.4, MariaDB 10.4).
2. PHP `mysqli` extension enabled.
3. PHP `openssl`/`curl` extensions enabled (for Khalti API call).
4. Internet connection for CDN-hosted Bootstrap assets (includes/local fallback for offline mode).
5. eSewa and Khalti sandbox documentation for accurate integration parameters.
6. Seeded sample data (from `database/setup.sql`) for immediate demonstration.

---

*End of Software Requirements Specification*