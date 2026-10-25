INSERT IGNORE INTO roles (role_name) VALUES ('rider');

ALTER TABLE deliveries
  ADD confirmation_pin CHAR(6) NULL AFTER delivered_at,
  ADD delivered_to VARCHAR(100) NULL AFTER confirmation_pin,
  ADD confirmed_by INT NULL AFTER delivered_to,
  ADD CONSTRAINT fk_deliveries_confirmed_by FOREIGN KEY (confirmed_by) REFERENCES users(user_id);

UPDATE deliveries
SET confirmation_pin = LPAD(FLOOR(RAND() * 1000000), 6, '0')
WHERE status IN ('scheduled', 'preparing', 'out_for_delivery') AND confirmation_pin IS NULL;
