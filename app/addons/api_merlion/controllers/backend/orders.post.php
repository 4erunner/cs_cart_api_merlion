<?php
 /*
*/

use Tygh\Registry; 
use Tygh\ApiMerlion;
use Tygh\ApiMerlionLogger;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }
$settings = fn_api_merlion_settings();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if ($mode == 'details') {
    Registry::set('navigation.tabs.api_merlion', array(
        'title' => __('api_merlion_menu'),
        'js' => true
    ));
    if(!empty($_REQUEST['order_id'])){
        $check_product = db_get_hash_array('SELECT product_id, status, message, order_date, order_price, order_available, amount FROM ?:api_merlion_order_product WHERE order_id = ?i ', 'product_id',  $_REQUEST['order_id']);
        Tygh::$app['view']->assign('api_merlion_order_products_status', $check_product);
    }
    
    
}
