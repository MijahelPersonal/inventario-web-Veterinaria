<?php
// Define el orden del menú y el permiso que permite ver cada acceso.
return [
    ['label' => '', 'links' => [['Inicio', '/', 'house-door', null]]],
    [
        'label' => 'GESTIÓN COMERCIAL',
        'links' => [
            ['Venta en tienda', '/tienda', 'cart-plus', 'sales.write'],
            ['Productos', '/catalogos/productos', 'box-seam', 'catalogs.read'],
            ['Inventario', '/inventario', 'boxes', 'inventory.read'],
            ['Clientes', '/catalogos/clientes', 'people', 'catalogs.read'],
            ['Ventas y cobros', '/ventas', 'receipt', 'sales.read'],
        ],
    ],
    [
        'label' => 'GESTIÓN VETERINARIA',
        'links' => [
            ['Citas / Agenda', '/citas', 'calendar-check', 'appointments.read'],
            ['Calendario', '/calendario', 'calendar3', 'appointments.read'],
            ['Mascotas / Pacientes', '/catalogos/mascotas', 'heart-pulse', 'catalogs.read'],
            ['Veterinarios', '/catalogos/veterinarios', 'person-vcard', 'catalogs.read'],
            ['Servicios', '/catalogos/servicios', 'clipboard2-pulse', 'catalogs.read'],
            ['Especialidades', '/catalogos/especialidades', 'tags', 'catalogs.read'],
        ],
    ],
    [
        'label' => 'CATÁLOGOS COMERCIALES',
        'collapsed' => true,
        'links' => [
            ['Categorías', '/catalogos/categorias', 'grid', 'catalogs.read'],
            ['Marcas', '/catalogos/marcas', 'bookmark-star', 'catalogs.read'],
            ['Proveedores', '/catalogos/proveedores', 'truck', 'catalogs.read'],
        ],
    ],
    [
        'label' => 'ANÁLISIS Y AJUSTES',
        'links' => [
            ['Reportes', '/reportes', 'bar-chart', 'reports.read'],
            ['Usuarios', '/usuarios', 'shield-check', 'users.manage'],
            ['Configuración', '/configuracion', 'gear', 'settings.manage'],
            ['Auditoría', '/auditoria', 'clock-history', 'audit.read'],
        ],
    ],
];
