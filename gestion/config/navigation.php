<?php
// Define el orden del menú y el permiso que permite ver cada acceso.
return [
    ['label' => '', 'links' => [['Inicio', '/', '⌂', null]]],
    [
        'label' => 'GESTIÓN COMERCIAL',
        'links' => [
            ['Venta en tienda', '/tienda', '＋', 'sales.write'],
            ['Productos', '/catalogos/productos', '▦', 'catalogs.read'],
            ['Inventario', '/inventario', '▤', 'inventory.read'],
            ['Clientes', '/catalogos/clientes', '♙', 'catalogs.read'],
            ['Ventas y cobros', '/ventas', '≡', 'sales.read'],
        ],
    ],
    [
        'label' => 'GESTIÓN VETERINARIA',
        'links' => [
            ['Citas / Agenda', '/citas', '▦', 'appointments.read'],
            ['Calendario', '/calendario', '▦', 'appointments.read'],
            ['Mascotas / Pacientes', '/catalogos/mascotas', '♡', 'catalogs.read'],
            ['Veterinarios', '/catalogos/veterinarios', '♙', 'catalogs.read'],
            ['Servicios', '/catalogos/servicios', '✚', 'catalogs.read'],
            ['Especialidades', '/catalogos/especialidades', '◇', 'catalogs.read'],
        ],
    ],
    [
        'label' => 'CATÁLOGOS COMERCIALES',
        'collapsed' => true,
        'links' => [
            ['Categorías', '/catalogos/categorias', '▦', 'catalogs.read'],
            ['Marcas', '/catalogos/marcas', '◇', 'catalogs.read'],
            ['Proveedores', '/catalogos/proveedores', '▣', 'catalogs.read'],
        ],
    ],
    [
        'label' => 'ANÁLISIS Y AJUSTES',
        'links' => [
            ['Reportes', '/reportes', '◷', 'reports.read'],
            ['Usuarios', '/usuarios', '♙', 'users.manage'],
            ['Configuración', '/configuracion', '⚙', 'settings.manage'],
            ['Auditoría', '/auditoria', '≡', 'audit.read'],
        ],
    ],
];
