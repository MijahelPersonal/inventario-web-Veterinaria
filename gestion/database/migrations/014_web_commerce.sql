-- Campos opcionales y valores por defecto: preserva productos e historial existentes.
ALTER TABLE products
 ADD COLUMN web_group VARCHAR(30) NOT NULL DEFAULT 'general',
 ADD COLUMN pet_type VARCHAR(15) NOT NULL DEFAULT 'ambos',
 ADD COLUMN offer_price DECIMAL(12,2) NULL,
 ADD COLUMN offer_from DATE NULL,
 ADD COLUMN offer_until DATE NULL,
 ADD INDEX idx_web_group (publish_online,active,web_group),
 ADD CONSTRAINT chk_offer_price CHECK (offer_price IS NULL OR (offer_price>0 AND offer_price<price)),
 ADD CONSTRAINT chk_offer_dates CHECK (offer_from IS NULL OR offer_until IS NULL OR offer_from<=offer_until);
