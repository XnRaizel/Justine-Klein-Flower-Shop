ALTER TABLE payments
  ADD payment_type ENUM('full','deposit','balance') NOT NULL DEFAULT 'full' AFTER order_id,
  ADD provider_url VARCHAR(500) NULL AFTER provider_ref;
