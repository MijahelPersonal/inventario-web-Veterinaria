CREATE TABLE veterinarian_hours (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,veterinarian_id BIGINT UNSIGNED NOT NULL,
 weekday TINYINT UNSIGNED NOT NULL,start_time TIME NOT NULL,end_time TIME NOT NULL,
 FOREIGN KEY(veterinarian_id) REFERENCES veterinarians(id),UNIQUE(veterinarian_id,weekday),
 CHECK(weekday BETWEEN 1 AND 7),CHECK(start_time<end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE appointments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,pet_id BIGINT UNSIGNED NOT NULL,
 veterinarian_id BIGINT UNSIGNED NOT NULL,service_id BIGINT UNSIGNED NOT NULL,
 start_at DATETIME NOT NULL,end_at DATETIME NOT NULL,price DECIMAL(12,2) NOT NULL,
 status ENUM('solicitada','confirmada','atendida','cancelada','no_asistio') NOT NULL DEFAULT 'solicitada',
 source ENUM('manual','whatsapp','web') NOT NULL DEFAULT 'manual',notes TEXT NULL,
 user_id BIGINT UNSIGNED NOT NULL,request_key CHAR(64) NOT NULL UNIQUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(pet_id) REFERENCES pets(id),FOREIGN KEY(veterinarian_id) REFERENCES veterinarians(id),
 FOREIGN KEY(service_id) REFERENCES services(id),FOREIGN KEY(user_id) REFERENCES users(id),
 CHECK(start_at<end_at),CHECK(price>=0),INDEX(veterinarian_id,start_at,end_at),INDEX(start_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO role_permissions(role_id,permission) SELECT id,'appointments.read' FROM roles WHERE name IN ('Recepcionista','Cajero','Veterinario');
INSERT INTO role_permissions(role_id,permission) SELECT id,'appointments.write' FROM roles WHERE name IN ('Recepcionista','Veterinario');
