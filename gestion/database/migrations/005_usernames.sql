ALTER TABLE users ADD COLUMN username VARCHAR(60) NULL AFTER name;
UPDATE users SET username=CONCAT('usuario',id);
UPDATE users SET username='admin' WHERE email='admin@struchvet.local';
ALTER TABLE users MODIFY username VARCHAR(60) NOT NULL,ADD UNIQUE KEY users_username_unique(username);
ALTER TABLE users MODIFY email VARCHAR(190) NULL;
