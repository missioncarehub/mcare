<?php

return [
    'disk' => env('OFFICIAL_DOCUMENT_DISK', 'local'),
    'template_version' => env('OFFICIAL_DOCUMENT_TEMPLATE_VERSION', '2.0'),
    'download_name_prefix' => env('OFFICIAL_DOCUMENT_PREFIX', 'MCARE'),
    'batch_export_expiry_hours' => (int) env('OFFICIAL_DOCUMENT_EXPORT_EXPIRY_HOURS', 24),
    // auto and fpdf both use PHP/FPDF so COTC stays landscape even when Chrome/Node are installed.
    'pdf_engine' => env('OFFICIAL_DOCUMENT_PDF_ENGINE', 'fpdf'),

    'browsershot' => [
        'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
        'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
    ],

    'organization' => [
        'name' => env('MCARE_ORGANIZATION_NAME', 'Mission Care Training Center'),
        'address' => env('MCARE_ORGANIZATION_ADDRESS', 'San Isidro Poblacion, Pili, Camarines Sur'),
        'phone' => env('MCARE_ORGANIZATION_PHONE', '09298202898'),
        'trainer_name' => env('MCARE_TRAINER_SIGNATORY', 'Maricris N. Collao'),
        'registrar_name' => env('MCARE_REGISTRAR_SIGNATORY', 'Salvacion A. Collao'),
        'course_hours' => (int) env('MCARE_CAREGIVING_HOURS', 786),
    ],
];
