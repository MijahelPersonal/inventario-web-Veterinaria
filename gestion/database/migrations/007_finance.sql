CREATE TABLE cash_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,
 opening_amount DECIMAL(14,2) NOT NULL,opened_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 closed_at TIMESTAMP NULL,expected_amount DECIMAL(14,2) NULL,counted_amount DECIMAL(14,2) NULL,difference DECIMAL(14,2) NULL,
 open_user_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN closed_at IS NULL THEN user_id ELSE NULL END) STORED,
 UNIQUE(open_user_id),FOREIGN KEY(user_id) REFERENCES users(id),CHECK(opening_amount>=0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE sales (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,client_id BIGINT UNSIGNED NULL,appointment_id BIGINT UNSIGNED NULL UNIQUE,
 total DECIMAL(14,2) NOT NULL DEFAULT 0,cost_total DECIMAL(14,2) NOT NULL DEFAULT 0,
 status ENUM('confirmed','void') NOT NULL DEFAULT 'confirmed',void_reason VARCHAR(255) NULL,
 user_id BIGINT UNSIGNED NOT NULL,request_key CHAR(64) NOT NULL UNIQUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(client_id) REFERENCES clients(id),FOREIGN KEY(appointment_id) REFERENCES appointments(id),
 FOREIGN KEY(user_id) REFERENCES users(id),CHECK(total>=0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE sale_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,sale_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NULL,service_id BIGINT UNSIGNED NULL,description VARCHAR(190) NOT NULL,
 quantity DECIMAL(14,3) NOT NULL,unit_price DECIMAL(12,2) NOT NULL,line_total DECIMAL(14,2) NOT NULL,cost_total DECIMAL(14,2) NOT NULL DEFAULT 0,
 FOREIGN KEY(sale_id) REFERENCES sales(id),FOREIGN KEY(product_id) REFERENCES products(id),FOREIGN KEY(service_id) REFERENCES services(id),
 CHECK(quantity>0),CHECK((product_id IS NOT NULL AND service_id IS NULL) OR (product_id IS NULL AND service_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE payments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,sale_id BIGINT UNSIGNED NOT NULL,session_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(14,2) NOT NULL,method ENUM('efectivo','transferencia','tarjeta') NOT NULL,
 reversal_of BIGINT UNSIGNED NULL UNIQUE,notes VARCHAR(255) NULL,user_id BIGINT UNSIGNED NOT NULL,
 request_key CHAR(64) NOT NULL UNIQUE,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(sale_id) REFERENCES sales(id),FOREIGN KEY(session_id) REFERENCES cash_sessions(id),
 FOREIGN KEY(reversal_of) REFERENCES payments(id),FOREIGN KEY(user_id) REFERENCES users(id),CHECK(amount<>0),INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE cash_entries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,session_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(14,2) NOT NULL,reason VARCHAR(255) NOT NULL,user_id BIGINT UNSIGNED NOT NULL,
 request_key CHAR(64) NOT NULL UNIQUE,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(session_id) REFERENCES cash_sessions(id),FOREIGN KEY(user_id) REFERENCES users(id),CHECK(amount<>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO role_permissions(role_id,permission) SELECT id,'sales.read' FROM roles WHERE name IN ('Recepcionista','Cajero');
INSERT INTO role_permissions(role_id,permission) SELECT id,'sales.write' FROM roles WHERE name IN ('Recepcionista','Cajero');
INSERT INTO role_permissions(role_id,permission) SELECT id,'payments.write' FROM roles WHERE name IN ('Recepcionista','Cajero');
INSERT INTO role_permissions(role_id,permission) SELECT id,'cash.write' FROM roles WHERE name IN ('Recepcionista','Cajero');
INSERT INTO role_permissions(role_id,permission) SELECT id,'reports.read' FROM roles WHERE name='Cajero';
