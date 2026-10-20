-- Las solicitudes públicas se revisan antes de confirmar citas o compras.
CREATE TABLE web_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reference CHAR(12) NOT NULL UNIQUE, kind ENUM('appointment','order','contact') NOT NULL,
 status ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
 name VARCHAR(120) NOT NULL, phone VARCHAR(40) NOT NULL, email VARCHAR(190) NULL,
 payload JSON NOT NULL, request_key CHAR(64) NOT NULL UNIQUE, client_hash CHAR(64) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(status,created_at), INDEX(client_hash,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE web_submission_limits (
 bucket CHAR(64) PRIMARY KEY, attempts INT UNSIGNED NOT NULL DEFAULT 0,
 window_start DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO role_permissions(role_id,permission) SELECT id,'web_requests.manage' FROM roles WHERE name='Cajero';
