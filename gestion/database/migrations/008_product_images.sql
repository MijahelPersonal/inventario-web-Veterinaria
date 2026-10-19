ALTER TABLE products ADD COLUMN image_path VARCHAR(100) NULL;
UPDATE settings SET setting_value='#2563eb' WHERE setting_key='primary_color' AND setting_value='#176b63';
