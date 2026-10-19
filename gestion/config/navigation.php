<?php
// Define el orden del menú y el permiso que permite ver cada acceso.
return [
    ['label' => '', 'links' => [['Dashboard', '/', 'speedometer2', null]]],
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
        ],
    ],
    [
        'label' => 'REPORTES',
        'links' => [['Reportes', '/reportes', 'bar-chart', 'reports.read']],
    ],
    [
        'label' => 'ADMINISTRACIÓN',
        'links' => [
            ['Usuarios', '/usuarios', 'shield-check', 'users.manage'],
            ['Configuración', '/configuracion', 'gear', 'settings.manage'],
        ],
    ],
];
