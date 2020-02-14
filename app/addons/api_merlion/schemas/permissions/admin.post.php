<?php
$schema['api_merlion_products'] = array(
    'modes' => array(
        'manage' => array(
            'permissions' => 'manage_api_merlion'
        ),
        'managing_groups' => array(
            'permissions' => 'manage_api_merlion'
        ),
        'attach_groups' => array(
            'permissions' => 'manage_api_merlion'
        ),
        'import' => array(
            'permissions' => 'manage_api_merlion'
        ),
        'change_group_comparison' => array(
            'permissions' => 'manage_api_merlion'
        ),
        'change_group_list_price' => array(
            'permissions' => 'manage_api_merlion'
        ),
        'del_filters' => array(
            'permissions' => 'manage_api_merlion'
        ), 
        'get_product' => array(
            'permissions' => 'manage_api_merlion'
        ),
        'check_product' => array(
            'permissions' => 'manage_api_merlion_product'
        ),
        'order_product' => array(
            'permissions' => 'manage_api_merlion_product'
        ),  
    ),
    //'permissions' => array ('GET' => 'manage_api_merlion', 'POST' => 'manage_api_merlion'),
);
$schema['api_merlion_settings'] = array(
    'modes' => array(
        'manage' => array(
            'permissions' => 'manage_api_merlion'
        ),
        'manage_handbooks' => array(
            'permissions' => 'manage_api_merlion'
        ),
        'manage_values' => array(
            'permissions' => 'manage_api_merlion'
        ),
        'update_catalog_groups' => array(
            'permissions' => 'manage_api_merlion'
        ),
        'update_shipment_date' => array(
            'permissions' => 'manage_api_merlion'
        ),
    ),
    //'permissions' => array ('GET' => 'manage_api_merlion', 'POST' => 'manage_api_merlion'),
);
$schema['api_merlion_orders'] = array(
    'modes' => array(
        'manage' => array(
            'permissions' => 'manage_api_merlion'
        ),
    ),
);
$schema['tools']['modes']['update_status']['param_permissions']['table']['api_merlion_products'] = 'manage_api_merlion';
$schema['tools']['modes']['update_status']['param_permissions']['table']['api_merlion_settings'] = 'manage_api_merlion';
return $schema;