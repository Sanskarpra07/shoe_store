-- setup.sql — Shoe Store Database Setup

CREATE DATABASE IF NOT EXISTS shoe_store_db;
USE shoe_store_db;

-- 1. Users table
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_eg VARCHAR(200) NOT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Brands table
CREATE TABLE IF NOT EXISTS brands (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Products table (shoes)
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    stock INT NOT NULL DEFAULT 0,
    size VARCHAR(50) DEFAULT NULL,
    color VARCHAR(50) DEFAULT NULL,
    image VARCHAR(500) DEFAULT NULL,
    category_id INT UNSIGNED,
    brand_id INT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
);

-- 5. Stock log table
CREATE TABLE IF NOT EXISTS stock_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    change_amount INT NOT NULL,
    reason TEXT,
    changed_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 6. Delivery slots table
CREATE TABLE IF NOT EXISTS delivery_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slot_name VARCHAR(50) NOT NULL,
    slot_time VARCHAR(50) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Customers table (created before orders because orders references it)
CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password_eg VARCHAR(200) NOT NULL,
    otp_code VARCHAR(6) DEFAULT NULL,
    otp_expires_at DATETIME DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED DEFAULT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(20) DEFAULT NULL,
    customer_address TEXT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cod','esewa','khalti') DEFAULT 'cod',
    payment_status ENUM('pending','completed','failed') DEFAULT 'pending',
    transaction_id VARCHAR(100) DEFAULT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    delivery_slot VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- 9. Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 10. Wishlist table
CREATE TABLE IF NOT EXISTS wishlists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_wish (customer_id, product_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 11. Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    comment TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Sample Customer (password: customer123)
INSERT INTO customers (full_name, email, phone, password_eg, is_verified, address) VALUES
    ('Sita Sharma', 'sita@example.com', '9841000000', '$2y$10$fiAOWiQVUfx9LOjnQqyyIu1OVb03fwyNMUCKX8Qq3y8r5hvd9UDc6', 1, 'Baneshwor, Kathmandu');

-- Sample Users (password: password)
INSERT INTO users (username, password_eg, role) VALUES
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
    ('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

-- Sample Categories
INSERT INTO categories (name, icon, description) VALUES
    ('Running', 'fa-solid fa-person-running', 'Running and jogging shoes'),
    ('Casual', 'fa-solid fa-bag-shopping', 'Everyday casual footwear'),
    ('Sports', 'fa-solid fa-trophy', 'Athletic and sports shoes'),
    ('Formal', 'fa-solid fa-briefcase', 'Formal and dress shoes'),
    ('Boots', 'fa-solid fa-boot', 'Boots and heavy-duty footwear');

-- Sample Brands
INSERT INTO brands (name, icon, description) VALUES
    ('Nike', 'fa-solid fa-bolt', 'Global leader in athletic footwear and apparel'),
    ('Adidas', 'fa-solid fa-bars-staggered', 'German multinational corporation designing shoes'),
    ('Puma', 'fa-solid fa-award', 'German multinational designing athletic and casual footwear'),
    ('Reebok', 'fa-solid fa-hexagon', 'British footwear and clothing company'),
    ('New Balance', 'fa-solid fa-shield-halved', 'American sports footwear manufacturer');

-- Sample Delivery Slots
INSERT INTO delivery_slots (slot_name, slot_time, is_active) VALUES
    ('Morning', '8 AM - 11 AM', 1),
    ('Afternoon', '12 PM - 3 PM', 1),
    ('Evening', '4 PM - 7 PM', 1);

-- Sample Products
INSERT INTO products (product_name, description, price, discount_price, stock, size, color, image, category_id, brand_id) VALUES
    ('Nike Air Max 270', 'Comfortable running shoes with Air Max technology', 129.99, 99.99, 45, '8-12', 'Black', 'assets/img/nike_air_max_270.jpg', 1, 1),
    ('Adidas Ultraboost 22', 'Premium running shoes with Boost cushioning', 149.99, NULL, 30, '7-11', 'White', 'assets/img/adidas_ultraboost.jpg', 1, 2),
    ('Puma RS-X', 'Retro-inspired casual sneakers', 89.99, 69.99, 60, '8-12', 'Blue', 'assets/img/puma_rsx.jpg', 2, 3),
    ('Nike Dunk Low', 'Classic casual lifestyle sneakers', 109.99, NULL, 25, '6-10', 'Red', 'assets/img/nike_dunk_low.jpg', 2, 1),
    ('Adidas Stan Smith', 'Iconic casual leather sneakers', 94.99, 79.99, 55, '7-12', 'Green', 'assets/img/adidas_stan_smith.jpg', 2, 2),
    ('Reebok Nano X', 'Cross-training sports shoes', 134.99, NULL, 20, '8-12', 'Black', 'assets/img/reebok_nano_x.jpg', 3, 4),
    ('Nike React Infinity', 'High-performance running shoes', 159.99, 129.99, 15, '7-11', 'Grey', 'assets/img/nike_react_infinity.jpg', 1, 1),
    ('New Balance 574', 'Classic casual running-inspired shoes', 89.99, NULL, 70, '6-12', 'Navy', 'assets/img/new_balance_574.jpg', 2, 5),
    ('Puma Future Rider', 'Lightweight casual sneakers', 74.99, 59.99, 40, '7-11', 'White', 'assets/img/puma_future_rider.jpg', 2, 3),
    ('Adidas Terrex', 'Outdoor trail boots', 169.99, NULL, 18, '8-13', 'Brown', 'assets/img/adidas_terrex.jpg', 5, 2),
    ('Nike Air Force 1', 'Timeless casual basketball sneakers', 109.99, NULL, 80, '6-13', 'White', 'assets/img/nike_air_force_1.jpg', 2, 1),
    ('Reebok Club C', 'Clean casual retro sneakers', 79.99, 64.99, 35, '7-12', 'Cream', 'assets/img/reebok_club_c.jpg', 2, 4);
