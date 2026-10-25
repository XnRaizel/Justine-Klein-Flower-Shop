ALTER TABLE payments
  MODIFY status ENUM('pending','paid','failed','partially_refunded','refunded') NOT NULL DEFAULT 'pending',
  ADD paid_by INT NULL AFTER paid_at,
  ADD CONSTRAINT fk_payments_paid_by FOREIGN KEY (paid_by) REFERENCES users(user_id);

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
