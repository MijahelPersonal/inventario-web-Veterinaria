<?php
declare(strict_types=1);
// Metadatos internos: nunca se aceptan nombres de tablas o columnas del navegador.
$text = static fn(string $label, int $max = 120, bool $required = false): array => compact(
    'label',
    'max',
    'required',
) + ['type' => 'text'];
$select = static fn(string $label, string $table, bool $required = false): array => compact(
    'label',
    'table',
    'required',
) + ['type' => 'select'];
$name = $text('Nombre', 160, true);
$contact = [
    'name' => $name,
    'document' => $text('Documento', 30),
    'phone' => $text('Teléfono', 40),
    'email' => ['label' => 'Correo', 'type' => 'email', 'max' => 190],
    'address' => $text('Dirección', 255),
];
$specialty = $select('Especialidad', 'specialties');
return [
    'productos' => [
        'table' => 'products',
        'title' => 'Productos',
        'fields' => [
            'code' => $text('Código interno', 60, true),
            'barcode' => $text('Código de barras', 80),
            'name' => $name,
            'description' => ['label' => 'Descripción', 'type' => 'textarea', 'max' => 5000],
            'category_id' => $select('Categoría', 'product_categories'),
            'brand_id' => $select('Marca', 'brands'),
            'presentation' => $text('Presentación'),
            'unit' => $text('Unidad base', 20, true) + ['default' => 'unidad'],
            'price' => [
                'label' => 'Precio de venta',
                'type' => 'money',
                'required' => true,
                'default' => '0.00',
            ],
            'min_stock' => [
                'label' => 'Stock mínimo',
                'type' => 'quantity',
                'required' => true,
                'default' => '0.000',
            ],
            'web_group' => ['label' => 'Sección de la tienda web', 'type' => 'option', 'default' => 'general', 'options' => ['general' => 'General', 'alimentos' => 'Alimentos y nutrición', 'farmacia' => 'Farmacia veterinaria', 'antiparasitarios' => 'Antiparasitarios', 'accesorios' => 'Accesorios y cuidado']],
            'pet_type' => ['label' => 'Destinado a', 'type' => 'option', 'default' => 'ambos', 'options' => ['ambos' => 'Perros y gatos', 'perros' => 'Perros', 'gatos' => 'Gatos', 'otros' => 'Otras mascotas']],
            'offer_price' => ['label' => 'Precio web en oferta (S/, opcional)', 'type' => 'money'],
            'offer_from' => ['label' => 'Oferta desde (opcional)', 'type' => 'campaign_date'],
            'offer_until' => ['label' => 'Oferta hasta (inclusive, opcional)', 'type' => 'campaign_date'],
            'publish_online' => ['label' => 'Publicar en la tienda web', 'type' => 'checkbox'],
        ],
    ],
    'categorias' => [
        'table' => 'product_categories',
        'title' => 'Categorías de productos',
        'fields' => ['name' => $text('Nombre', 120, true)],
    ],
    'marcas' => [
        'table' => 'brands',
        'title' => 'Marcas',
        'fields' => ['name' => $text('Nombre', 120, true)],
    ],
    'proveedores' => ['table' => 'suppliers', 'title' => 'Proveedores', 'fields' => $contact],
    'clientes' => ['table' => 'clients', 'title' => 'Clientes', 'fields' => $contact],
    'mascotas' => [
        'table' => 'pets',
        'title' => 'Mascotas',
        'fields' => [
            'client_id' => $select('Cliente responsable', 'clients', true),
            'name' => $text('Nombre', 120, true),
            'species' => $text('Especie', 60, true),
            'breed' => $text('Raza', 80),
            'sex' => $text('Sexo', 30),
            'birth_date' => ['label' => 'Fecha de nacimiento', 'type' => 'date'],
            'weight' => ['label' => 'Peso (kg)', 'type' => 'quantity'],
            'microchip' => $text('Microchip / expediente', 80),
            'allergies' => ['label' => 'Alergias conocidas', 'type' => 'textarea', 'max' => 5000],
            'background' => [
                'label' => 'Antecedentes comunicados',
                'type' => 'textarea',
                'max' => 5000,
            ],
        ],
    ],
    'especialidades' => [
        'table' => 'specialties',
        'title' => 'Especialidades',
        'fields' => ['name' => $text('Nombre', 120, true)],
    ],
    'veterinarios' => [
        'table' => 'veterinarians',
        'title' => 'Veterinarios',
        'fields' => [
            'name' => $name,
            'specialty_id' => $specialty,
            'license_number' => $text('Colegiatura', 60),
            'phone' => $text('Teléfono', 40),
            'email' => ['label' => 'Correo', 'type' => 'email', 'max' => 190],
        ],
    ],
    'servicios' => [
        'table' => 'services',
        'title' => 'Servicios',
        'fields' => [
            'name' => $name,
            'description' => ['label' => 'Descripción', 'type' => 'textarea', 'max' => 5000],
            'specialty_id' => $specialty,
            'price' => [
                'label' => 'Precio',
                'type' => 'money',
                'required' => true,
                'default' => '0.00',
            ],
            'duration_minutes' => [
                'label' => 'Duración (minutos)',
                'type' => 'duration',
                'required' => true,
                'default' => 30,
            ],
        ],
    ],
];
