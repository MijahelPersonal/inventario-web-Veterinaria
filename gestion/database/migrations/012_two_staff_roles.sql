-- Simplifica perfiles conservando todas las cuentas y al administrador existente.
START TRANSACTION;
INSERT IGNORE INTO user_roles(user_id,role_id)
SELECT ur.user_id,c.id FROM user_roles ur JOIN roles previous ON previous.id=ur.role_id
JOIN roles c ON c.name='Cajero' WHERE previous.name IN ('Recepcionista','Veterinario');
DELETE ur FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE r.name IN ('Recepcionista','Veterinario');
DELETE cashier FROM user_roles cashier JOIN roles c ON c.id=cashier.role_id AND c.name='Cajero'
JOIN user_roles administrator ON administrator.user_id=cashier.user_id
JOIN roles a ON a.id=administrator.role_id AND a.name='Administrador';
DELETE rp FROM role_permissions rp JOIN roles r ON r.id=rp.role_id WHERE r.name IN ('Recepcionista','Veterinario');
DELETE FROM roles WHERE name IN ('Recepcionista','Veterinario');
COMMIT;
