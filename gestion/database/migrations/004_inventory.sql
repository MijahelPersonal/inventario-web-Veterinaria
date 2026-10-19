CREATE TABLE stock_lots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_id BIGINT UNSIGNED NOT NULL,
 lot_code VARCHAR(100) NOT NULL,expires_at DATE NULL,cost DECIMAL(12,2) NOT NULL,
 quantity DECIMAL(14,3) NOT NULL,reserved_quantity DECIMAL(14,3) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(product_id) REFERENCES products(id),CHECK(cost>=0),CHECK(quantity>=0),
 CHECK(reserved_quantity>=0 AND reserved_quantity<=quantity),INDEX(product_id,expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE stock_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,lot_id BIGINT UNSIGNED NOT NULL,
 quantity DECIMAL(14,3) NOT NULL,cost DECIMAL(12,2) NOT NULL,
 kind ENUM('compra','inicial','ajuste','consumo','perdida','vencimiento','venta','devolucion') NOT NULL,
 reason VARCHAR(255) NOT NULL,supplier_id BIGINT UNSIGNED NULL,reference VARCHAR(100) NULL,
 user_id BIGINT UNSIGNED NOT NULL,request_key CHAR(64) NOT NULL UNIQUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(lot_id) REFERENCES stock_lots(id),FOREIGN KEY(supplier_id) REFERENCES suppliers(id),
 FOREIGN KEY(user_id) REFERENCES users(id),CHECK(quantity<>0),INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO role_permissions(role_id,permission) SELECT id,'inventory.read' FROM roles WHERE name IN ('Recepcionista','Cajero','Veterinario');
