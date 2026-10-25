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

ALTER TABLE orders
  ADD COLUMN custom_order_request_id INT NULL,
  ADD UNIQUE KEY uq_orders_custom_order_request_id (custom_order_request_id),
  ADD CONSTRAINT fk_orders_custom_order_request_id
    FOREIGN KEY (custom_order_request_id) REFERENCES custom_order_requests(custom_order_request_id);
