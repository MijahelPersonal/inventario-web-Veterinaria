CREATE TABLE settings (
 setting_key VARCHAR(80) PRIMARY KEY,setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO settings(setting_key,setting_value) VALUES
 ('clinic_name','STRUCH VET'),('contact_phone',''),('contact_email',''),('address',''),('currency','PEN'),('primary_color','#176b63');
ALTER TABLE pets ADD COLUMN microchip VARCHAR(80) NULL,ADD COLUMN allergies TEXT NULL,ADD COLUMN background TEXT NULL;
ALTER TABLE appointments ADD COLUMN symptoms TEXT NULL,ADD COLUMN diagnosis TEXT NULL,ADD COLUMN treatment TEXT NULL;
