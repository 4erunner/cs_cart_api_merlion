<?php
/*
*/

// Подключение класса Registry

use Tygh\Registry; 
use Tygh\ApiMerlion;
use Tygh\ApiMerlionLogger;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if(defined('CONSOLE')) {
    $cron_password = Settings::instance()->getSettingDataByName('api_merlion_import_cron_password');
    if(!empty($cron_password['value']) && !empty($_REQUEST['cron_password'])){
        if($cron_password['value']!=$_REQUEST['cron_password']){
            echo("\nwrong password!\n\n");
            exit(0);
        }
    }
}

$GLOBALS['api_merlion_logger_orders'] = $logger = new ApiMerlionLogger('api_merlion_orders');

// Секция POST

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'get_or_create_order') {
        $logger = $GLOBALS['api_merlion_logger_orders']->instance('mode:get_or_create_order');
        if(defined('AJAX_REQUEST')){
            $settings = fn_api_merlion_settings();
            $note = Settings::instance()->getSettingDataByName('api_merlion_order_note');
            $order_id = Settings::instance()->getSettingDataByName('api_merlion_order_id');
            $order_id = $order_id ? $order_id['value'] : '';
            $order_found = false;
            if(!empty($note['value'])){
                $order_found = fn_api_merlion_check_order($order_id, $note['value']);
                if($order_found){
                    $logger->message('found order', $order_found);
                    Settings::instance()->updateValue('api_merlion_order_id', $order_found) ;
                    Tygh::$app['ajax']->assign('new_order', $order_found);
                    fn_set_notification('N', __('notice'), __('api_merlion_orders.order_found', array('[order]' => $order_found)));
                }
                else{
                    $logger->message('no order found, try create new');
                    $order_id = fn_api_merlion_order_create($settings, $note['value']);
                    $logger->message('get new order', $order_id);
                    if($order_id){
                        fn_set_notification('W', __('important'),__("api_merlion_orders.order_created", array('[order]'=>$order_id)));
                        Settings::instance()->updateValue('api_merlion_order_id', $order_id);
                        Tygh::$app['ajax']->assign('new_order', $order_id);                         
                    }
                    else{
                        fn_set_notification('E', __('error'), __('api_merlion_errors.no_data'));
                    }
                }
            }
            else{
                fn_set_notification('E', __('notice'), __('api_merlion_settings.order_not').' - '.__('api_merlion_tooltip.order_note'));
            }
        }
    }
    elseif ($mode == 'delete_order') {
        $logger = $GLOBALS['api_merlion_logger_orders']->instance('mode:delete_order');
        if(defined('AJAX_REQUEST')){
            $settings = fn_api_merlion_settings();
            $order_id = Settings::instance()->getSettingDataByName('api_merlion_order_id');
            fn_print_r($order_id);
            if(fn_api_merlion_order_delete($order_id['value'])){
                fn_set_notification('N', __('notice'),'Order:' .$order_id['value']. ' deleted' );
                Settings::instance()->updateValue('api_merlion_order_id', '');
                Tygh::$app['ajax']->assign('new_order', '');
            }
        }
    }
    
}

// Секция GET

if ($mode == 'manage') {
    $logger = $GLOBALS['api_merlion_logger_orders']->instance("mode:manage");
    
    if(ApiMerlion::connect()){
        $settings = fn_api_merlion_settings();
        
        if(fm_api_merlion_check_order_ident($settings, $settings['api_merlion_order_note'])){
            
            if(empty($_REQUEST['action'])){
                $logger->message('get request: ', $_REQUEST);
                $clear_oreder_list = array();
                $order_list = ApiMerlion::getOrdersList();
                $logger->message('get order list:', $order_list);
                if($order_list){
                    if(array_key_exists('document_no', $order_list)){
                        $order_list = array($order_list);
                    }
                    $logger->message('get orders: ', $order_list);
                    foreach($order_list as $order){
                        if($order['Contact'] == $settings['api_merlion_order_note']){
                            $clear_oreder_list[] = $order;
                        }
                    }
                }
                $logger->message('found orders:', $clear_oreder_list);
                Tygh::$app['view']->assign('level', 0);
                Tygh::$app['view']->assign('action', "manage");
                Tygh::$app['view']->assign('list', $clear_oreder_list);
            }
            elseif($_REQUEST['action'] == "products"){
                if(!empty($_REQUEST['order_id'])){
                    $logger->message('get request: ', $_REQUEST);
                    $order_lines = ApiMerlion::getOrderLines($_REQUEST['order_id'], 0);
                    $logger->message('Items in order: ', $order_lines);
                    if(array_key_exists('item_no', $order_lines)){
                        $order_lines = array($order_lines);
                    }
                    if(count($order_lines) == 1){
                        if(empty($order_lines[0]['item_no'])){
                            $order_lines = array();
                        }
                    }
                    $product_codes = array();
                    foreach($order_lines as $key => $item){
                        if(!empty($item['item_no'])){
                            $product_codes[] = $item['item_no'];
                        }
                    }
                    if($order_lines && $product_codes){
                        $fields = array(
                            '?:products.product_id',
                            '?:products.product_code',
                            '?:product_descriptions.product',
                            '?:api_merlion_order_product.status'
                        );
                        $join = db_quote(" LEFT JOIN ?:product_descriptions ON ?:products.product_id = ?:product_descriptions.product_id AND 
                            ?:product_descriptions.lang_code = ?s ", CART_LANGUAGE);
                        $join .= db_quote(" LEFT JOIN ?:api_merlion_order_product ON  ?:api_merlion_order_product.product_id = ?:products.product_id AND 
                            ?:api_merlion_order_product.api_merlion_order = ?s ", $_REQUEST['order_id']);
                        $products_names = db_get_hash_array("SELECT ".implode(' , ',$fields)." FROM ?:products ?p WHERE ?:products.product_code IN (?a)", 'product_code', $join, $product_codes);
                        $logger->message('Get names for items: ', $products_names);
                        if($products_names){
                            foreach($order_lines as $key => $item){
                                if(array_key_exists($item['item_no'],$products_names)){
                                    $order_lines[$key]['name'] = $products_names[$item['item_no']]['product'];
                                    $order_lines[$key]['product_id'] = $products_names[$item['item_no']]['product_id'];
                                    $order_lines[$key]['status'] = $products_names[$item['item_no']]['status'];
                                }
                            }
                        }
                    }
                    $logger->message('Updated Items in order: ', $order_lines);
                    Tygh::$app['view']->assign('level', 1);
                    Tygh::$app['view']->assign('action', "products");
                    Tygh::$app['view']->assign('group_id', $_REQUEST['order_id']); 
                    Tygh::$app['view']->assign('list', $order_lines);                    
                }
            }
            elseif($_REQUEST['action'] == "market_orders"){
                
                if(!empty($_REQUEST['order_id']) && !empty($_REQUEST['product_code'])){
                    $logger->message('request', $_REQUEST);
                    $fields = array(
                        '?:api_merlion_order_product.amount',
                        '?:api_merlion_order_product.order_date',
                        '?:api_merlion_order_product.order_id',
                        '?:orders.timestamp'
                    );
                    $join = db_quote(" LEFT JOIN ?:orders ON ?:orders.order_id = ?:api_merlion_order_product.order_id ");
                    $market_order_lines = db_get_array("SELECT ".implode(" , ", $fields)." FROM ?:api_merlion_order_product ?p WHERE ?:api_merlion_order_product.product_code = ?s AND ?:api_merlion_order_product.api_merlion_order = ?s ", $join, $_REQUEST['product_code'], $_REQUEST['order_id']);
                    $logger->message(false, $market_order_lines);
                    Tygh::$app['view']->assign('level', 2);
                    Tygh::$app['view']->assign('action', "market_orders");
                    Tygh::$app['view']->assign('group_id', $_REQUEST['product_code']); 
                    Tygh::$app['view']->assign('list', $market_order_lines); 
                }
            }            
        }
        else{
            fn_set_notification('E', __('error'), __('api_merlion_errors.no_data'));
        }
    }
    else{
        fn_set_notification('E', __('error'), ApiMerlion::$error);
    }
}
elseif ($mode == 'order_check_products'){
    $logger = $GLOBALS['api_merlion_logger_orders']->instance('order_check_products');
    try{
        if(defined('AJAX_REQUEST')){
            $order_info = fn_get_order_info($_REQUEST['order_id'], false, true, true, false);
            $logger->message('get shop order: ',$order_info);
            foreach ($order_info['products'] as $k => $v) {
                $order_info['products'][$k]['main_pair'] = fn_get_cart_product_icon(
                    $v['product_id'], $order_info['products'][$k]
                );
            }
            if(ApiMerlion::connect()){
                $settings = fn_api_merlion_settings();
                if($order_info){
                    $check_codes = array();
                    $products = array();
                    foreach ($order_info['products'] as $product_key=>$product){
                        $check_codes[]=$product['product_code'];
                        $products[$product['product_code']] = $product;
                    }
                    if($check_codes){
                        $page = NULL;
                        $count_rows = NULL;
                        $logger->message("settings parameters:", array('items' => $check_codes, 'api_merlion_product_available' => $settings['api_merlion_product_available'], 'api_merlion_shipment_method' => $settings['api_merlion_shipment_method'], 'api_merlion_shipment_date' => $settings['api_merlion_shipment_date']));
                        $check_products = fn_api_merlion_get_products('', $check_codes, "0", $settings['api_merlion_shipment_method'], $settings['api_merlion_shipment_date'], '', $page, $count_rows);
                        $logger->message('status order: ', $check_products);
                        $logger->message('get products from API', $check_products);
                        foreach($check_products as $product_code=>$product){
                            $check_order_status = db_get_field('SELECT `status` FROM ?:api_merlion_order_product WHERE order_id = ?i AND product_id = ?i', $order_info['order_id'], $products[(string)$product_code]['product_id']);
                            $logger->message('status order: ', $check_order_status);
                            if($check_order_status != 'R' && $check_order_status != 'P'){
                                $data  = array(
                                    'order_id' => $order_info['order_id'],
                                    'product_id' => $products[(string)$product_code]['product_id'],
                                    'product_code' => (string)$product_code,
                                    'amount' => 0,
                                    'order_date' => fn_api_merlion_create_date(NULL, 'Y-m-d H:i:s'),
                                    'order_price' => $product['PriceClientRUB'],
                                    'order_available' => $product['AvailableClient'],
                                    'status' => ((int)$product['AvailableClient'] >= (int)$products[(string)$product_code]['amount']) && ((int)$product['PriceClientRUB'] < (int)$products[(string)$product_code]['original_price']) ? ((int)$product['Online_Reserve'] != 2 ? 'A' : "W") : 'N' ,
                                );
                                $logger->message('prepare data for order: ', $data);
                                $check_order  = db_get_field('SELECT count(*) FROM ?:api_merlion_order_product WHERE order_id = ?i AND product_id = ?i', $order_info['order_id'], $products[(string)$product_code]['product_id']);
                                if ((int)$check_order == 0){
                                    $logger->message('insert data: ', db_query('INSERT INTO ?:api_merlion_order_product ?e', $data));
                                }
                                else{
                                    $logger->message('update data: ', db_query('UPDATE ?:api_merlion_order_product SET ?u WHERE order_id = ?i AND product_id = ?i',array_slice($data, 2), $order_info['order_id'], $products[(string)$product_code]['product_id']));
                                }                                
                            }
                        }
                    }
                }
                $check_product = db_get_hash_array('SELECT product_id, status, message, order_date, order_price, order_available, amount FROM ?:api_merlion_order_product WHERE order_id = ?i ', 'product_id',  $_REQUEST['order_id']);
                Tygh::$app['view']->assign('api_merlion_order_products_status', $check_product);
            }
            Tygh::$app['view']->assign('order_info',$order_info);
            Tygh::$app['view']->display('design/backend/templates/addons/api_merlion/views/api_merlion_orders/order_products.tpl');
        }
        
    }
    catch(Exception  $e){
        fn_set_notification('E', __('error'), $e->getMessage());
        $logger->message($e->getMessage(), $e);
    }
}
elseif($mode == 'order_add_product'){
    $logger = $GLOBALS['api_merlion_logger_orders']->instance('order_add_product');
    try{
        if(defined('AJAX_REQUEST')){
            if(ApiMerlion::connect()){
                if(!empty($_REQUEST['order_id']) && !empty($_REQUEST['product_id'])){
                    $order_info = fn_get_order_info($_REQUEST['order_id'], false, true, true, false);
                    $logger->message('get shop order: ',$order_info);
                    foreach ($order_info['products'] as $k => $v) {
                        $order_info['products'][$k]['main_pair'] = fn_get_cart_product_icon(
                            $v['product_id'], $order_info['products'][$k]
                        );
                    }
                    $api_order_product = db_get_row('SELECT product_code, status, message, order_date, order_price, order_available, amount FROM ?:api_merlion_order_product WHERE order_id = ?i AND product_id = ?i', $_REQUEST['order_id'], $_REQUEST['product_id']);
                    $logger->message('get api order: ',$api_order_product);
                    if($api_order_product && $order_info){
                        fn_api_merlion_check_and_update_order($order_info, $api_order_product, 'ADD');
                        $check_product = db_get_hash_array('SELECT product_id, status, message, order_date, order_price, order_available, amount FROM ?:api_merlion_order_product WHERE order_id = ?i ', 'product_id',  $order_info['order_id'] );
                        Tygh::$app['view']->assign('api_merlion_order_products_status', $check_product); 
                    }
                    else{
                        fn_set_notification('E', __('error'), __('api_merlion_errors.no_data'));
                    }
                    Tygh::$app['view']->assign('order_info',$order_info);
                    Tygh::$app['view']->display('design/backend/templates/addons/api_merlion/views/api_merlion_orders/order_products.tpl');
                }
                else{
                    fn_set_notification('E', __('error'), __('api_merlion_errors.no_data'));
                }
            }
        }
        
    }
    catch(Exception  $e){
        fn_set_notification('E', __('error'), $e->getMessage());
        $logger->message($e->getMessage(), $e);
    }
}
elseif($mode == 'order_add_all_products'){
    $logger = $GLOBALS['api_merlion_logger_orders']->instance('order_add_all_products');
    try{
        if(defined('AJAX_REQUEST')){
            if(ApiMerlion::connect()){
                if(!empty($_REQUEST['order_id'])){
                    $order_info = fn_get_order_info($_REQUEST['order_id'], false, true, true, false);
                    $logger->message('get shop order: ',$order_info);
                    foreach ($order_info['products'] as $k => $v) {
                        $order_info['products'][$k]['main_pair'] = fn_get_cart_product_icon(
                            $v['product_id'], $order_info['products'][$k]
                        );
                    }
                    if(ApiMerlion::connect()){
                        $settings = fn_api_merlion_settings();
                        if($order_info){
                            $check_codes = array();
                            $products = array();
                            foreach ($order_info['products'] as $product_key=>$product){
                                $check_codes[]=$product['product_code'];
                                $products[$product['product_code']] = $product;
                            }
                            if($check_codes){
                                $page = NULL;
                                $count_rows = NULL;
                                $logger->message("settings parameters:", array('items' => $check_codes, 'api_merlion_product_available' => $settings['api_merlion_product_available'], 'api_merlion_shipment_method' => $settings['api_merlion_shipment_method'], 'api_merlion_shipment_date' => $settings['api_merlion_shipment_date']));
                                $check_products = fn_api_merlion_get_products('', $check_codes, "0", $settings['api_merlion_shipment_method'], $settings['api_merlion_shipment_date'], '', $page, $count_rows);
                                $logger->message('get products from API', $check_products);
                                foreach($check_products as $product_code=>$product){
                                    $check_order_status = db_get_field('SELECT `status` FROM ?:api_merlion_order_product WHERE order_id = ?i AND product_id = ?i', $order_info['order_id'], $products[(string)$product_code]['product_id']);
                                    $logger->message('status order: ', $check_order_status);
                                    if($check_order_status != 'R' && $check_order_status != 'P'){
                                        $data  = array(
                                            'order_id' => $order_info['order_id'],
                                            'product_id' => $products[(string)$product_code]['product_id'],
                                            'product_code' => (string)$product_code,
                                            'amount' => 0,
                                            'order_date' => fn_api_merlion_create_date(NULL, 'Y-m-d H:i:s'),
                                            'order_price' => $product['PriceClientRUB'],
                                            'order_available' => $product['AvailableClient'],
                                            'status' => ((int)$product['AvailableClient'] >= (int)$products[(string)$product_code]['amount']) && ((int)$product['PriceClientRUB'] < (int)$products[(string)$product_code]['original_price']) ? ((int)$product['Online_Reserve'] != 2 ? 'A' : "W") : 'N',
                                        );
                                        $logger->message('prepare data for order: ', $data);
                                        $check_order  = db_get_field('SELECT count(*) FROM ?:api_merlion_order_product WHERE order_id = ?i AND product_id = ?i', $order_info['order_id'], $products[(string)$product_code]['product_id']);
                                        if ((int)$check_order == 0){
                                            $logger->message('insert data: ', db_query('INSERT INTO ?:api_merlion_order_product ?e', $data));
                                        }
                                        else{
                                            $logger->message('update data: ', db_query('UPDATE ?:api_merlion_order_product SET ?u WHERE order_id = ?i AND product_id = ?i',array_slice($data, 2), $order_info['order_id'], $products[(string)$product_code]['product_id']));
                                        }
                                        if(!empty($data)){
                                            $logger->message('reserv product: ', $data);
                                            if($data['status'] == 'A'){
                                                sleep(1);
                                                fn_api_merlion_check_and_update_order($order_info, $data, 'ADD');
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $check_product = db_get_hash_array('SELECT product_id, status, message, order_date, order_price, order_available, amount FROM ?:api_merlion_order_product WHERE order_id = ?i ', 'product_id',  $order_info['order_id'] );
                    Tygh::$app['view']->assign('api_merlion_order_products_status', $check_product);
                    Tygh::$app['view']->assign('order_info',$order_info);
                    Tygh::$app['view']->display('design/backend/templates/addons/api_merlion/views/api_merlion_orders/order_products.tpl');
                }
                else{
                    fn_set_notification('E', __('error'), __('api_merlion_errors.no_data'));
                }
            }
        }
        
    }
    catch(Exception  $e){
        fn_set_notification('E', __('error'), $e->getMessage());
        $logger->message($e->getMessage(), $e);
    }
}
elseif($mode == 'order_remove_product'){
    $logger = $GLOBALS['api_merlion_logger_orders']->instance('order_remove_product');
    try{
        if(defined('AJAX_REQUEST')){
            if(ApiMerlion::connect()){
                if(!empty($_REQUEST['order_id']) && !empty($_REQUEST['product_id'])){
                    $order_info = fn_get_order_info($_REQUEST['order_id'], false, true, true, false);
                    $logger->message('get shop order: ',$order_info);
                    foreach ($order_info['products'] as $k => $v) {

                        if (!$downloads_exist && !empty($v['extra']['is_edp']) && $v['extra']['is_edp'] == 'Y') {
                            $downloads_exist = true;
                        }

                        $order_info['products'][$k]['main_pair'] = fn_get_cart_product_icon(
                            $v['product_id'], $order_info['products'][$k]
                        );
                    }
                    $api_order_product = db_get_row('SELECT product_code, status, message, order_date, order_price, order_available, amount FROM ?:api_merlion_order_product WHERE order_id = ?i AND product_id = ?i', $_REQUEST['order_id'], $_REQUEST['product_id']);
                    $logger->message('get api order: ',$api_order_product);
                    if($api_order_product && $order_info){
                        fn_api_merlion_check_and_update_order($order_info, $api_order_product, 'DEL');
                        $check_product = db_get_hash_array('SELECT product_id, status, message, order_date, order_price, order_available, amount FROM ?:api_merlion_order_product WHERE order_id = ?i ', 'product_id',  $order_info['order_id'] );
                        Tygh::$app['view']->assign('api_merlion_order_products_status', $check_product); 
                    }
                    else{
                        fn_set_notification('E', __('error'), __('api_merlion_errors.no_data'));
                    }
                    Tygh::$app['view']->assign('order_info',$order_info);
                    Tygh::$app['view']->display('design/backend/templates/addons/api_merlion/views/api_merlion_orders/order_products.tpl');
                }
                else{
                    fn_set_notification('E', __('error'), __('api_merlion_errors.no_data'));
                }
            }
        }
        
    }
    catch(Exception  $e){
        fn_set_notification('E', __('error'), $e->getMessage());
        $logger->message($e->getMessage(), $e);
    }
}

function fn_api_merlion_check_order($order_id = '', $comment = ''){
    $logger = $GLOBALS['api_merlion_logger_orders']->instance(__FUNCTION__);
    if(ApiMerlion::connect()){
        $order_found = false;
        if($order_id){
            $order_check = ApiMerlion::getOrdersList($order_id);
            $logger->message('found order', $order_check);
            if(!empty($order_check['document_no'])){
                if($order_check['Status'] == '-'){
                    $order_found = true;
                    $order_id = $order_check['document_no'];
                }
            }
            if(!$order_found){
                $order_id = false;
                $logger->message('not found order', $order_id);
            }  
        }
        if(!$order_found){
            $order_check_list = ApiMerlion::getOrdersList();
            if($order_check_list){
                if(array_key_exists('document_no', $order_check_list)){
                    $order_check_list = array($order_check_list);
                }
                $logger->message('get order for check', $order_check_list);
                foreach($order_check_list as $order){
                    if($order['Contact'] == $comment && $order['Status'] == '-'){
                        $order_found = true;
                        $order_id = $order['document_no'];
                        break;
                    }
                }
            }
        }
    }
    else{
        fn_set_notification('E', __('notice'), ApiMerlion::$error);
    }
    $logger->message('order number: ', $order_id);
    return $order_id;
}

function fn_api_merlion_order_create($settings = NULL, $comment = ''){
    $logger = $GLOBALS['api_merlion_logger_orders']->instance(__FUNCTION__);
    $result = False;
    if(ApiMerlion::connect()){
        if(!$settings){
            $settings = fn_api_merlion_settings();
        }
        # 05.10.2016
        # update API Merlion - change function setOrderHeaderCommand
        # && !empty($settings['api_merlion_representative']) 
        # В связи с тем что коды контрагентов поменялись – функция getRepresentative теперь не может получить представителей.
        # Как оказалось они не критичны при создании заказа, но по факту функция не рабочая.
        if(!empty($settings['api_merlion_shipment_date']) && !empty($settings['api_merlion_shipment_method'])  && !empty($settings['api_merlion_counter'])  && !empty($settings['api_merlion_shipment_agent']) && !empty($settings['api_merlion_endpoint_delivery_id'])  && !empty($settings['api_merlion_packing_type'])){
            $logger->message('get settings for create order', func_get_args());
            $command = ApiMerlion::setOrderHeaderCommand(
                '', 
                $settings['api_merlion_shipment_method'], 
                $settings['api_merlion_shipment_date'], 
                $settings['api_merlion_counter'],
                $settings['api_merlion_shipment_agent'],
                '',
                !empty($comment) ? $coment : $settings['api_merlion_order_note'],
                $settings['api_merlion_representative'],
                $settings['api_merlion_endpoint_delivery_id'],
                $settings['api_merlion_packing_type']
            );
            $logger->message('execute [setOrderHeaderCommand] witch command number: ', $command);
            $result = fn_api_merlion_check_command($command);
            $logger->message('fn_api_merlion_check_command return:', $result);
            if($result){
                $result = $result['DocumentNo'];
            }
        }
        else{
            fn_set_notification('E', __('notice'), __('api_merlion_errors.no_data'). ' ' . __("api_merlion_settings.manage_handbooks"));            
        }
    }
    else{
        fn_set_notification('E', __('notice'), ApiMerlion::$error);
    }
    return $result;
}

function fn_api_merlion_order_delete($order_id){
    $logger = $GLOBALS['api_merlion_logger_orders']->instance(__FUNCTION__);
    $result = false;
    if(ApiMerlion::connect()){
        $logger->message('execute [setDeleteOrderCommand] for order: ', $order_id);
        $command = ApiMerlion::setDeleteOrderCommand($order_id);
        $logger->message('execute [setDeleteOrderCommand] witch command number: ', $command);
        $result = fn_api_merlion_check_command($command);
    }
    else{
        fn_set_notification('E', __('notice'), ApiMerlion::$error);
    }
    return $result;
}

function fn_api_merlion_update_order_line($order_product, $settings, $type = ''){
    $logger = $GLOBALS['api_merlion_logger_orders']->instance(__FUNCTION__);
    $exst = false;
    $result = false;
    $order_id = $settings['api_merlion_order_id'];
    if(ApiMerlion::connect()){
        $logger->message('try [getOrderLines]');
        if(!empty($order_product['api_merlion_order'])){
            if($order_product['api_merlion_order'] != $settings['api_merlion_order_id']){
                $order_id = $order_product['api_merlion_order'];
            }
        }
        $order_lines = ApiMerlion::getOrderLines($order_id, 0);
        $logger->message('[getOrderLines] result:', $order_lines);
        if(array_key_exists('item_no', $order_lines)){
            $order_lines = array($order_lines);
        }
        foreach($order_lines as $line){
            if($line['item_no'] == $order_product['product_code']){
                $exst = $line;
                break;
            }
        }
        switch ($type) {
            case 'ADD' :
                if($exst){
                    $order_product['amount'] = $exst['qty'] + $order_product['amount'];
                }
                $exst = true;
                break;
            case 'DEL' :
                if($exst){
                    $order_product['amount'] = $exst['qty'] - $order_product['amount'];
                    $exst = true;
                }
                else{
                    fn_set_notification('E', __('notice'), _('api_merlion_errors.order_no_product'));
                }
                break;
            default :
                $logger->message('TYPE update wrong! Must be "ADD" or "DEL"', func_get_args());
                fn_set_notification('E', __('notice'), __('api_merlion_errors.order_update_wrong_type'));
                $exst = false;
        }        
        if($exst){
            $logger->message('try [setOrderLineCommand] add product to order', func_get_args());
            $command = ApiMerlion::setOrderLineCommand(
                $order_id,
                $order_product['product_code'],
                $order_product['amount'],
                0.0
            );
            $logger->message('execute [setOrderLineCommand] witch command number: ', $command); 
            $result = fn_api_merlion_check_command($command);
        }
    }
    else{
        fn_set_notification('E', __('notice'), ApiMerlion::$error);
    }
    return $result;
}

function fn_api_merlion_check_command($command){
    $logger = $GLOBALS['api_merlion_logger_orders']->instance(__FUNCTION__);
    $result = false;
    if(ApiMerlion::connect()){
        $logger->message('try [getCommandResult] check command: '.$command);
        $result = ApiMerlion::getCommandResult($command);
        $logger->message('result [getCommandResult]: ', $result);
        if($result){
            if($result['ProcessingResult'] == "Ошибка" || $result['ProcessingResult'] == "Отменено"){
                $logger->message('execute return error', $result['ErrorText']);
                fn_set_notification('E', __('notice'), $result['ErrorText']);
                ApiMerlion::$error = $result['ErrorText'];
                $result = false;
            }
        }
    }
    else{
        fn_set_notification('E', __('notice'), ApiMerlion::$error);
    }
    return $result;
}

function fn_api_merlion_check_and_update_order($order_info, $api_order_product, $type = ''){
    $logger = $GLOBALS['api_merlion_logger_orders']->instance(__FUNCTION__);
    $logger->message('get args: ', func_get_args());
    $settings = fn_api_merlion_settings();
    $exec = false;
    switch ($type) {
        case 'ADD' :
            if(fn_api_merlion_get_date($api_order_product['order_date'], 'Y-m-d H:i:s', true) > time()-300){
                $exec = true;
            }
            else{
                $logger->message('Last status checked at: '.$api_order_product['order_date'].' > Status expired!');
                fn_set_notification('E', __('warning'), __('api_merlion_orders.product_status_expired'));
            }
            if($exec){
                if($api_order_product['status'] == 'A' || $api_order_product['status'] == 'W'){
                    $exec = true;
                }
                else{
                    $logger->message('Status product: '.$api_order_product['status'].' > Must be "A" or "W"');
                    fn_set_notification('E', __('warning'), __('api_merlion_orders.product_status_wrong'));
                    $exec = false;
                }                            
            }
            break;
        case 'DEL' :
            if($api_order_product['status'] == 'R' || $api_order_product['status'] == 'P' ){
                $exec = true;
            }
            else{
                $logger->message('Status product: '.$api_order_product['status'].' > Must be "R" or "P"');
                fn_set_notification('E', __('warning'), __('api_merlion_orders.product_status_wrong'));
                $exec = false;
            } 
            break;
    }
    if($exec){
        $api_order_id = false;
        $api_order_id = fn_api_merlion_check_order($settings['api_merlion_order_id'], $settings['api_merlion_order_note']);
        if(!$api_order_id){
            if(!empty($settings['api_merlion_order_auto_create']) && $settings['api_merlion_order_auto_create'] == 'Y'){
                $api_order_id = fn_api_merlion_order_create($settings, $note['value']);
                if($api_order_id){
                    Settings::instance()->updateValue('api_merlion_order_id', $api_order_id);
                    $settings = fn_api_merlion_settings();
                    fn_set_notification('W', __('important'),__("api_merlion_orders.order_created", array('[order]'=>$api_order_id)));
                }                                    
            }
        }
        else{
            $logger->message('found order', $api_order_id);
            Settings::instance()->updateValue('api_merlion_order_id', $api_order_id) ;
            fn_set_notification('N', __('notice'), __('api_merlion_orders.order_found', array('[order]' => $api_order_id)));
        }
        if($api_order_id){
            foreach($order_info['products'] as $key => $product){
                switch ($type) {
                    case 'ADD' :
                        $amount = $product['amount'];
                        break;
                    case 'DEL' :
                        $amount = $api_order_product['amount'];
                        break;
                    default :
                        $amount = 0;
                }
                if($product['product_code'] == $api_order_product['product_code']){
                    $order_product = array(
                        'product_id' => $product['product_id'],
                        'product_code' => $api_order_product['product_code'],
                        'amount' => $amount,
                        'order_id' => $order_info['order_id'],
                        'api_merlion_order' => $api_order_id,
                        'message' => ''
                    );
                    break;
                }
            }
            if (!empty($order_product)){
                $result = fn_api_merlion_update_order_line($order_product, $settings, $type);
                if($result){
                    switch ($type) {
                        case 'ADD' :
                            $order_product['status'] = $api_order_product['status'] == 'W' ? 'P' : 'R';
                            $order_product_sec = $order_product;
                            $order_product_sec['_amount_'] = $order_product_sec['amount'];
                            fn_set_notification('W', __('important'), __("api_merlion_orders.add_to_order", $order_product_sec));
                            break;
                        case 'DEL' :
                            $order_product['status'] = '';
                            $order_product_sec = $order_product;
                            $order_product_sec['_amount_'] = $order_product_sec['amount'];
                            fn_set_notification('W', __('important'), __("api_merlion_orders.del_to_order", $order_product_sec));
                            break;
                        default :
                            $order_product['status'] = '';
                            $order_product_sec = $order_product;
                            $order_product_sec['_amount_'] = $order_product_sec['amount'];
                            fn_set_notification('E', __('error'), __('api_merlion_errors.no_data'));
                    }
                }
                elseif(ApiMerlion::$error){
                    $order_product['status'] = 'E';
                    $order_product['amount'] = 0;
                    $order_product['message'] = ApiMerlion::$error;
                }
                $logger->message('update api order: ',db_query('UPDATE ?:api_merlion_order_product SET ?u WHERE order_id = ?i AND product_id = ?s', $order_product, $order_info['order_id'] ,$order_product['product_id']));
            }
        }
        else{
            fn_set_notification('E', __('warning'), __("api_merlion_settings.order_id").' - '.__('no'));
        }                              
    }    
}

function fn_api_merlion_get_products($cat_id, $item_id, $available, $shipment_method, $shipment_date, $last_time_change, &$page, &$count_rows){
    $logger = $GLOBALS['api_merlion_logger_orders']->instance(__FUNCTION__);
    $result = array();
    foreach($item_id as $id){
        $result[$id] = array(
                'No' => $id,
                'AvailableClient' => 0,
                'PriceClientRUB' => 0,
                'Online_Reserve' => 0, 
            );
    }
    try{
        $values = ApiMerlion::getItems($cat_id, $item_id, $shipment_method, $page? $page : 0, 500, $last_time_change);
        if($values && !array_key_exists('No', $values)){
            foreach($values as $key => $value){
                if(!empty($value['No'])){
                    $result[$value['No']] = $value + $result[$value['No']];
                }
                else{
                    $logger->message("Not found key [No]", $values);
                    //break;
                }
            }            
        }
        elseif($values){
            if(!empty($values['No'])){
                $result[$values['No']] = $values + $result[$values['No']];
            }
        }
    }
    catch(Exception $e){
        $logger->message($e, func_get_args());
    }
    if(!empty($result)){
        try{
            $values = ApiMerlion::getItemsAvail('', $shipment_method, $shipment_date, $available, array_keys($result));
            if($values && !array_key_exists('No', $values)){
                 foreach($values as $key => $value){
                    if(!empty($value['No'])){
                        $result[$value['No']] = $value + $result[$value['No']];
                    }
                    else{
                        $logger->message("Not found key [No]" , $values);
                        //break;
                    }
                }           
            }
            elseif($values){
                if(!empty($values['No'])){
                    $result[$values['No']] = $values + $result[$values['No']];
                }
            }
        }
        catch(Exception $e){
            $logger->message($e, func_get_args());
        }         
    }
    return $result;
}

function fm_api_merlion_check_order_ident($settings, $ident){
    return preg_match('/.*\|'.$ident.'/i', $settings['api_merlion_user']);
}

// function fn_api_merlion_get_shipment_dates($settings, $with_method = true){
    // $logger = $GLOBALS['api_merlion_logger_orders']->instance(__FUNCTION__);
    // $dates = ApiMerlion::getShipmentDates("", $settings['api_merlion_shipment_method'] && $with_method ? $settings['api_merlion_shipment_method'] : "");
    // $logger->message("getShipmentDates:", $dates);
    // if(count($dates) == 1){
        // $dates = array($dates);
    // }
    // $index = count($dates)-1;
    // try{
        // if(isset($_REQUEST['date_index'])){
            // if(preg_match ('/^[0-9]+$/', $_REQUEST['date_index'], $matches)){
                // $index = (int)$_REQUEST['date_index'];
            // }
        // }
    // }catch(Exception $e){
         // error_log($e->getMessage());
         // fn_set_notification('E', __('notice'), $e->getMessage());
    // }
    // if($index > count($dates)-1){
        // $index = count($dates)-1;
    // }
    // elseif($index < 0){
        // $index = count($dates)-1;
    // }
    // Settings::instance()->updateValue('api_merlion_shipment_date', $dates[$index]['Date']);
    // if($dates[$index]['Date']){
        // fn_set_notification('N', __('notice'), __('api_merlion_settings.updated_shipment_date').$dates[$index]['Date']);
    // }else{
        // fn_set_notification('E', __('error'), 'Dates is NULL');
        // fn_set_notification('E', __('notice'), 'get Dates without method');
        // fn_api_merlion_get_shipment_dates($settings, false);
    // }
// }
