INSERT INTO role_permissions(role_id,permission) SELECT id,'clinical.read' FROM roles WHERE name='Veterinario';
INSERT INTO role_permissions(role_id,permission) SELECT id,'clinical.write' FROM roles WHERE name='Veterinario';
