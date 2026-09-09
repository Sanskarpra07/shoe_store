# MegaFoot — Online Shoe Store Management System

---

## Project Proposal / Synopsis

**BCA 5th Semester — E-Business Course**

---

| Field              | Details                                  |
| ------------------ | ---------------------------------------- |
| **Project Title**  | MegaFoot — Online Shoe Store Management System |
| **Course**         | BCA 5th Semester, E-Business             |
| **Academic Year**  | 2025 / 2026                             |
| **Technology**     | PHP 7.4, MySQL/MariaDB, Bootstrap 5.3   |
| **Platform**       | XAMPP (Local Development & Demo)        |

---

## Acknowledgement

We would like to express our sincere gratitude to our course instructor for the E-Business course for providing us with the opportunity to work on this project. Their guidance, encouragement, and valuable feedback throughout the development process were instrumental in shaping this project.

We also extend our thanks to the faculty and staff of the BCA department for their continuous support and for providing the necessary infrastructure and resources. Finally, we thank our family and friends for their unwavering support and motivation during the entire course of this project.

---

## Abstract

MegaFoot is a web-based online shoe store management system designed and developed as part of the BCA 5th Semester E-Business course. The system provides a complete e-commerce solution for buying and selling footwear from premium brands such as Nike, Adidas, Puma, Reebok, and New Balance. Customers can browse a rich product catalog, filter by brand and category, manage a shopping cart, place orders with multiple payment options (Cash on Delivery, eSewa, and Khalti), track their orders, maintain wishlists, and submit product reviews and ratings. On the administration side, the system offers a comprehensive dashboard with key performance indicators (KPIs), product CRUD management, order processing workflows, customer management, category and brand administration, stock audit logging, review moderation, and date-range-filtered reporting with CSV export capabilities. The system is built using PHP 7.4 with MySQL/MariaDB as the database backend, Bootstrap 5.3 for responsive front-end design, and integrates with Nepal's popular digital payment gateways — eSewa and Khalti — in sandbox mode. Security measures include Cross-Site Request Forgery (CSRF) token protection, prepared SQL statements to prevent injection attacks, and password hashing using PHP's `password_hash()` function. The system follows a three-tier architecture with clear separation between the presentation layer, business logic layer, and data access layer, ensuring maintainability and scalability.

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Objectives](#2-objectives)
3. [Scope](#3-scope)
4. [Literature Review](#4-literature-review)
5. [Methodology](#5-methodology)
6. [System Architecture](#6-system-architecture)
7. [Technology Stack](#7-technology-stack)
8. [Project Timeline](#8-project-timeline)
9. [Expected Outcomes](#9-expected-outcomes)
10. [References](#10-references)

---

## 1. Introduction

### 1.1 E-Business Context

Electronic business (e-business) refers to the use of digital technologies and the Internet to execute major business processes in the enterprise. E-business includes activities for the internal management of the firm and for coordination with suppliers and other business partners. It encompasses e-commerce (buying and selling over the Internet) as well as internal business processes such as production, inventory management, product development, risk management, knowledge management, and human resources.

The global e-commerce market has experienced exponential growth over the past decade. According to Statista, global e-commerce sales surpassed $5.8 trillion in 2023 and are projected to exceed $8 trillion by 2027. This growth has been driven by increasing internet penetration, mobile device adoption, improved digital payment infrastructure, and changing consumer preferences toward online shopping.

### 1.2 Online Retail Growth in Nepal

Nepal's digital economy is emerging rapidly. With internet penetration reaching approximately 60% of the population and mobile subscriptions exceeding 40 million, the foundation for e-commerce is strengthening. Platforms like Daraz Nepal, Sastodeal, and Foodmandu have demonstrated that Nepali consumers are increasingly comfortable with online purchasing. The introduction of digital wallets such as eSewa and Khalti has further simplified online transactions, reducing the dependency on traditional banking infrastructure.

However, niche online retail — particularly in the footwear segment — remains underserved. Most shoe purchases in Nepal still happen through physical retail stores in areas like New Road, Durbar Marg, and civil malls. These physical stores face challenges including limited inventory visibility, lack of online presence, manual record-keeping, and inability to reach customers outside their immediate geographic area.

### 1.3 Problem Statement

Physical shoe stores in Nepal face the following key challenges:

1. **No Online Presence**: Traditional shoe retailers lack a digital storefront, limiting their reach to foot traffic only.
2. **Manual Inventory Management**: Stock tracking is done manually using paper records or basic spreadsheets, leading to errors, stockouts, and overstock situations.
3. **Limited Customer Reach**: Without an online platform, stores cannot serve customers in other cities or districts.
4. **No Digital Payments**: Most physical stores only accept cash, missing out on customers who prefer digital payment methods.
5. **No Order Tracking**: Customers have no way to check the status of their orders remotely.
6. **No Customer Reviews**: There is no mechanism for customers to share feedback or rate products, reducing trust for new buyers.
7. **No Analytics**: Store owners lack data-driven insights into sales trends, popular products, and revenue patterns.

MegaFoot aims to address all these challenges by providing a comprehensive, web-based shoe store management system that serves both customers and administrators.

---

## 2. Objectives

The primary objectives of the MegaFoot project are:

1. **Develop a user-friendly online shoe catalog** that allows customers to browse, search, and filter products by brand (Nike, Adidas, Puma, Reebok, New Balance) and category (Running, Casual, Sports, Formal, Boots).

2. **Implement a complete shopping cart system** with the ability to add products, update quantities, remove items, validate stock availability, and calculate order totals with discount pricing.

3. **Provide a multi-payment checkout process** supporting Cash on Delivery (COD), eSewa (sandbox), and Khalti (sandbox) payment methods, with proper order creation and payment status tracking.

4. **Build a comprehensive admin dashboard** displaying key performance indicators including total products, orders, revenue, registered customers, pending orders, and low-stock alerts.

5. **Implement an admin order management workflow** with status progression (pending → processing → shipped → delivered / cancelled) and customer notification via email.

6. **Create a stock audit system** using a `stock_log` table that records every inventory change with reason, timestamp, and the admin responsible, ensuring full accountability.

7. **Develop customer engagement features** including user registration with OTP email verification, wishlist management, product reviews with star ratings (pending → approved moderation workflow), and order tracking by order ID and email.

8. **Ensure application security** through CSRF token protection on all forms, prepared SQL statements to prevent injection attacks, password hashing with `password_hash()`, and role-based access control for the admin panel.

---

## 3. Scope

### 3.1 In-Scope Features

| Feature Area             | Description                                                                 |
| ------------------------ | --------------------------------------------------------------------------- |
| Product Catalog          | Full product listing with images, descriptions, pricing, discount prices, brand and category information |
| Search & Filter          | Text-based product search, filter by category and brand, result count display |
| Product Detail Page      | Detailed product view with breadcrumbs, specifications, add-to-cart, related products, up-sell recommendations |
| Shopping Cart            | Session-based cart with add/update/remove, quantity validation, line totals, order summary |
| Checkout                 | Shipping information form, payment method selection, form validation, login/registration required, pre-fill for logged-in users |
| Payment Processing       | COD (immediate), eSewa UAT signed V2 form redirect, Khalti sandbox API call with callback verification |
| Order Tracking           | Track order status by Order ID + email, public access without login            |
| Customer Registration    | Account creation with email, password, name, phone, address                   |
| OTP Verification         | Email-based OTP code for account activation (pending → verified workflow)     |
| Customer Login/Logout    | Session-based authentication for customers                                    |
| My Account               | Profile view and update                                                       |
| My Orders                | Order history with status, items, and invoice view                            |
| Wishlist                 | Save/unsave products to wishlist, view wishlist page                          |
| Product Reviews          | Star rating (1-5) with comment, submit → pending → approved moderation        |
| Admin Dashboard          | KPI cards (products, orders, revenue, customers, low stock, pending orders), recent products, recent orders |
| Admin Product CRUD       | Add/edit/delete products with name, description, price, discount, stock, brand, category, image |
| Admin Order Management   | View all orders, update status, search/filter                                 |
| Admin Customer Management| View registered customers, activate/deactivate accounts                      |
| Admin Category CRUD      | Add/edit/delete product categories                                            |
| Admin Brand CRUD         | Add/edit/delete product brands                                                |
| Admin Stock Log          | View inventory change history, log manual stock adjustments with reasons      |
| Admin Reviews            | View pending reviews, approve/reject/delete                                   |
| Admin Reports            | Date-range filtering, sales summary, top products, revenue trends, CSV export |
| Admin User Management    | Create admin/staff accounts with role assignment                              |
| Printable Invoice        | Generate printable invoice for completed orders                               |
| Email Notifications      | Order confirmation email, welcome email for new registrations                 |

### 3.2 Out-of-Scope Features

| Feature                    | Reason for Exclusion                                      |
| -------------------------- | --------------------------------------------------------- |
| Native Mobile App (iOS/Android) | Requires separate React Native / Flutter development; beyond project scope |
| AI-Powered Recommendations | Requires machine learning infrastructure and training data |
| International Shipping     | Focus is on domestic (Nepal) market only                   |
| Multi-Currency Support     | Prices are in USD for demo; local currency conversion not in scope |
| Real Payment Gateway       | Uses sandbox/test mode for eSewa and Khalti only           |
| SMS Notifications          | Would require third-party SMS gateway integration          |
| Loyalty/Rewards Program    | Adds significant business logic complexity                 |
| Multi-Language Support     | English-only interface for this project                    |

---

## 4. Literature Review

### 4.1 E-Commerce Trends

Modern e-commerce platforms have evolved significantly from simple product listing websites. Today's consumers expect personalized experiences, multiple payment options, real-time order tracking, and mobile-responsive interfaces. Key trends include:

- **Mobile-First Design**: Over 60% of e-commerce traffic now comes from mobile devices, making responsive design a necessity rather than a luxury (Statista, 2024).
- **Digital Wallets**: In Nepal, eSewa alone has over 14 million users, and Khalti serves over 10 million, making digital wallet integration essential for any e-commerce platform targeting the Nepali market.
- **Social Commerce**: Integration with social media platforms for product discovery and sharing.
- **User-Generated Content**: Product reviews and ratings significantly influence purchasing decisions, with 93% of consumers reading reviews before making a purchase (Spiegel Research Center, 2023).

### 4.2 Existing Solutions

#### Daraz Nepal
Daraz is the largest e-commerce platform in Nepal, offering a wide range of products including footwear. However, it operates as a marketplace connecting multiple sellers, which can lead to inconsistent product quality, variable shipping times, and complex seller management. MegaFoot differentiates itself by being a single-brand store with direct inventory control.

#### Sastodeal
Sastodeal is another popular Nepali e-commerce platform. While it offers competitive pricing, its shoe collection is spread across multiple sellers, making quality control and consistent customer experience challenging. MegaFoot focuses exclusively on footwear, allowing for a more curated and specialized experience.

#### Nike.com / Adidas.com
International brand websites offer excellent product presentation and user experience but do not serve the Nepali market directly. They lack integration with local payment methods (eSewa, Khalti) and do not support local shipping infrastructure. MegaFoot bridges this gap by combining international brand products with local payment and delivery capabilities.

### 4.3 What MegaFoot Improves

| Aspect               | Existing Platforms                | MegaFoot                          |
| -------------------- | --------------------------------- | ---------------------------------- |
| Product Focus        | General marketplace               | Specialized footwear store         |
| Payment Integration  | Limited local payment options     | COD + eSewa + Khalti               |
| Admin Dashboard      | Complex, enterprise-level         | Simple, intuitive for small business|
| Stock Management     | Basic or none for small sellers   | Full audit log with change tracking|
| Review Moderation    | Automated or absent               | Manual approval workflow           |
| Up-selling           | Algorithm-driven (complex)        | Price-based category alternatives  |
| Deployment           | Cloud-hosted                      | Local XAMPP for demo + easy migration|

---

## 5. Methodology

### 5.1 Technology Justification

**PHP 7.4**: PHP remains the most widely used server-side language for web development, powering approximately 77% of websites with known server-side languages (W3Techs, 2024). Its extensive documentation, large community, and built-in support for MySQL make it ideal for academic projects. PHP 7.4 offers significant performance improvements over earlier versions, including the preloading feature and typed properties.

**MySQL/MariaDB 10.4**: MySQL is the world's most popular open-source relational database management system. MariaDB, a MySQL fork, provides enhanced performance and additional features while maintaining full compatibility. The combination of PHP and MySQL (often referred to as the LAMP stack) is proven, well-documented, and supported by extensive tooling.

**Bootstrap 5.3**: Bootstrap is the most widely used CSS framework, providing a comprehensive set of responsive, mobile-first components. Version 5.3 introduces improved utility classes, CSS custom properties, and enhanced dark mode support. Using Bootstrap ensures consistent design across devices with minimal custom CSS.

### 5.2 Development Environment

**XAMPP**: The project uses XAMPP (Apache + MariaDB + PHP + Perl) as the local development server. XAMPP provides a complete, self-contained development environment that is easy to install and configure. The `setup.bat` script automates database initialization, and `install.php` provides a web-based setup wizard.

### 5.3 Development Approach

The project follows an **Iterative Development Model** with the following phases:

1. **Requirements Gathering**: Analyzing the needs of a shoe store business, identifying user roles and their requirements.
2. **System Design**: Creating the database schema, designing the user interface mockups, and defining system architecture.
3. **Core Development**: Building the product catalog, shopping cart, and checkout system.
4. **Admin Panel Development**: Creating the administrative interface for managing all aspects of the store.
5. **Payment Integration**: Integrating eSewa and Khalti payment gateways in sandbox mode.
6. **Testing & Refinement**: Comprehensive testing of all features, fixing bugs, and improving user experience.
7. **Documentation**: Preparing project documentation including SRS, system design, testing reports, and user manuals.

Each iteration builds upon the previous one, allowing for continuous feedback and improvement.

---

## 6. System Architecture

MegaFoot follows a **Three-Tier Architecture** pattern, separating the application into three distinct layers:

### 6.1 Presentation Layer (Client-Side)

The presentation layer is responsible for the user interface and user experience. It consists of:

- **HTML5**: Semantic markup for all pages (index, shop, product detail, cart, checkout, admin panel)
- **Bootstrap 5.3 CSS Framework**: Responsive grid system, components (cards, tables, forms, modals, badges), and utility classes
- **Bootstrap Icons**: Icon library for visual elements (cart, user, search, heart, etc.)
- **Custom CSS** (`assets/css/frontend.css`): Brand-specific styling including color scheme, product cards, and typography
- **JavaScript**: Client-side interactivity including payment method selection highlighting, star rating input, form validation, and Bootstrap components (dropdowns, modals)

### 6.2 Business Logic Layer (Server-Side)

The business logic layer is implemented in PHP and handles all application processing:

- **Authentication** (`auth_helper.php`): Customer login state management, admin session verification, role-based access control, navbar and footer rendering helpers
- **Cart Management** (`cart.php`, `add_to_cart.php`): Session-based shopping cart with add/update/remove operations, stock validation
- **Order Processing** (`process_order.php`): Order creation, stock reduction, payment routing (COD/eSewa/Khalti), email notification
- **Payment Integration** (`backend/payment_config.php`): eSewa form submission, Khalti API calls via cURL
- **Admin Operations** (`admin/*.php`): CRUD operations for products, categories, brands, orders, customers, reviews, stock logs, reports
- **Email Notifications** (`email_helper.php`): Order confirmation emails, welcome emails for new customers

### 6.3 Data Layer

The data layer manages all database interactions using MySQL/MariaDB:

- **Database**: `shoe_store_db` on MariaDB 10.4
- **Connection**: MySQLi extension with prepared statements for all user-input queries
- **Schema**: 10 tables (users, customers, categories, brands, products, orders, order_items, reviews, wishlists, stock_log) with proper foreign key constraints
- **Data Integrity**: Foreign key references with ON DELETE CASCADE or ON DELETE SET NULL behaviors

### 6.4 Architecture Diagram (Textual)

```
+-------------------+     HTTP/HTTPS      +-------------------+
|                   | <=================> |                   |
|   Web Browser     |                     |   Apache Server   |
|   (Chrome/Firefox)|                     |   (via XAMPP)     |
|                   |                     |                   |
+-------------------+                     +-------------------+
                                                  |
                                           PHP 7.4 Runtime
                                                  |
                                    +-------------+-------------+
                                    |             |             |
                              +-----+-----+ +----+----+ +------+------+
                              | Presentation| | Business | |   Data      |
                              | Layer       | | Logic    | |   Layer     |
                              | (HTML/CSS/  | | (PHP     | | (MySQL/     |
                              |  Bootstrap) | |  Files)  | |  MariaDB)   |
                              +-------------+ +----+----+ +------+------+
                                                       |           |
                                                  +----+-----------+----+
                                                  |                    |
                                          +-------+-------+  +--------+--------+
                                          |  eSewa API    |  |  Khalti API     |
                                          |  (Sandbox)    |  |  (Sandbox)      |
                                          +---------------+  +-----------------+
```

---

## 7. Technology Stack

### 7.1 Frontend Technologies

| Technology         | Version   | Purpose                                            |
| ------------------ | --------- | -------------------------------------------------- |
| HTML5              | 5.2       | Semantic page structure and content markup          |
| CSS3               | 3         | Styling, animations, responsive design              |
| Bootstrap          | 5.3.3     | Responsive grid, UI components, utility classes     |
| Bootstrap Icons    | 1.11.3    | Icon library for UI elements                        |
| JavaScript         | ES6+      | Client-side interactivity and dynamic behaviors     |

### 7.2 Backend Technologies

| Technology         | Version   | Purpose                                            |
| ------------------ | --------- | -------------------------------------------------- |
| PHP                | 7.4.29    | Server-side scripting and business logic            |
| MySQLi Extension   | -         | Database connectivity with prepared statement support|
| cURL Extension     | -         | API calls to Khalti payment gateway                 |

### 7.3 Database

| Technology         | Version   | Purpose                                            |
| ------------------ | --------- | -------------------------------------------------- |
| MariaDB            | 10.4.24   | Relational database management system               |
| phpMyAdmin         | 5.2+      | Database administration and management              |

### 7.4 Payment Gateways

| Gateway            | Mode      | Integration Method                                  |
| ------------------ | --------- | --------------------------------------------------- |
| Cash on Delivery   | Live      | Direct order creation, payment_status = completed    |
| eSewa              | Sandbox   | Signed ePay V2 form POST (HMAC-SHA256) to UAT gateway, callback verified via status API |
| Khalti             | Sandbox   | KPG-2 REST initiate + Lookup verification via cURL (v2 API)            |

### 7.5 Development Tools

| Tool               | Version   | Purpose                                            |
| ------------------ | --------- | -------------------------------------------------- |
| XAMPP              | 2.4.53    | Local development server (Apache + MariaDB + PHP)   |
| VS Code            | Latest    | Code editor                                         |
| Git                | Latest    | Version control                                     |
| setup.bat          | -         | Automated database setup script                     |
| install.php        | -         | Web-based installation wizard                       |

---

## 8. Project Timeline

The project was developed over a 10-week period following an iterative approach:

| Phase                              | Week 1 | Week 2 | Week 3 | Week 4 | Week 5 | Week 6 | Week 7 | Week 8 | Week 9 | Week 10 |
| ---------------------------------- | :----: | :----: | :----: | :----: | :----: | :----: | :----: | :----: | :----: | :-----: |
| **1. Requirements Analysis**       | ████░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░  |
| **2. System Design & DB Schema**   | ░░████ | ████░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░  |
| **3. Frontend Design (Bootstrap)** | ░░░░░░ | ░░████ | ████░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░  |
| **4. Product Catalog & Shop**      | ░░░░░░ | ░░░░░░ | ░░████ | ████░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░  |
| **5. Cart & Checkout System**      | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░████ | ████░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░  |
| **6. Payment Integration**         | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░████ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░  |
| **7. Admin Panel Development**     | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ██████ | ████░░ | ░░░░░░ | ░░░░░░ | ░░░░░░  |
| **8. Customer Features & Reviews** | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░████ | ████░░ | ░░░░░░ | ░░░░░░  |
| **9. Testing & Bug Fixes**         | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ████░░ | ░░░░░░  |
| **10. Documentation & Deployment** | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░░░░░ | ░░████ | ████░░  |

### Phase Descriptions

1. **Requirements Analysis (Week 1)**: Studying e-business concepts, identifying requirements for an online shoe store, defining user roles and functional requirements.
2. **System Design (Weeks 1-2)**: Creating the database schema with 10 tables, designing the system architecture, defining entity relationships.
3. **Frontend Design (Weeks 2-3)**: Building the responsive layout using Bootstrap 5.3, creating the brand identity with custom CSS, implementing the navbar, footer, and reusable components.
4. **Product Catalog & Shop (Weeks 3-4)**: Implementing the product listing page with search, filter by brand/category, product detail page with up-sell and related products.
5. **Cart & Checkout (Weeks 4-5)**: Building the session-based shopping cart, checkout form with payment selection, order summary.
6. **Payment Integration (Week 5)**: Implementing COD processing, eSewa sandbox redirect, Khalti API integration with sandbox fallback.
7. **Admin Panel (Weeks 6-7)**: Building the admin dashboard with KPIs, product CRUD, order management, category/brand management, customer management, stock log.
8. **Customer Features (Weeks 7-8)**: Registration with OTP verification, login/logout, my account, my orders, wishlist, product reviews with star ratings.
9. **Testing & Bug Fixes (Week 9)**: Comprehensive testing of all features, security testing (CSRF, SQL injection, XSS), cross-browser testing.
10. **Documentation & Deployment (Weeks 9-10)**: Writing project proposal, SRS, system design, testing report, user manuals, preparing the project for submission.

---

## 9. Expected Outcomes

Upon completion, the MegaFoot project delivers:

1. **A Fully Functional E-Commerce Website**: A responsive, visually appealing online shoe store with product catalog, shopping cart, checkout, and payment processing capabilities.

2. **An Administrative Dashboard**: A comprehensive back-end management interface allowing store administrators to manage products, orders, customers, categories, brands, stock, reviews, and view business reports.

3. **Payment Gateway Integration**: Working integration with eSewa and Khalti payment gateways in sandbox mode, along with Cash on Delivery support for offline payments.

4. **Customer Engagement Features**: User registration with OTP verification, wishlist management, product reviews with moderation, and order tracking.

5. **Security Implementation**: CSRF protection, prepared SQL statements, password hashing, and role-based access control ensuring the application is resistant to common web vulnerabilities.

6. **Stock Audit System**: A complete inventory audit trail tracking every stock change with reasons and responsible personnel.

7. **Business Intelligence**: Date-range filtered reports with sales summaries, top product analysis, and CSV export capability for further analysis.

8. **Complete Project Documentation**: Comprehensive documentation including project proposal, software requirements specification, system design document, testing report, user manual, and admin manual.

9. **Easy Deployment**: Automated setup via `setup.bat` and `install.php` for quick local deployment and demonstration.

---

## 10. References

1. **PHP Manual**. (2024). *PHP: Hypertext Preprocessor — Documentation*. https://www.php.net/docs.php

2. **MySQL 8.0 Reference Manual**. (2024). *Oracle Corporation*. https://dev.mysql.com/doc/refman/8.0/en/

3. **Bootstrap Documentation**. (2024). *Bootstrap 5.3 — Get Started with Bootstrap*. https://getbootstrap.com/docs/5.3/getting-started/introduction/

4. **eSewa Developer Documentation**. (2024). *eSewa Payment Gateway — Integration Guide*. https://developer.esewa.com.np/

5. **Khalti Developer Documentation**. (2024). *Khalti Payment Gateway — API Reference*. https://docs.khalti.com/

6. **Laudon, K.C. & Traver, C.G.** (2023). *E-Commerce 2023: Business, Technology, Society* (17th ed.). Pearson Education.

7. **Statista**. (2024). *E-Commerce Worldwide — Statistics & Facts*. https://www.statista.com/topics/1200/online-shopping/

8. **W3Techs**. (2024). *Usage Statistics of PHP for Websites*. https://w3techs.com/technologies/details/pl-php

9. **Mozilla Developer Network**. (2024). *Web Development Documentation*. https://developer.mozilla.org/en-US/

10. **OWASP Foundation**. (2023). *OWASP Top Ten Web Application Security Risks*. https://owasp.org/www-project-top-ten/

---

*Prepared for BCA 5th Semester, E-Business Course*
*MegaFoot — Online Shoe Store Management System*
