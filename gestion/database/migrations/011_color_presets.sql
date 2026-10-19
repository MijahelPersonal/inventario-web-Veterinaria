-- Adapta los colores personalizados anteriores a la nueva selección de diez opciones.
UPDATE settings SET setting_value='#2563eb'
WHERE setting_key='primary_color' AND setting_value NOT IN
('#2563eb','#4f46e5','#7c3aed','#9333ea','#be185d','#dc2626','#c2410c','#047857','#0f766e','#0369a1');
