<?php
/*
$schema['central']['orders']['items']['api_merlion_menu'] = array(
);
*/
$schema['central']['products']['items']['api_merlion_menu'] = array(
    'attrs' => array(
        'class'=>'is-addon'
    ),
    'type' => 'title',
    'href' => 'api_merlion_products.manage',
    'position' => 1400,
    'subitems' => array(
        'api_merlion_products.managing_groups' => array(
            'href' => 'api_merlion_products.managing_groups',
            'position' => 402
        ),
        'api_merlion_products.attach_groups' => array(
            'href' => 'api_merlion_products.attach_groups',
            'position' => 403
        ),
        'api_merlion_products.import' => array(
            'href' => 'api_merlion_products.import',
            'position' => 404
        ),
    )
);
$schema['top']['addons']['items']['api_merlion_menu'] = array(
    'attrs' => array(
        'class'=>'is-addon'
    ),
    'type' => 'title',
    'href' => 'api_merlion_settings.manage',
    'position' => 1400,
    'subitems' => array(
        'api_merlion_settings.manage_handbooks' => array(
            'href' => 'api_merlion_settings.manage_handbooks',
            'position' => 400
        ),
        'api_merlion_settings.manage_values' => array(
            'href' => 'api_merlion_settings.manage_values',
            'position' => 401
        ),
        'api_merlion_settings.update_catalog_groups' => array(
            'href' => 'api_merlion_settings.update_catalog_groups',
            'position' => 402
        ),
        'api_merlion_settings.update_shipment_date' => array(
            'href' => 'api_merlion_settings.update_shipment_date',
            'position' => 403
        ),
    ),
);
$schema['central']['orders']['items']['api_merlion_menu_orders'] = array(
    'attrs' => array(
        'class'=>'is-addon'
    ),
    'type' => 'title',
    'href' => 'api_merlion_orders.manage',
    'position' => 1400,
);
return $schema;