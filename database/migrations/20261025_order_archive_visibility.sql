ALTER TABLE orders
  ADD COLUMN customer_hidden_at DATETIME NULL,
  ADD COLUMN operations_archived_at DATETIME NULL;

ALTER TABLE deliveries
  ADD COLUMN operations_archived_at DATETIME NULL;
