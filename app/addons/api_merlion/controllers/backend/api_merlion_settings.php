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

$GLOBALS['api_merlion_logger_settings'] = $logger = new ApiMerlionLogger('api_merlion_settings');

// Секция POST

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    fn_trusted_vars('api_merlion_settings');
    
    if ($mode == 'm_update') {
        if (isset($_REQUEST['api_merlion_settings'])) {
            foreach ($_REQUEST['api_merlion_settings'] as $name => $value) {
                        Settings::instance()->updateValue($name, $value);
            }
        }
        if(strpos($_SERVER['HTTP_REFERER'], 'manage_handbooks')){
            return array(CONTROLLER_STATUS_OK, 'api_merlion_settings.update_shipment_date');
        }
        elseif(strpos($_SERVER['HTTP_REFERER'], 'manage_values')){
            return array(CONTROLLER_STATUS_OK, 'api_merlion_settings.manage_values');
        }
        
    }
    elseif($mode == 'u_update'){
        $values_schema = fn_get_schema('api_merlion', 'update_settings_values');
        if(in_array($_REQUEST['name'], $values_schema['values'])){
            Settings::instance()->updateValue($_REQUEST['name'], $_REQUEST['value']? "Y" : "N");
        }
        else{
            fn_set_notification('E', __('notice'),  __('api_merlion_errors.no_data'));
        }
    }
   
}

// Секция GET

if ($mode == 'manage') {
    return array(CONTROLLER_STATUS_REDIRECT, 'api_merlion_settings.manage_handbooks');
}
elseif($mode == 'manage_handbooks'){
    $logger = $GLOBALS['api_merlion_logger_settings']->instance('mode:manage_handbooks');
    if(ApiMerlion::connect()){
        $settings = fn_api_merlion_settings();
        $handbooks = array();
        $handbooks['shipment_method'] = ApiMerlion::getShipmentMethods();
        $logger->message('execute [getShipmentMethods] ', $handbooks['shipment_method']);
        $handbooks['shipment_agent'] = ApiMerlion::getShipmentAgents();
        $logger->message('execute [getShipmentAgents] ', $handbooks['shipment_agent']);
        $handbooks['counter'] = ApiMerlion::getCounterAgent();
        $logger->message('execute [getCounterAgent] ', $handbooks['counter']);
        if(!empty($settings['api_merlion_counter'])){
            $handbooks['api_merlion_representative'] = ApiMerlion::getRepresentative($settings['api_merlion_counter']);
            $logger->message('execute [getRepresentative] ', $handbooks['api_merlion_representative']);
            $check_response = false;
            foreach($handbooks['api_merlion_representative'] as $key=>$value){
                if($value){
                    $check_response = true;
                }
            }
            if(!$check_response){
                $handbooks['api_merlion_representative'] = array();
            }
        }
        $handbooks['api_merlion_endpoint_delivery_id'] = ApiMerlion::getEndPointDelivery('','');
        $logger->message('execute [getEndPointDelivery] ', $handbooks['api_merlion_endpoint_delivery_id']);
        $handbooks['api_merlion_packing_type'] = ApiMerlion::getPackingTypes('');
        $logger->message('execute [getPackingTypes] ', $handbooks['api_merlion_packing_type']);
        if(ApiMerlion::$status){
            Tygh::$app['view']->assign('api_merlion_settings', $handbooks);
            Tygh::$app['view']->assign('current_api_merlion_settings', $settings);
        }
        else{
            fn_set_notification('E', __('notice'), ApiMerlion::$error);
        }
    }
    else{
        fn_set_notification('E', __('notice'), ApiMerlion::$error);
    }
}
elseif($mode == 'manage_values'){
    if(ApiMerlion::connect()){
        if(ApiMerlion::$status){
            Tygh::$app['view']->assign('cart_languages', CART_LANGUAGE);
            Tygh::$app['view']->assign('languages', fn_get_translation_languages(true));
            Tygh::$app['view']->assign('current_api_merlion_settings', fn_api_merlion_settings()+array("api_merlion_logging_dir" => $logger::$dir));
            
        }
        else{
            fn_set_notification('E', __('notice'), ApiMerlion::$error);
        }
    }
    else{
        fn_set_notification('E', __('notice'), ApiMerlion::$error);
    }
}
elseif($mode == 'update_catalog_groups'){
    if(ApiMerlion::connect()){
		$logger = $GLOBALS['api_merlion_logger_settings']->instance($mode);
        $merlion_groups = ApiMerlion::getCatalog();
        if($merlion_groups){
            db_query('UPDATE ?:api_merlion_groups SET ?:api_merlion_groups.check=?i WHERE 1', 0);
            $inserted = 0;
            foreach($merlion_groups as $key => $group){
                $values = array(
                    'group_id' => $group['ID'],
                    'group_pid' => $group['ID_PARENT'],
                    'name' => $group['Description'],
                    'check' => 1,
                );
                if(db_query('INSERT INTO ?:api_merlion_groups ?e ON DUPLICATE KEY UPDATE ?u',$values, $values)){
                    $inserted++;
                }
            }
            $updated = db_query('UPDATE ?:api_merlion_groups SET ?u WHERE ?:api_merlion_groups.check=?i ', array('status'=>'D') , 0);
            fn_set_notification('N', __('notice'), __('api_merlion_notice.groups_update').$inserted);
            if($updated){
                fn_set_notification('W', __('important'), __('api_merlion_notice.groups_disable'));
                $fields = array(
                    '?:api_merlion_groups.id',
                    '?:api_merlion_groups.name',
                    '?:category_descriptions.category',
                );
                $join = db_quote("LEFT JOIN ?:category_descriptions ON ?:api_merlion_groups.category_id = ?:category_descriptions.category_id");
                foreach(db_get_hash_array("SELECT ". implode(',', $fields) ." FROM ?:api_merlion_groups ?p WHERE ?:api_merlion_groups.status = ?s and ?:api_merlion_groups.check=?i", 'id', $join, 'D', 0 ) as $key => $value){
                    if(!empty((string)$value['category'])){
						fn_set_notification('W', __('important'), __('api_merlion_notice.groups_disable').' '.(string)$value['name'].' => '.__('category').': '.(string)$value['category']);
						$logger->message(__('important'), __('api_merlion_notice.groups_disable').' '.(string)$value['name'].' => '.__('category').': '.(string)$value['category'], false, true);
					}
                }
            }
        }
        else{
            fn_set_notification('E', __('notice'), __('api_merlion_errors.no_data'));            
        }
    }
    else{
        fn_set_notification('E', __('notice'), ApiMerlion::$error);
    }
}
elseif($mode == 'update_shipment_date'){
    if(ApiMerlion::connect()){
        $settings = fn_api_merlion_settings();
        fn_api_merlion_get_shipment_dates($settings);
    }
    Tygh::$app['view']->assign('current_api_merlion_settings', fn_api_merlion_settings());
}
elseif($mode == 'change_counter'){
    $logger = $GLOBALS['api_merlion_logger_settings']->instance('mode:change_counter');
    if(ApiMerlion::connect()){
        if(!empty($_REQUEST['counter'])){
            $logger->message('get counter: ', $_REQUEST['counter']);
            $handbooks['api_merlion_representative'] = ApiMerlion::getRepresentative($_REQUEST['counter']);
            $logger->message('execute [getRepresentative] ', $handbooks['api_merlion_representative']);
            $check_response = false;
            foreach($handbooks['api_merlion_representative'] as $key=>$value){
                if($value){
                    $check_response = true;
                }
            }
            if($check_response){
                Tygh::$app['view']->assign('api_merlion_settings', $handbooks);
            }
            else{
                Tygh::$app['view']->assign('api_merlion_settings',  array());
            }
        }
        else{
            
        }
    }
}


function fn_api_merlion_get_shipment_dates($settings, $with_method = true){
    $logger = $GLOBALS['api_merlion_logger_settings'];
    $dates = ApiMerlion::getShipmentDates("", $settings['api_merlion_shipment_method'] && $with_method ? $settings['api_merlion_shipment_method'] : "");
    $logger->message("getShipmentDates:", $dates);
    if(count($dates) == 1){
        $dates = array($dates);
    }
    $index = count($dates)-1;
    try{
        if(isset($_REQUEST['date_index'])){
            if(preg_match ('/^[0-9]+$/', $_REQUEST['date_index'], $matches)){
                $index = (int)$_REQUEST['date_index'];
            }
        }
    }catch(Exception $e){
         error_log($e->getMessage());
         fn_set_notification('E', __('notice'), $e->getMessage());
    }
    if($index > count($dates)-1){
        $index = count($dates)-1;
    }
    elseif($index < 0){
        $index = count($dates)-1;
    }
    Settings::instance()->updateValue('api_merlion_shipment_date', $dates[$index]['Date']);
    if($dates[$index]['Date']){
        fn_set_notification('N', __('notice'), __('api_merlion_settings.updated_shipment_date').$dates[$index]['Date']);
    }else{
        fn_set_notification('E', __('error'), 'Dates is NULL');
        fn_set_notification('E', __('notice'), 'get Dates without method');
        fn_api_merlion_get_shipment_dates($settings, false);
    }
}