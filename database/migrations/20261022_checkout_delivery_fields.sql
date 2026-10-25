ALTER TABLE deliveries
  MODIFY latitude DECIMAL(10,7) NULL,
  MODIFY longitude DECIMAL(10,7) NULL,
  MODIFY distance_km DECIMAL(6,2) NULL,
  MODIFY status ENUM('scheduled','preparing','out_for_delivery','delivered','failed','cancelled') NOT NULL DEFAULT 'scheduled';
