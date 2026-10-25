-- =====================================================================
-- Bloom Express database  (Checking 2)
-- Import with phpMyAdmin (Laragon): Import tab -> choose this file -> Go
-- Re-importing is safe: tables are dropped and recreated.
-- Demo logins (all use the password: password123)
--   admin@bloomexpress.test  (admin)    staff@bloomexpress.test (staff)
--   rider@bloomexpress.test  (rider)
--   maria@example.com (customer)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS bloom_express CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bloom_express;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS stock_logs;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS deliveries;
DROP TABLE IF EXISTS order_item_addons;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS custom_order_messages;
DROP TABLE IF EXISTS custom_order_requests;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS addons;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
  role_id INT NOT NULL AUTO_INCREMENT,
  role_name VARCHAR(30) NOT NULL,
  PRIMARY KEY (role_id),
  UNIQUE KEY uq_roles_role_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  user_id INT NOT NULL AUTO_INCREMENT,
  role_id INT NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL,
  phone VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  google_id VARCHAR(64),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email),
  CONSTRAINT fk_users_role_id FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
  category_id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  slug VARCHAR(60) NOT NULL,
  PRIMARY KEY (category_id),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
  product_id INT NOT NULL AUTO_INCREMENT,
  category_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255),
  stock_qty INT NOT NULL DEFAULT 0,
  low_stock_level INT NOT NULL DEFAULT 5,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (product_id),
  CONSTRAINT fk_products_category_id FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE addons (
  addon_id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  type VARCHAR(30) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (addon_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE carts (
  cart_id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cart_id),
  CONSTRAINT fk_carts_user_id FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cart_items (
  cart_item_id INT NOT NULL AUTO_INCREMENT,
  cart_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  customization VARCHAR(255),
  PRIMARY KEY (cart_item_id),
  CONSTRAINT fk_cart_items_cart_id FOREIGN KEY (cart_id) REFERENCES carts(cart_id),
  CONSTRAINT fk_cart_items_product_id FOREIGN KEY (product_id) REFERENCES products(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE custom_order_requests (
  custom_order_request_id INT NOT NULL AUTO_INCREMENT,
  request_number VARCHAR(24) NOT NULL,
  user_id INT NOT NULL,
  title VARCHAR(120) NOT NULL,
  occasion VARCHAR(80),
  requested_flowers TEXT NOT NULL,
  colors VARCHAR(255),
  budget DECIMAL(10,2),
  recipient_name VARCHAR(100),
  delivery_address VARCHAR(255),
  requested_delivery_at DATETIME,
  quote_total DECIMAL(10,2),
  status ENUM('new','in_review','waiting_for_customer','quoted','accepted','declined','converted_to_order','closed') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (custom_order_request_id),
  UNIQUE KEY uq_custom_order_requests_number (request_number),
  CONSTRAINT fk_custom_order_requests_user_id FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE custom_order_messages (
  custom_order_message_id INT NOT NULL AUTO_INCREMENT,
  custom_order_request_id INT NOT NULL,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (custom_order_message_id),
  CONSTRAINT fk_custom_order_messages_request_id FOREIGN KEY (custom_order_request_id) REFERENCES custom_order_requests(custom_order_request_id),
  CONSTRAINT fk_custom_order_messages_user_id FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
  order_id INT NOT NULL AUTO_INCREMENT,
  order_number VARCHAR(20) NOT NULL,
  user_id INT NOT NULL,
  status ENUM('pending','confirmed','preparing','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
  subtotal DECIMAL(10,2) NOT NULL,
  delivery_fee DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  card_message VARCHAR(255),
  custom_order_request_id INT,
  customer_hidden_at DATETIME NULL,
  operations_archived_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (order_id),
  UNIQUE KEY uq_orders_order_number (order_number),
  UNIQUE KEY uq_orders_custom_order_request_id (custom_order_request_id),
  CONSTRAINT fk_orders_user_id FOREIGN KEY (user_id) REFERENCES users(user_id),
  CONSTRAINT fk_orders_custom_order_request_id FOREIGN KEY (custom_order_request_id) REFERENCES custom_order_requests(custom_order_request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
  order_item_id INT NOT NULL AUTO_INCREMENT,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (order_item_id),
  CONSTRAINT fk_order_items_order_id FOREIGN KEY (order_id) REFERENCES orders(order_id),
  CONSTRAINT fk_order_items_product_id FOREIGN KEY (product_id) REFERENCES products(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_item_addons (
  order_item_id INT NOT NULL,
  addon_id INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (order_item_id, addon_id),
  CONSTRAINT fk_order_item_addons_order_item_id FOREIGN KEY (order_item_id) REFERENCES order_items(order_item_id),
  CONSTRAINT fk_order_item_addons_addon_id FOREIGN KEY (addon_id) REFERENCES addons(addon_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE deliveries (
  delivery_id INT NOT NULL AUTO_INCREMENT,
  order_id INT NOT NULL,
  recipient_name VARCHAR(100) NOT NULL,
  address VARCHAR(255) NOT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  distance_km DECIMAL(6,2) NULL,
  scheduled_at DATETIME NOT NULL,
  status ENUM('scheduled','preparing','out_for_delivery','delivered','failed','cancelled') NOT NULL DEFAULT 'scheduled',
  assigned_to INT,
  delivered_at DATETIME,
  operations_archived_at DATETIME NULL,
  confirmation_pin CHAR(6),
  delivered_to VARCHAR(100),
  confirmed_by INT,
  PRIMARY KEY (delivery_id),
  UNIQUE KEY uq_deliveries_order_id (order_id),
  CONSTRAINT fk_deliveries_order_id FOREIGN KEY (order_id) REFERENCES orders(order_id),
  CONSTRAINT fk_deliveries_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(user_id),
  CONSTRAINT fk_deliveries_confirmed_by FOREIGN KEY (confirmed_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
  payment_id INT NOT NULL AUTO_INCREMENT,
  order_id INT NOT NULL,
  payment_type ENUM('full','deposit','balance') NOT NULL DEFAULT 'full',
  provider VARCHAR(30) NOT NULL,
  provider_ref VARCHAR(100),
  provider_url VARCHAR(500),
  method VARCHAR(20) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','paid','failed','partially_refunded','refunded') NOT NULL DEFAULT 'pending',
  paid_at DATETIME,
  paid_by INT,
  PRIMARY KEY (payment_id),
  CONSTRAINT fk_payments_order_id FOREIGN KEY (order_id) REFERENCES orders(order_id),
  CONSTRAINT fk_payments_paid_by FOREIGN KEY (paid_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payment_refunds (
  refund_id INT NOT NULL AUTO_INCREMENT,
  payment_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  recorded_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (refund_id),
  KEY idx_payment_refunds_payment_id (payment_id),
  CONSTRAINT fk_payment_refunds_payment_id FOREIGN KEY (payment_id) REFERENCES payments(payment_id),
  CONSTRAINT fk_payment_refunds_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_status_history (
  history_id INT NOT NULL AUTO_INCREMENT,
  order_id INT NOT NULL,
  from_status ENUM('pending','confirmed','preparing','out_for_delivery','delivered','cancelled') NULL,
  to_status ENUM('pending','confirmed','preparing','out_for_delivery','delivered','cancelled') NOT NULL,
  changed_by INT NOT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (history_id),
  KEY idx_order_status_history_order_id (order_id),
  CONSTRAINT fk_order_status_history_order_id FOREIGN KEY (order_id) REFERENCES orders(order_id),
  CONSTRAINT fk_order_status_history_changed_by FOREIGN KEY (changed_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_logs (
  log_id INT NOT NULL AUTO_INCREMENT,
  product_id INT NOT NULL,
  order_id INT,
  user_id INT NOT NULL,
  change_qty INT NOT NULL,
  reason VARCHAR(40) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (log_id),
  CONSTRAINT fk_stock_logs_product_id FOREIGN KEY (product_id) REFERENCES products(product_id),
  CONSTRAINT fk_stock_logs_order_id FOREIGN KEY (order_id) REFERENCES orders(order_id),
  CONSTRAINT fk_stock_logs_user_id FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
  notification_id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  order_id INT NOT NULL,
  channel ENUM('email','sms') NOT NULL DEFAULT 'email',
  message VARCHAR(255) NOT NULL,
  status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  sent_at DATETIME,
  PRIMARY KEY (notification_id),
  CONSTRAINT fk_notifications_user_id FOREIGN KEY (user_id) REFERENCES users(user_id),
  CONSTRAINT fk_notifications_order_id FOREIGN KEY (order_id) REFERENCES orders(order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- sample data ----------
INSERT INTO roles (role_id, role_name) VALUES
  (1, 'customer'),
  (2, 'staff'),
  (3, 'admin'),
  (4, 'rider');

INSERT INTO users (user_id, role_id, full_name, email, phone, password_hash, created_at) VALUES
  (1, 3, 'Owner Admin', 'admin@bloomexpress.test', '09170000001', '$2y$10$dBM4tIbWjQJHd1xWnlMqq.7fVOZs5AY7Tsslln9Asn7J1WHBRs.LG', NOW() - INTERVAL 30 DAY),
  (2, 2, 'Shop Staff', 'staff@bloomexpress.test', '09170000002', '$2y$10$dBM4tIbWjQJHd1xWnlMqq.7fVOZs5AY7Tsslln9Asn7J1WHBRs.LG', NOW() - INTERVAL 30 DAY),
  (3, 4, 'Rider Staff', 'rider@bloomexpress.test', '09170000003', '$2y$10$dBM4tIbWjQJHd1xWnlMqq.7fVOZs5AY7Tsslln9Asn7J1WHBRs.LG', NOW() - INTERVAL 30 DAY),
  (4, 1, 'Maria Santos', 'maria@example.com', '09171234567', '$2y$10$dBM4tIbWjQJHd1xWnlMqq.7fVOZs5AY7Tsslln9Asn7J1WHBRs.LG', NOW() - INTERVAL 30 DAY),
  (5, 1, 'Juan Dela Cruz', 'juan@example.com', '09181234567', '$2y$10$dBM4tIbWjQJHd1xWnlMqq.7fVOZs5AY7Tsslln9Asn7J1WHBRs.LG', NOW() - INTERVAL 30 DAY),
  (6, 1, 'Ana Reyes', 'ana@example.com', '09191234567', '$2y$10$dBM4tIbWjQJHd1xWnlMqq.7fVOZs5AY7Tsslln9Asn7J1WHBRs.LG', NOW() - INTERVAL 30 DAY);

INSERT INTO categories (category_id, name, slug) VALUES
  (1, 'Birthday', 'birthday'),
  (2, 'Anniversary', 'anniversary'),
  (3, 'Graduation', 'graduation'),
  (4, 'Sympathy', 'sympathy'),
  (5, 'Thank You', 'thank-you');

INSERT INTO products (product_id, category_id, name, description, price, stock_qty, low_stock_level, is_archived, created_at) VALUES
  (1, 2, 'Rosy Delight', 'A dozen red roses wrapped in kraft paper with baby''s breath.', 1299, 19, 5, 0, NOW() - INTERVAL 25 DAY),
  (2, 1, 'Sunny Day Bouquet', 'Sunflowers, daisies and yellow chrysanthemums for a cheerful birthday.', 899, 13, 5, 0, NOW() - INTERVAL 25 DAY),
  (3, 3, 'Graduate Pride', 'Mixed blooms in school colors with a congratulatory ribbon.', 1099, 11, 5, 0, NOW() - INTERVAL 25 DAY),
  (4, 4, 'Peaceful White', 'White lilies and carnations arranged in a calm, elegant basket.', 1499, 8, 3, 0, NOW() - INTERVAL 25 DAY),
  (5, 5, 'Pink Gratitude', 'Pink tulips and peonies, a simple way to say thank you.', 749, 24, 5, 0, NOW() - INTERVAL 25 DAY),
  (6, 2, 'Forever Together', 'Premium roses and orchids in a keepsake box.', 1899, 5, 3, 0, NOW() - INTERVAL 25 DAY),
  (7, 1, 'Birthday Blush', 'Pastel roses, lisianthus and eucalyptus in a hand-tied bouquet.', 999, 12, 5, 0, NOW() - INTERVAL 25 DAY),
  (8, 1, 'Party Pop Mix', 'Bright gerberas and carnations that bring the party to the room.', 799, 17, 5, 0, NOW() - INTERVAL 25 DAY),
  (9, 3, 'Cap & Gown Blooms', 'Blue and white hydrangeas with a graduation cap topper.', 1199, 9, 4, 0, NOW() - INTERVAL 25 DAY),
  (10, 4, 'Serene Lilies', 'Oriental lilies with soft greenery, arranged for quiet remembrance.', 1699, 7, 3, 0, NOW() - INTERVAL 25 DAY),
  (11, 5, 'Thank You Tulips', 'Fresh tulips in a reusable glass vase.', 849, 13, 5, 0, NOW() - INTERVAL 25 DAY),
  (12, 2, 'Golden Anniversary Box', 'Gold-tinted roses, orchids and chocolates in a luxury box.', 2499, 3, 3, 0, NOW() - INTERVAL 25 DAY),
  (13, 2, 'Old Valentine Special', 'Seasonal bouquet no longer offered (archived).', 1599, 0, 3, 1, NOW() - INTERVAL 25 DAY);

INSERT INTO addons (addon_id, name, type, price) VALUES
  (1, 'Satin Ribbon', 'extra', 50),
  (2, 'Chocolate Box', 'extra', 250),
  (3, 'Teddy Bear', 'extra', 350),
  (4, 'Extra Rose Stems', 'flower', 120),
  (5, 'Baby''s Breath Filler', 'flower', 80),
  (6, 'Red Wrapping', 'color', 0),
  (7, 'Pink Wrapping', 'color', 0),
  (8, 'White Wrapping', 'color', 0);

INSERT INTO carts (cart_id, user_id, created_at) VALUES
  (1, 4, NOW() - INTERVAL 0 DAY);

INSERT INTO cart_items (cart_item_id, cart_id, product_id, quantity, customization) VALUES
  (1, 1, 1, 1, 'Red wrapping, satin ribbon'),
  (2, 1, 5, 2, 'Pink wrapping');

INSERT INTO orders (order_id, order_number, user_id, status, subtotal, delivery_fee, total, card_message, created_at) VALUES
  (1, 'BE-0001', 4, 'delivered', 1349, 110, 1459, 'Happy anniversary, love!', NOW() - INTERVAL 9 DAY),
  (2, 'BE-0002', 5, 'delivered', 1698, 142, 1840, 'Happy birthday Mom!', NOW() - INTERVAL 7 DAY),
  (3, 'BE-0003', 6, 'delivered', 2348, 97, 2445, 'Congratulations, graduate!', NOW() - INTERVAL 5 DAY),
  (4, 'BE-0004', 4, 'cancelled', 1499, 126, 1625, 'With deepest sympathy', NOW() - INTERVAL 4 DAY),
  (5, 'BE-0005', 5, 'out_for_delivery', 2149, 149, 2298, 'Forever yours', NOW() - INTERVAL 1 DAY),
  (6, 'BE-0006', 6, 'preparing', 3097, 89, 3186, 'Thanks for everything!', NOW() - INTERVAL 1 DAY),
  (7, 'BE-0007', 4, 'confirmed', 2799, 167, 2966, '50 golden years!', NOW() - INTERVAL 0 DAY),
  (8, 'BE-0008', 5, 'pending', 2547, 103, 2650, 'Salamat po!', NOW() - INTERVAL 0 DAY),
  (9, 'BE-0009', 6, 'pending', 1029, 82, 1111, 'Happy birthday!', NOW() - INTERVAL 0 DAY);

INSERT INTO order_items (order_item_id, order_id, product_id, quantity, unit_price) VALUES
  (1, 1, 1, 1, 1299),
  (2, 2, 2, 1, 899),
  (3, 2, 8, 1, 799),
  (4, 3, 3, 1, 1099),
  (5, 3, 9, 1, 1199),
  (6, 4, 4, 1, 1499),
  (7, 5, 6, 1, 1899),
  (8, 6, 7, 2, 999),
  (9, 6, 5, 1, 749),
  (10, 7, 12, 1, 2499),
  (11, 8, 11, 3, 849),
  (12, 9, 2, 1, 899);

INSERT INTO order_item_addons (order_item_id, addon_id, price) VALUES
  (1, 6, 0),
  (1, 1, 50),
  (2, 7, 0),
  (4, 1, 50),
  (7, 2, 250),
  (7, 6, 0),
  (8, 8, 0),
  (9, 3, 350),
  (10, 2, 250),
  (10, 1, 50),
  (12, 5, 80),
  (12, 1, 50);

INSERT INTO deliveries (delivery_id, order_id, recipient_name, address, latitude, longitude, distance_km, scheduled_at, status, assigned_to, delivered_at) VALUES
  (1, 1, 'Maria Santos', '12 Mabini St., Quezon City', 14.561, 121.039, 4.2, NOW() - INTERVAL 8 DAY, 'delivered', 2, NOW() - INTERVAL 8 DAY),
  (2, 2, 'Juan Dela Cruz', '45 Rizal Ave., Manila', 14.572, 121.048, 6.8, NOW() - INTERVAL 6 DAY, 'delivered', 3, NOW() - INTERVAL 6 DAY),
  (3, 3, 'Ana Reyes', '8 Katipunan Rd., Quezon City', 14.583, 121.057, 3.1, NOW() - INTERVAL 4 DAY, 'delivered', 2, NOW() - INTERVAL 4 DAY),
  (4, 4, 'Maria Santos', '90 Sampaguita St., Pasig', 14.594, 121.066, 5.5, NOW() - INTERVAL 3 DAY, 'failed', NULL, NULL),
  (5, 5, 'Juan Dela Cruz', '21 Bonifacio Dr., Taguig', 14.605, 121.075, 7.4, NOW() + INTERVAL 0 DAY, 'out_for_delivery', 3, NULL),
  (6, 6, 'Ana Reyes', '3 Maple St., Mandaluyong', 14.616, 121.084, 2.4, NOW() + INTERVAL 1 DAY, 'preparing', NULL, NULL),
  (7, 7, 'Maria Santos', '67 Acacia Ave., Makati', 14.627, 121.093, 8.9, NOW() + INTERVAL 2 DAY, 'scheduled', NULL, NULL),
  (8, 8, 'Juan Dela Cruz', '14 Orchid Lane, San Juan', 14.638, 121.102, 3.6, NOW() + INTERVAL 2 DAY, 'scheduled', NULL, NULL),
  (9, 9, 'Ana Reyes', '5 Jasmine Rd., Pasig', 14.649, 121.111, 1.8, NOW() + INTERVAL 3 DAY, 'scheduled', NULL, NULL);

INSERT INTO payments (payment_id, order_id, provider, provider_ref, method, amount, status, paid_at) VALUES
  (1, 1, 'PayMongo', 'pay_demo_0001', 'gcash', 1459, 'paid', NOW() - INTERVAL 9 DAY),
  (2, 2, 'PayMongo', 'pay_demo_0002', 'maya', 1840, 'paid', NOW() - INTERVAL 7 DAY),
  (3, 3, 'PayMongo', 'pay_demo_0003', 'card', 2445, 'paid', NOW() - INTERVAL 5 DAY),
  (4, 4, 'PayMongo', 'pay_demo_0004', 'gcash', 1625, 'refunded', NOW() - INTERVAL 4 DAY),
  (5, 5, 'PayMongo', 'pay_demo_0005', 'gcash', 2298, 'paid', NOW() - INTERVAL 1 DAY),
  (6, 6, 'PayMongo', 'pay_demo_0006', 'maya', 3186, 'paid', NOW() - INTERVAL 1 DAY),
  (7, 7, 'PayMongo', 'pay_demo_0007', 'card', 2966, 'paid', NOW() - INTERVAL 0 DAY),
  (8, 8, 'PayMongo', 'pay_demo_0008', 'gcash', 2650, 'pending', NULL),
  (9, 9, 'PayMongo', 'pay_demo_0009', 'maya', 1111, 'pending', NULL);

INSERT INTO stock_logs (log_id, product_id, order_id, user_id, change_qty, reason, created_at) VALUES
  (1, 1, NULL, 1, 20, 'restock', NOW() - INTERVAL 25 DAY),
  (2, 2, NULL, 1, 15, 'restock', NOW() - INTERVAL 25 DAY),
  (3, 3, NULL, 1, 12, 'restock', NOW() - INTERVAL 25 DAY),
  (4, 4, NULL, 1, 8, 'restock', NOW() - INTERVAL 25 DAY),
  (5, 5, NULL, 1, 25, 'restock', NOW() - INTERVAL 25 DAY),
  (6, 6, NULL, 1, 6, 'restock', NOW() - INTERVAL 25 DAY),
  (7, 7, NULL, 1, 14, 'restock', NOW() - INTERVAL 25 DAY),
  (8, 8, NULL, 1, 18, 'restock', NOW() - INTERVAL 25 DAY),
  (9, 9, NULL, 1, 10, 'restock', NOW() - INTERVAL 25 DAY),
  (10, 10, NULL, 1, 7, 'restock', NOW() - INTERVAL 25 DAY),
  (11, 11, NULL, 1, 16, 'restock', NOW() - INTERVAL 25 DAY),
  (12, 12, NULL, 1, 4, 'restock', NOW() - INTERVAL 25 DAY),
  (13, 1, 1, 4, -1, 'order', NOW() - INTERVAL 9 DAY),
  (14, 2, 2, 5, -1, 'order', NOW() - INTERVAL 7 DAY),
  (15, 8, 2, 5, -1, 'order', NOW() - INTERVAL 7 DAY),
  (16, 3, 3, 6, -1, 'order', NOW() - INTERVAL 5 DAY),
  (17, 9, 3, 6, -1, 'order', NOW() - INTERVAL 5 DAY),
  (18, 6, 5, 5, -1, 'order', NOW() - INTERVAL 1 DAY),
  (19, 7, 6, 6, -2, 'order', NOW() - INTERVAL 1 DAY),
  (20, 5, 6, 6, -1, 'order', NOW() - INTERVAL 1 DAY),
  (21, 12, 7, 4, -1, 'order', NOW() - INTERVAL 0 DAY),
  (22, 11, 8, 5, -3, 'order', NOW() - INTERVAL 0 DAY),
  (23, 2, 9, 6, -1, 'order', NOW() - INTERVAL 0 DAY);

INSERT INTO notifications (notification_id, user_id, order_id, channel, message, status, sent_at) VALUES
  (1, 4, 1, 'email', 'Receipt for order BE-0001', 'sent', NOW() - INTERVAL 9 DAY),
  (2, 4, 1, 'sms', 'Order BE-0001 is now delivered', 'sent', NOW() - INTERVAL 9 DAY),
  (3, 5, 2, 'email', 'Receipt for order BE-0002', 'sent', NOW() - INTERVAL 7 DAY),
  (4, 5, 2, 'sms', 'Order BE-0002 is now delivered', 'sent', NOW() - INTERVAL 7 DAY),
  (5, 6, 3, 'email', 'Receipt for order BE-0003', 'sent', NOW() - INTERVAL 5 DAY),
  (6, 6, 3, 'sms', 'Order BE-0003 is now delivered', 'sent', NOW() - INTERVAL 5 DAY),
  (7, 4, 4, 'email', 'Receipt for order BE-0004', 'sent', NOW() - INTERVAL 4 DAY),
  (8, 4, 4, 'sms', 'Order BE-0004 is now cancelled', 'sent', NOW() - INTERVAL 4 DAY),
  (9, 5, 5, 'email', 'Receipt for order BE-0005', 'sent', NOW() - INTERVAL 1 DAY),
  (10, 5, 5, 'sms', 'Order BE-0005 is now out for delivery', 'sent', NOW() - INTERVAL 1 DAY),
  (11, 6, 6, 'email', 'Receipt for order BE-0006', 'sent', NOW() - INTERVAL 1 DAY),
  (12, 6, 6, 'sms', 'Order BE-0006 is now preparing', 'sent', NOW() - INTERVAL 1 DAY),
  (13, 4, 7, 'email', 'Receipt for order BE-0007', 'sent', NOW() - INTERVAL 0 DAY),
  (14, 4, 7, 'sms', 'Order BE-0007 is now confirmed', 'sent', NOW() - INTERVAL 0 DAY),
  (15, 5, 8, 'email', 'Receipt for order BE-0008', 'queued', NULL),
  (16, 5, 8, 'sms', 'Order BE-0008 is now pending', 'sent', NOW() - INTERVAL 0 DAY),
  (17, 6, 9, 'email', 'Receipt for order BE-0009', 'queued', NULL),
  (18, 6, 9, 'sms', 'Order BE-0009 is now pending', 'sent', NOW() - INTERVAL 0 DAY);
