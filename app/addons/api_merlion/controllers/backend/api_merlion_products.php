<?php
/*
*/

// Подключение класса Registry

use Tygh\Bootstrap;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Tools\Url;
use Tygh\ApiMerlion;
use Tygh\ApiMerlionLogger;
use Tygh\Settings;
use Tygh\Enum\ProductFeatures;
use Tygh\Mailer;
use Tygh\Ym\Yml2;

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


$GLOBALS['api_merlion_logger_products'] = $logger = new ApiMerlionLogger('api_merlion_products');
// hack - get import and export function
$real_mode = $mode;
$real_metode = $_SERVER['REQUEST_METHOD'];
$real_request = $_REQUEST;
$REQUEST = NULL;
$_SERVER['REQUEST_METHOD'] = '%%%';
$mode = '%%%';
$config_dir = Registry::get('config.dir');
try {
    require_once($config_dir['root'].'/app/controllers/backend/exim.php');
} catch (Exception $e) {
    $logger->messqge('Error load Export/Import Module: ' . $e->getMessage());
}
try{
    require_once($config_dir['root'].'/app/controllers/backend/product_filters.php');
}
catch (Exception $e) {
    $logger->messqge('Error load Product Filters Module: ' . $e->getMessage());
}
try{
    require_once($config_dir['root'].'/app/addons/yml_export/config.php');
    require_once($config_dir['root'].'/app/addons/yml_export/controllers/frontend/yml.php');
}
catch (Exception $e) {
    $logger->messqge('Error load Yandex YML Export Module: ' . $e->getMessage());
}
$mode = $real_mode;
$_SERVER['REQUEST_METHOD'] = $real_metode;
$_REQUEST = $real_request;

// Секция POST

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update_status') {
        $result = false;
        if (!preg_match("/^[a-z_]+$/", $_REQUEST['table'])) {
            return false;
        }
        if (!empty($_REQUEST['id']) && !empty($_REQUEST['status'])) {
            $parent_status = db_get_field('SELECT t2.status FROM ?:api_merlion_groups as t1 left join ?:api_merlion_groups as t2 on t2.group_id = t1.group_pid WHERE t1.group_id = ?s', $_REQUEST['id']);
            if($parent_status === 'A' || $parent_status === null){
               $result = fn_change_status_groups($_REQUEST); 
            }
            
        }
        if ($result) {
            fn_set_notification('N', __('notice'), __('status_changed'));
            Tygh::$app['ajax']->assign('update_ids', $_REQUEST['id']);
            Tygh::$app['ajax']->assign('update_status', $_REQUEST['status']);
        }
        else {
            fn_set_notification('E', __('error'), __('error_status_not_changed'));
        }
    }
    elseif($mode == 'm_import'){
        $logger = $GLOBALS['api_merlion_logger_products']->instance('m_import');
        $logger->message('get request', $_REQUEST);
        if(!empty($_REQUEST['api_merlion_import'])){
            @ignore_user_abort(1);
            ini_set('auto_detect_line_endings', true);
            @set_time_limit(0);
            $dir = implode(DIRECTORY_SEPARATOR, array(rtrim(fn_get_files_dir_path(),'/'),'api_merlion'));
            fn_mkdir($dir);
            fn_set_progress('parts', 3);
            fn_set_progress('echo', 'Import...'  , true);
            $offline = Settings::instance()->getSettingDataByName('api_merlion_import_offline')['value'] == 'Y' ? true : false ;
            $logger->message('offline mode', $offline);
            if(ApiMerlion::connect() || $offline){
                $settings = fn_api_merlion_settings();
                $update_time = time();
                $file_path = implode(DIRECTORY_SEPARATOR, array($dir,$_REQUEST['api_merlion_import'].'_'.implode('_',array((string)$update_time, (string)uniqid())).'.csv'));
                $chose_categories=array();
                $all_chose_categories=array();
                if(!empty($_REQUEST["api_merlion_import_category_id"])){
                    $chose_categories[]=$_REQUEST["api_merlion_import_category_id"];
                }
                elseif(!empty($_REQUEST["api_merlion_import_category_ids"])){
                    $chose_categories=explode(',',$_REQUEST["api_merlion_import_category_ids"]);
                }
                if($chose_categories){
                    fn_api_merlion_get_subcategory($chose_categories, $all_chose_categories);
                }
                $logger->message("Chosen categories:", $all_chose_categories);
                switch($_REQUEST['api_merlion_import']){
                    case "items":
                        $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_items_update');
                        # $GLOBALS['api_merlion_logger_products'] = $logger = new ApiMerlionLogger(implode('_',array('api_merlion_products',$_REQUEST['api_merlion_import'])));
                        $clear_to = false;
                        # 2017.03.17
                        # added [api_merlion_import_force] - for force update
                        if((empty($check_update['value']) || $check_update['value'] == 'N') || !empty($_REQUEST['api_merlion_import_force'])){
                            try{
                                Settings::instance()->updateValue('api_merlion_last_items_update', $update_time);
                                Settings::instance()->updateValue('api_merlion_last_items_update_stop', ' / #'.(string)getmypid());
                                $extra_fields = array();
                                $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'start');
                                $logger->message("settings: ".(string)var_export($settings, true));
                                if(array_key_exists('api_merlion_import_extra', $_REQUEST)){
                                    $extra_fields = array_filter($_REQUEST['api_merlion_import_extra'][$_REQUEST['api_merlion_import']]);
                                    Settings::instance()->updateValue('api_merlion_import_items_extra', serialize($extra_fields));
                                } else {
                                    $extra_fields = @unserialize($settings['api_merlion_import_items_extra']);
                                }
                                if(!empty($settings['api_merlion_shipment_date']) && !empty($settings['api_merlion_shipment_method'])){
                                    Settings::instance()->updateValue('api_merlion_import_items_update', 'Y');
                                    if(empty($_REQUEST['api_merlion_import_items'])){
                                        $_REQUEST['api_merlion_import_items'] = null;
                                    }
                                    if(count(db_get_fields("SELECT `id` FROM ?:api_merlion_products WHERE `Language` != '' OR `Language` IS NOT NULL")) == 0){
                                        $_REQUEST['api_merlion_import_items'] = 'categories_items';
                                        fn_set_notification('E', __('error'), __('api_merlion_errors.no_products_update'));
                                    }

                                    // akkom product clear
                                    $logger->message('truncate table api_merlion_akkom_product:', db_query("TRUNCATE TABLE ?:api_merlion_akkom_product"));

                                    if($_REQUEST['api_merlion_import_items'] == 'local_items'){
                                        $logger = $GLOBALS['api_merlion_logger_products']->instance('m_import|items|local_items');
                                        $execute = true;
                                        if(!$offline){
                                            $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'get items...');
                                            $logger->message("settings parameters:", array('api_merlion_product_available' => $settings['api_merlion_product_available'], 'api_merlion_shipment_method' => $settings['api_merlion_shipment_method'], 'api_merlion_shipment_date' => $settings['api_merlion_shipment_date']));
                                            $groups_products = fn_api_merlion_get_active_products(array('?:products.product_code'), $all_chose_categories);
                                            $logger->message('get active products: '.(string)count($groups_products));
                                            $groups_products = array_chunk($groups_products, 500);
                                            foreach($groups_products as $group_products){
                                                fn_set_progress('echo','get items...'  , false);
                                                $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_items_update');
                                                if(empty($check_update['value']) || $check_update['value'] == 'N'){
                                                    $execute = false;
                                                    break;
                                                }
                                                $to_table_values = array(
                                                    'check' => $update_time,
                                                );
                                                $count_rows = 0;
                                                $page = 0;
                                                fn_api_merlion_save_products(fn_api_merlion_get_products('', $group_products, $settings['api_merlion_product_available']=="Y" ? "1" : "0", $settings['api_merlion_shipment_method'], $settings['api_merlion_shipment_date'], '', $page, $count_rows), $to_table_values, db_get_fields("SELECT group_id FROM ?:api_merlion_groups WHERE list_price = ?i", 1));
                                            }
                                            $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'clear items: '.(string)fn_api_merlion_clear_unchecked($update_time, $settings));
                                                                                
                                        }
                                        if($execute){
                                            fn_set_progress('echo', 'create file import', true);
                                            $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " . 'create file import');
                                            $import_schema = fn_get_schema('import', 'active_products');
                                            fn_api_merlion_create_products_file($file_path, $update_time, $import_schema, $extra_fields, $settings, !empty($_REQUEST['api_merlion_import_items_quantity']) ? true : false, $all_chose_categories);
                                            $pattern = fn_get_pattern_definition('products', 'import');
                                            if (($data = fn_get_csv($pattern, $file_path, $import_schema['import_options'])) != false) {
                                                $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_items_update');
                                                if(empty($check_update['value']) || $check_update['value'] == 'Y'){
                                                    try{
                                                        fn_set_progress('echo', $logger->message('start import...'), true);
                                                        fn_import($pattern, $data, $import_schema['import_options']);
                                                        fn_set_progress('echo', $logger->message('stop import...'), true);
                                                    }
                                                    catch(Exception $e){
                                                        $logger->message($e);
                                                    }
                                                }
                                            }
                                            else{
                                                $logger->message('Error on import!');
                                            }
                                            if($settings['api_merlion_delete_import_file'] == 'Y'){
                                                fn_rm($file_path);
                                            }
                                        }

                                    }
                                    elseif($_REQUEST['api_merlion_import_items'] == 'categories_items'){
                                        $logger = $GLOBALS['api_merlion_logger_products']->instance('m_import|items|categories_items');
                                        $execute = true;
                                        if(!$offline){
                                            $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'get items...');
                                            $logger->message("settings parameters:", array('api_merlion_product_available' => $settings['api_merlion_product_available'], 'api_merlion_shipment_method' => $settings['api_merlion_shipment_method'], 'api_merlion_shipment_date' => $settings['api_merlion_shipment_date']));
                                            $groups = fn_api_merlion_get_active_groups($all_chose_categories);
                                            $logger->message('get active groups: '.(string)count($groups));
                                            foreach($groups as $group){
                                                $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_items_update');
                                                if(empty($check_update['value']) || $check_update['value'] == 'N'){
                                                    $execute = false;
                                                    break;
                                                }
                                                if($group['group_id']){
                                                    $logger->message('group: '.$group['group_id'], $group);
                                                    $categories = db_get_hash_single_array('SELECT ?:categories.category_id, ?:category_descriptions.category FROM ?:categories LEFT JOIN ?:category_descriptions ON ?:categories.category_id = ?:category_descriptions.category_id  WHERE ?:categories.category_id IN (?n)', array('category_id', 'category'), explode('/', $group['id_path']));
                                                    $category = str_replace('/', '///', $group['id_path']);
                                                    foreach($categories as $key => $value){
                                                        $category = str_replace($key, $value, $category);
                                                    }       
                                                    $to_table_values = array(
                                                        'Category' =>  $category,
                                                        'Comparison' => $group['comparison'] ? 'Y' : 'N',
                                                        'PartnumberName' => $group['partnumber_name'] ? 'Y' : 'N',
                                                        'Language' => empty($settings['api_merlion_product_language']) ? CART_LANGUAGE : $settings['api_merlion_product_language'],
                                                        'check' => $update_time,
                                                    );
                                                    $while = true;
                                                    $page = 1;
                                                    $rows_on_page = $count_rows = 500;
                                                    $products_count = 0;
                                                    while ($while) {
                                                        fn_set_progress('echo','get items...' , false);
                                                        if($count_rows == $rows_on_page){
                                                            fn_api_merlion_save_products(fn_api_merlion_get_products($group['group_id'], '', $settings['api_merlion_product_available']=="Y" ? "1" : "0", $settings['api_merlion_shipment_method'], $settings['api_merlion_shipment_date'], '', $page, $count_rows), $to_table_values, db_get_fields("SELECT group_id FROM ?:api_merlion_groups WHERE list_price = ?i", 1));
                                                            $products_count +=$count_rows;
                                                            $page++;
                                                        }else{
                                                            $while = false;
                                                        }
                                                    }
                                                }
                                            }
                                            $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'clear items: '.(string)fn_api_merlion_clear_unchecked($update_time, $settings));
                                        }
                                        if($execute){
                                            fn_set_progress('echo', 'create file import', true);
                                            $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'create file import');
                                            $import_schema = fn_get_schema('import', 'category_products');
                                            fn_api_merlion_create_products_file($file_path, $update_time, $import_schema, $extra_fields, $settings, !empty($_REQUEST['api_merlion_import_items_quantity']) ? true : false, $all_chose_categories);
                                            $pattern = fn_get_pattern_definition('products', 'import');
                                            if (($data = fn_get_csv($pattern, $file_path, $import_schema['import_options'])) != false) {
                                                $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_items_update');
                                                if(empty($check_update['value']) || $check_update['value'] == 'Y'){
                                                    try{
                                                        fn_set_progress('echo', $logger->message('start import...'), true);
                                                        fn_import($pattern, $data, $import_schema['import_options']);
                                                        fn_set_progress('echo', $logger->message('stop import...'), true);
                                                    }
                                                    catch(Exception $e){
                                                        $logger->message($e);
                                                    }
                                                }
                                            }
                                            else{
                                                $logger->message('Error on import!');
                                            }
                                            if($settings['api_merlion_delete_import_file'] == 'Y'){
                                                fn_rm($file_path);
                                            }
                                        }
                                    }

                                    // akkom source
                                    $akkom_source_patch = fn_get_files_dir_path()."import_akkom_products.csv";
                                    if(file_exists($akkom_source_patch) && is_readable($akkom_source_patch)) {
                                        $file_path = implode(DIRECTORY_SEPARATOR, array($dir,$_REQUEST['api_merlion_import'].'_'.implode('_',array((string)$update_time, (string)uniqid())).'.csv'));
                                        $logger = $GLOBALS['api_merlion_logger_products']->instance('m_import|items|akkom_items');
                                        $execute = true;
                                        $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'get items...');
                                        $to_table_values = array(
                                            'Language' => empty($settings['api_merlion_product_language']) ? CART_LANGUAGE : $settings['api_merlion_product_language'],
                                            'check' => $update_time,
                                        );

                                        $csv = fn_api_merlion_csv_to_array($akkom_source_patch, "\t");
                                        if(is_array($csv)) {

                                            $update_products = fn_api_merlion_save_akkom_products($csv, $to_table_values);

                                            if(isset($extra_fields['YML Sales notes'])) {
                                                unset($extra_fields['YML Sales notes']);
                                            }
                                            if (isset($update_products) && is_array($update_products) && count($update_products) > 0) {

                                                foreach ($update_products as $key => $up_product) {
                                                    db_query('INSERT INTO ?:api_merlion_akkom_product
                (`product_code`)
                VALUES(?s)', $up_product);
                                                }

                                                fn_api_merlion_nulled_akkom_products($settings, $update_products);
                                                fn_api_merlion_nulled_action_products($settings, $update_products);

                                                $import_schema_ap = fn_get_schema('import', 'akkom_category_products');
                                                $akkom_product_file_path = implode(DIRECTORY_SEPARATOR, array($dir,'akkom_products_'.implode('_',array((string)$update_time, (string)uniqid())).'.csv'));
                                                $extra_fields['Status'] = 'A';
                                                fn_api_merlion_create_products_file($akkom_product_file_path, $update_time, $import_schema_ap, $extra_fields, $settings, true , array(), 'A');

                                                $pattern = fn_get_pattern_definition('products', 'import');
                                                if (($data = fn_get_csv($pattern, $akkom_product_file_path, $import_schema_ap['import_options'])) != false) {
                                                    try{
                                                        fn_set_progress('echo', $logger->message('start akkom product import...'), true);
                                                        fn_import($pattern, $data, $import_schema_ap['import_options']);
                                                        fn_set_progress('echo', $logger->message('stop import...'), true);
                                                        if($settings['api_merlion_delete_import_file'] == 'Y'){
                                                            fn_rm($akkom_product_file_path);
                                                        }
                                                    }
                                                    catch(Exception $e){
                                                        $logger->message($e);
                                                    }
                                                }
                                                else{
                                                    $logger->message('Error on import!');
                                                }

                                                /*
                                                $import_schema_af = fn_get_schema('import', 'features');
                                                $akkom_features_file_path = implode(DIRECTORY_SEPARATOR, array($dir,'akkom_features_'.implode('_',array((string)$update_time, (string)uniqid())).'.csv'));
                                                fn_api_merlion_create_features_file($akkom_features_file_path, $update_time, $import_schema_af, $settings, $update_products);

                                                $pattern = fn_get_pattern_definition('products', 'import');
                                                if (($data = fn_get_csv($pattern, $akkom_features_file_path, $import_schema_af['import_options'])) != false) {
                                                    $categories = fn_api_merlion_features_clear_category();
                                                    try{
                                                        fn_set_progress('echo', $logger->message('start akkkom features import...'), true);
                                                        fn_import($pattern, $data, $import_schema_af['import_options']);
                                                        fn_set_progress('echo', $logger->message('stop import...'), true);
                                                        if($settings['api_merlion_delete_import_file'] == 'Y'){
                                                            fn_rm($akkom_features_file_path);
                                                        }
                                                    }
                                                    catch(Exception $e){
                                                        $logger->message($e);
                                                    }
                                                    fn_api_merlion_features_set_category($categories);
                                                }
                                                else{
                                                    $logger->message('Error on import!');
                                                }


                                                $import_schema_ai = fn_get_schema('import', 'images');
                                                $akkom_images_file_path = implode(DIRECTORY_SEPARATOR, array($dir,'akkom_images_'.implode('_',array((string)$update_time, (string)uniqid())).'.csv'));
                                                fn_api_merlion_create_images_file($akkom_images_file_path, $update_time, $import_schema_ai, $settings, $update_products);

                                                $pattern = fn_get_pattern_definition('product_images', 'import');
                                                if (($data = fn_get_csv($pattern, $akkom_images_file_path, $import_schema_ai['import_options'])) != false) {
                                                    try{
                                                        fn_set_progress('echo', $logger->message('start akkom images import...'), true);
                                                        fn_import($pattern, $data, $import_schema_ai['import_options']);
                                                        fn_set_progress('echo', $logger->message('stop import...'), true);

                                                        if($settings['api_merlion_delete_import_file'] == 'Y'){
                                                            fn_rm($akkom_images_file_path);
                                                        }
                                                    }
                                                    catch(Exception $e){
                                                        $logger->message($e);
                                                    }
                                                }
                                                else{
                                                    $logger->message('Error on import!');
                                                }
                                                */
                                            }
                                        }
                                    }

                                    // ^ akkom source

                                    $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_items_update');
                                    if((empty($check_update['value']) || $check_update['value'] == 'Y')){
                                        $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'nulled updated products without image and not availble: '.(string)fn_api_merlion_nulled_products($settings, $update_time));
                                        if (empty($all_chose_categories)) {
                                            $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'nulled all not updated products: '.(string)fn_api_merlion_nulled_products_on_all($settings, $update_time));
                                        }

                                    }


                                    if(!empty(trim($settings['api_merlion_yml_export_code']))){
                                        fn_set_progress('echo','YML generate...' , true);
                                        fn_api_merlion_yml_generate($settings['api_merlion_yml_export_code']);
                                    }                               
                                    # fn_clear_cache();


                                    Settings::instance()->updateValue('api_merlion_import_items_update', 'N');
                                    $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'stop');
                                    Settings::instance()->updateValue('api_merlion_last_items_update_stop', time());

                                }
                                else{
                                    fn_set_notification('E', __('error'), __('api_merlion_errors.no_data').": ".__('api_merlion_settings.shipment_date').", ".__('api_merlion_settings.shipment_method'));
                                }                                
                            }
                            catch(Exception $e){
                                Settings::instance()->updateValue('api_merlion_import_items_update', 'N');
                                Settings::instance()->updateValue('api_merlion_last_items_update_stop', time());
                                error_log($logger->message($e));
                            }
                        }
                        else{
                            fn_set_notification('E', __('error'), __('api_merlion_notice.process_update_running'));
                        }
                        break;
                    case "features":
                        $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_features_update');
                        # $GLOBALS['api_merlion_logger_products'] = $logger = new ApiMerlionLogger(implode('_',array('api_merlion_products',$_REQUEST['api_merlion_import'])));
                        if(empty($check_update['value']) || $check_update['value'] == 'N'){
                            try{
                                Settings::instance()->updateValue('api_merlion_last_features_update', $update_time);
                                Settings::instance()->updateValue('api_merlion_last_features_update_stop', ' / #'.(string)getmypid());
                                $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'start');
                                Settings::instance()->updateValue('api_merlion_import_features_update', 'Y');
                                if(!empty($_REQUEST['api_merlion_import_features_period'])){
                                    if($_REQUEST['api_merlion_import_features_period'] == 'A' ){
                                        $_REQUEST['api_merlion_import_features'] = 'local_items';
                                    }
                                }
                                if(empty($_REQUEST['api_merlion_import_features'])){
                                    $_REQUEST['api_merlion_import_features'] = null;
                                }
                                if(count(db_get_fields("SELECT `id` FROM ?:api_merlion_products WHERE `Language` != '' OR `Language` IS NOT NULL")) == 0){
                                    $_REQUEST['api_merlion_import_features'] = null;
                                    fn_set_notification('E', __('error'), __('api_merlion_errors.no_products_update'));
                                }
                                if($_REQUEST['api_merlion_import_features'] == 'local_items'){
                                    $logger = $GLOBALS['api_merlion_logger_products']->instance('m_import|features|local_items');
                                    $execute = true;
                                    if(!$offline){
                                        $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'get items...');
                                        $groups_products = fn_api_merlion_get_active_products(array('?:products.product_code'), $all_chose_categories);
                                        $logger->message('get active products: '.(string)count($groups_products));
                                        $groups_products = array_chunk($groups_products, 500); 
                                        foreach($groups_products as $group_products){
                                            $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_features_update');
                                            if(empty($check_update['value']) || $check_update['value'] == 'N'){
                                                $execute = false;
                                                break;
                                            }
                                            
                                            $to_table_values = array(
                                                'check' => $update_time,
                                            );
                                            $while = true;
                                            $page = 1;
                                            $rows_on_page = $count_rows = 5000;
                                            $features = 0;
                                            while ($while) {
                                                fn_set_progress('echo','get items...'  , false);
                                                if($count_rows == $rows_on_page){
                                                    fn_api_merlion_save_features(fn_api_merlion_get_features('', $group_products, $page, '', $count_rows), $to_table_values);
                                                    $features += $count_rows;
                                                    $page++;
                                                }else{
                                                    $while = false;
                                                }
                                            }
                                            $logger->message("features: ".(string)$features );
                                        }                                    
                                    }
                                    if($execute){
                                        fn_set_progress('echo', 'create file import', true);
                                        $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'create file import');
                                        $import_schema = fn_get_schema('import', 'features');
                                        fn_api_merlion_create_features_file($file_path, $update_time, $import_schema, $settings);
                                        $pattern = fn_get_pattern_definition('products', 'import');
                                        if (($data = fn_get_csv($pattern, $file_path, $import_schema['import_options'])) != false) {
                                            $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_features_update');
                                            if(empty($check_update['value']) || $check_update['value'] == 'Y'){
                                                $categories = fn_api_merlion_features_clear_category();
                                                try{
                                                    fn_set_progress('echo', $logger->message('start import...'), true);
                                                    fn_import($pattern, $data, $import_schema['import_options']);
                                                    fn_set_progress('echo', $logger->message('stop import...'), true);
                                                }
                                                catch(Exception $e){
                                                    $logger->message($e);
                                                }
                                                fn_api_merlion_features_set_category($categories);
                                            }
                                        }
                                        else{
                                            $logger->message('Error on import!');
                                        }
                                        $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_features_update');
                                        if(empty($check_update['value']) || $check_update['value'] == 'Y'){
                                            fn_set_progress('echo', 'Sorting...', false);
                                            fn_api_merlion_features_sorting();
                                        }
                                        if($settings['api_merlion_delete_import_file'] == 'Y'){
                                            fn_rm($file_path);
                                        }
                                    }                            
                                }
                                elseif($_REQUEST['api_merlion_import_features'] == 'period_items'){
                                    $logger = $GLOBALS['api_merlion_logger_products']->instance('m_import|features|period_items');
                                    if(!empty($_REQUEST['api_merlion_import_features_time_from']) || !empty($_REQUEST['api_merlion_import_features_period'])){
                                        if(!empty($_REQUEST['api_merlion_import_features_period'])){
                                            list($_REQUEST['api_merlion_import_features_time_from'], $clear_to) = fn_create_periods(array("period" => $_REQUEST['api_merlion_import_features_period']));
                                        }
                                        if(preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $_REQUEST['api_merlion_import_features_time_from']) || $clear_to){
                                            if($clear_to){
                                                $last_time_change = fn_api_merlion_create_date($_REQUEST['api_merlion_import_features_time_from']);
                                            }else{
                                                $last_time_change = fn_api_merlion_create_date(fn_api_merlion_get_date($_REQUEST['api_merlion_import_features_time_from'], "d/m/Y"));
                                            }
                                            $execute = true;
                                            if(!$offline){
                                                $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'get items...');
                                                $groups_products = fn_api_merlion_get_active_products_conditions(array(" LEFT JOIN ?:api_merlion_features  ON ?:api_merlion_features.`No` = ?:products.product_code " => " ?:api_merlion_features.`No` IS NULL "), $all_chose_categories);
                                                $logger->message('get products without features: '.(string)count($groups_products));
                                                $groups_products = array_chunk($groups_products, 500);
                                                foreach($groups_products as $group_products){
                                                    $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_features_update');
                                                    if(empty($check_update['value']) || $check_update['value'] == 'N'){
                                                        $execute = false;
                                                        break;
                                                    }
                                                    
                                                    $to_table_values = array(
                                                        'check' => $update_time,
                                                    );
                                                    $while = true;
                                                    $page = 1;
                                                    $rows_on_page = $count_rows = 5000;
                                                    $features = 0;
                                                    while ($while) {
                                                        fn_set_progress('echo','get items...'  , false);
                                                        if($count_rows == $rows_on_page){
                                                            fn_api_merlion_save_features(fn_api_merlion_get_features('', $group_products, $page, '', $count_rows), $to_table_values);
                                                            $features += $count_rows;
                                                            $page++;
                                                        }else{
                                                            $while = false;
                                                        }
                                                    }
                                                    $logger->message("features: ".(string)$features );
                                                }  
                                                $groups = fn_api_merlion_get_active_groups($all_chose_categories);
                                                $logger->message('get active groups: '.(string)count($groups));
                                                foreach($groups as $group){
                                                    $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_features_update');
                                                    if(empty($check_update['value']) || $check_update['value'] == 'N'){
                                                        $execute = false;
                                                        break;
                                                    }
                                                    if($group['group_id']){
                                                        $to_table_values = array(
                                                            'check' => $update_time,
                                                        );
                                                        $while = true;
                                                        $page = 1;
                                                        $rows_on_page = $count_rows = 5000;
                                                        $features = 0;
                                                        while ($while) {
                                                            fn_set_progress('echo','get items...' , false);
                                                            if($count_rows == $rows_on_page){
                                                                fn_api_merlion_save_features(fn_api_merlion_get_features($group['group_id'], '', $page, $last_time_change, $count_rows), $to_table_values);
                                                                $features += $count_rows;
                                                                $page++;
                                                            }else{
                                                                $while = false;
                                                            }
                                                        }
                                                        $logger->message("group: ".$group['group_id']." features: ".(string)$features );
                                                    }
                                                }
                                                if($execute){
                                                    fn_set_progress('echo', 'create file import', true);
                                                    $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'create file import');
                                                    $import_schema = fn_get_schema('import', 'features');
                                                    fn_api_merlion_create_features_file($file_path, $update_time, $import_schema, $settings);
                                                    $pattern = fn_get_pattern_definition('products', 'import');
                                                    if (($data = fn_get_csv($pattern, $file_path, $import_schema['import_options'])) != false) {
                                                        $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_features_update');
                                                        if(empty($check_update['value']) || $check_update['value'] == 'Y'){
                                                            $categories = fn_api_merlion_features_clear_category();
                                                            try{
                                                                fn_set_progress('echo', $logger->message('start import...'), true);
                                                                fn_import($pattern, $data, $import_schema['import_options']);
                                                                fn_set_progress('echo', $logger->message('stop import...'), true);
                                                            }
                                                            catch(Exception $e){
                                                                $logger->message($e);
                                                            }
                                                            fn_api_merlion_features_set_category($categories);
                                                        }
                                                    }
                                                    else{
                                                        $logger->message('Error on import!');
                                                    }
                                                    $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_features_update');
                                                    if(empty($check_update['value']) || $check_update['value'] == 'Y'){
                                                        fn_set_progress('echo', 'Sorting...', false);
                                                        fn_api_merlion_features_sorting();
                                                    }
                                                    if($settings['api_merlion_delete_import_file'] == 'Y'){
                                                        fn_rm($file_path);
                                                    }
                                                }
                                            }
                                            else{
                                                fn_set_notification('E', __('error'), __('api_merlion_errors.no_data'));
                                            }
                                        }
                                        else{
                                            fn_set_notification('E', __('error'), __('api_merlion_errors.no_period'));
                                        }
                                    }
                                    else{
                                        fn_set_notification('E', __('error'), __('api_merlion_errors.no_period'));
                                    }
                                }
                                $check_filter = Settings::instance()->getSettingDataByName('api_merlion_products_filters_create');
                                if(empty($check_filter['value']) || $check_filter['value'] == 'Y'){
                                    fn_set_progress('echo', $logger->message('Create filters...'), false);
                                    $count_filters = fn_api_merlion_filters_create($settings);
                                    fn_set_notification('N', __('notice'), 'Create filters '.(string)$count_filters);
                                }
                                $check_filter = Settings::instance()->getSettingDataByName('api_merlion_products_filters_bind');
                                if(empty($check_filter['value']) || $check_filter['value'] == 'Y'){
                                    fn_set_progress('echo', $logger->message('Bind filters to categories...'), false);
                                    fn_api_merlion_binding_filters_to_categories();
                                    fn_set_notification('N', __('notice'), 'Bind filters to categories');
                                }
                                # fn_clear_cache();
                                Settings::instance()->updateValue('api_merlion_import_features_update', 'N');
                                $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'stop');
                                Settings::instance()->updateValue('api_merlion_last_features_update_stop', time());
                            }
                            catch(Exception $e)
                            {
                                Settings::instance()->updateValue('api_merlion_import_features_update', 'N');
                                Settings::instance()->updateValue('api_merlion_last_features_update_stop', time());
                                error_log($logger->message($e));
                            }
                        }
                        else{
                            fn_set_notification('E', __('error'), __('api_merlion_notice.process_update_running'));
                        }
                        break;
                    case "images":
                        $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_images_update');
                        # $GLOBALS['api_merlion_logger_products'] = $logger = new ApiMerlionLogger(implode('_',array('api_merlion_products',$_REQUEST['api_merlion_import'])));
                        if(empty($check_update['value']) || $check_update['value'] == 'N'){
                            try{
                                Settings::instance()->updateValue('api_merlion_last_images_update', $update_time);
                                Settings::instance()->updateValue('api_merlion_last_images_update_stop', ' / #'.(string)getmypid());
                                $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'start');
                                Settings::instance()->updateValue('api_merlion_import_images_update', 'Y');
                                if(!empty($_REQUEST['api_merlion_import_images_period'])){
                                    if($_REQUEST['api_merlion_import_images_period'] == 'A' ){
                                        $_REQUEST['api_merlion_import_images'] = 'local_items';
                                    }
                                }
                                if(empty($_REQUEST['api_merlion_import_images'])){
                                    $_REQUEST['api_merlion_import_features'] = null;
                                }
                                if(count(db_get_fields("SELECT `id` FROM ?:api_merlion_products WHERE `Language` != '' OR `Language` IS NOT NULL")) == 0){
                                    $_REQUEST['api_merlion_import_images'] = null;
                                    fn_set_notification('E', __('error'), __('api_merlion_errors.no_products_update'));
                                }
                                if($_REQUEST['api_merlion_import_images'] == 'local_items'){
                                    $logger = $GLOBALS['api_merlion_logger_products']->instance('m_import|images|local_items');
                                    $execute = true;
                                    if(!$offline){
                                        $last_time_change = fn_api_merlion_create_date(time()-(60*60*24*365*20));
                                        $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'get items...');
                                        $groups_products = array_chunk(fn_api_merlion_get_active_products(array('?:products.product_code'), $all_chose_categories), 500);
                                        $logger->message('get active products: '.(string)(count($groups_products)*500));
                                        foreach($groups_products as $group_products){
                                            $img_update_time = time();
                                            $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_images_update');
                                            if(empty($check_update['value']) || $check_update['value'] == 'N'){
                                                $execute = false;
                                                break;
                                            }
                                            $to_table_values = array(
                                                'check' => $img_update_time,
                                            );
                                            $while = true;
                                            $page = 1;
                                            $rows_on_page = $count_rows = 5000;
                                            $images = 0;
                                            while ($while) {
                                                fn_set_progress('echo','get items...' , false);
                                                if($count_rows == $rows_on_page){
                                                    fn_api_merlion_save_images(fn_api_merlion_get_images('', $group_products, $page, $last_time_change, $count_rows), $to_table_values);
                                                    $images += $count_rows;
                                                    $page++;
                                                }else{
                                                    $while = false;
                                                }
                                            }
                                            $logger->message("images: ".(string)$images );
                                            if($execute){
                                                $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'download images');
                                                fn_api_merlion_download_image($img_update_time, $offline, !empty($_REQUEST['api_merlion_import_images_force']));
                                                fn_set_progress('echo', 'create file import', true);
                                                $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'create file import');
                                                $import_schema = fn_get_schema('import', 'images');
                                                fn_api_merlion_create_images_file($file_path, $img_update_time, $import_schema, $settings);
                                                $pattern = fn_get_pattern_definition('product_images', 'import');
                                                if (($data = fn_get_csv($pattern, $file_path, $import_schema['import_options'])) != false) {
                                                    $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_images_update');
                                                    if(empty($check_update['value']) || $check_update['value'] == 'Y'){
                                                        try{
                                                            $_REQUEST['import_options'] = $import_schema['import_options'];
                                                            fn_set_progress('echo', $logger->message('start import...'), true);
                                                            fn_import($pattern, $data, $import_schema['import_options']);
                                                            fn_set_progress('echo', $logger->message('stop import...'), true);
                                                        }
                                                        catch(Exception $e){
                                                            $logger->message($e);
                                                        }
                                                    }
                                                }
                                                else{
                                                    $logger->message('Error on import!');
                                                }
                                                if($settings['api_merlion_delete_import_file'] == 'Y'){
                                                    fn_rm($file_path);
                                                }
                                            }
                                        }
                                    }
                                }
                                elseif($_REQUEST['api_merlion_import_images'] == 'period_items'){
                                    $logger = $GLOBALS['api_merlion_logger_products']->instance('m_import|images|period_items');
                                    if(!empty($_REQUEST['api_merlion_import_images_time_from']) || !empty($_REQUEST['api_merlion_import_images_period'])){
                                        if(!empty($_REQUEST['api_merlion_import_images_period'])){
                                            list($_REQUEST['api_merlion_import_images_time_from'], $clear_to) = fn_create_periods(array("period" => $_REQUEST['api_merlion_import_images_period']));
                                        }
                                        if(preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $_REQUEST['api_merlion_import_images_time_from']) || $clear_to){
                                            if($clear_to){
                                                $last_time_change = fn_api_merlion_create_date($_REQUEST['api_merlion_import_images_time_from']);
                                            }else{
                                                $last_time_change = fn_api_merlion_create_date(fn_api_merlion_get_date($_REQUEST['api_merlion_import_images_time_from'], "d/m/Y"));
                                            }
                                            $execute = true;
                                            if(!$offline){
                                                $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'get items...');
                                                $groups_products = array_chunk(fn_api_merlion_get_active_products_conditions(array(" LEFT JOIN ?:api_merlion_images ON ?:api_merlion_images.`No` = ?:products.product_code " => " ?:api_merlion_images.`No` IS NULL "), $all_chose_categories), 500);
                                                $logger->message('get products without images: '.(string)(count($groups_products)*500));
                                                foreach($groups_products as $group_products){
                                                    $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_images_update');
                                                    if(empty($check_update['value']) || $check_update['value'] == 'N'){
                                                        $execute = false;
                                                        break;
                                                    }
                                                    $to_table_values = array(
                                                        'check' => $update_time,
                                                    );
                                                    $while = true;
                                                    $page = 1;
                                                    $rows_on_page = $count_rows = 5000;
                                                    $images = 0;
                                                    while ($while) {
                                                        fn_set_progress('echo','get items...' , false);
                                                        if($count_rows == $rows_on_page){
                                                            fn_api_merlion_save_images(fn_api_merlion_get_images('', $group_products, $page, '', $count_rows), $to_table_values);
                                                            $images += $count_rows;
                                                            $page++;
                                                        }else{
                                                            $while = false;
                                                        }
                                                    }
                                                    $logger->message("images: ".(string)$images );
                                                }
                                                $groups = fn_api_merlion_get_active_groups($all_chose_categories);
                                                $logger->message('get active groups: '.(string)count($groups));
                                                foreach($groups as $group){
                                                    $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_images_update');
                                                    if(empty($check_update['value']) || $check_update['value'] == 'N'){
                                                        $execute = false;
                                                        break;
                                                    }
                                                    if($group['group_id']){
                                                        
                                                        $to_table_values = array(
                                                            'check' => $update_time,
                                                        );
                                                        $while = true;
                                                        $page = 1;
                                                        $rows_on_page = $count_rows = 5000;
                                                        $images = 0;
                                                        while ($while) {
                                                            fn_set_progress('echo','get items...' , false);
                                                            if($count_rows == $rows_on_page){
                                                                fn_api_merlion_save_images(fn_api_merlion_get_images($group['group_id'], '', $page, $last_time_change, $count_rows), $to_table_values);
                                                                $images += $count_rows;
                                                                $page++;
                                                            }else{
                                                                $while = false;
                                                            }
                                                        }
                                                        $logger->message("group: ".$group['group_id']." images: ".(string)$images );
                                                    }
                                                }
                                                if($execute){
                                                    $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'download images');
                                                    fn_api_merlion_download_image($update_time, $offline, !empty($_REQUEST['api_merlion_import_images_force']));
                                                    fn_set_progress('echo', 'create file import', true);
                                                    $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'create file import');
                                                    $import_schema = fn_get_schema('import', 'images');
                                                    fn_api_merlion_create_images_file($file_path, $update_time, $import_schema, $settings);
                                                    $pattern = fn_get_pattern_definition('product_images', 'import');
                                                    if (($data = fn_get_csv($pattern, $file_path, $import_schema['import_options'])) != false) {
                                                        $check_update = Settings::instance()->getSettingDataByName('api_merlion_import_images_update');
                                                        if(empty($check_update['value']) || $check_update['value'] == 'Y'){
                                                            try{
                                                                $_REQUEST['import_options'] = $import_schema['import_options'];
                                                                fn_set_progress('echo', $logger->message('start import...'), true);
                                                                fn_import($pattern, $data, $import_schema['import_options']);
                                                                fn_set_progress('echo', $logger->message('stop import...'), true);
                                                            }
                                                            catch(Exception $e){
                                                                $logger->message($e);
                                                            }
                                                        }
                                                    }
                                                    else{
                                                        $logger->message('Error on import!');
                                                    }
                                                    if($settings['api_merlion_delete_import_file'] == 'Y'){
                                                        fn_rm($file_path);
                                                    }
                                                }
                                            }
                                            else{
                                                fn_set_notification('E', __('error'), __('api_merlion_errors.no_data'));
                                            }
                                        }
                                        else{
                                            fn_set_notification('E', __('error'), __('api_merlion_errors.no_period'));
                                        }
                                    }
                                    else{
                                        fn_set_notification('E', __('error'), __('api_merlion_errors.no_period'));
                                    }
                                }
                                # fn_clear_cache();
                                Settings::instance()->updateValue('api_merlion_import_images_update', 'N');
                                $logger->message("process type: ".(string)$_REQUEST['api_merlion_import_'.$_REQUEST['api_merlion_import']] ." > action: " .'stop');
                                Settings::instance()->updateValue('api_merlion_last_images_update_stop', time());
                            }
                            catch(Exception $e){
                                Settings::instance()->updateValue('api_merlion_import_images_update', 'N');
                                Settings::instance()->updateValue('api_merlion_last_images_update_stop', time());
                                error_log($logger->message($e));
                            }
                        }
                        else{
                            fn_set_notification('E', __('error'), __('api_merlion_notice.process_update_running'));
                        }
                        break;
                }
            }
            else{
                fn_set_notification('E', __('notice'), __('api_merlion_errors.no_data'));            
            }
        } 
        else{
            fn_set_notification('E', __('notice'), __('api_merlion_errors.no_data'));
        }
    }
    return array(CONTROLLER_STATUS_OK, 'api_merlion_products.managing_groups');
}

// Секция GET

if ($mode == 'manage') {
    return array(CONTROLLER_STATUS_REDIRECT, 'api_merlion_products.managing_groups');
}
elseif($mode == 'managing_groups'){
    $params = array(
        'group_id' => empty($_REQUEST['group_id']) ? 'Order' : $_REQUEST['group_id'],
        'action' => 'manage',
    );
    fn_get_groups($params);
}
elseif($mode == 'attach_groups'){
    $action = empty($_REQUEST['action']) ? false : $_REQUEST['action'];
    $settings = fn_api_merlion_settings();
    if($action ){
        if($action == 'clear'){
            if(!empty($_REQUEST['action_group'])){
                db_query('UPDATE ?:api_merlion_groups SET category_id = NULL WHERE group_id = ?s',$_REQUEST['action_group']);
            }
        }
        elseif($action == 'attach'){
            if(!empty($_REQUEST['action_group']) && !empty($_REQUEST['action_category'])){
                db_query('UPDATE ?:api_merlion_groups SET category_id = ?i WHERE group_id = ?s',(int)$_REQUEST['action_category'],$_REQUEST['action_group']);
                fn_change_deattach_groups(array('group_id' => $_REQUEST['action_group']));
            }
        }
        try{
            $params_category = array(
                'category_id' => (int)$_REQUEST['update_category'],
            );
            $params_merlion = array(
                'group_id' => $_REQUEST['update_group'],
                'action' => 'attach',
            );    
            fn_api_merlion_get_categories($params_category, empty($settings['api_merlion_product_language']) ? CART_LANGUAGE : $settings['api_merlion_product_language']);
            fn_get_groups($params_merlion); 
        }
        catch(Exception $e){
            fn_set_notification('N', __('error'), $e->getMessage());
        }
           
    }
    else{
        $params_category = array(
            'category_id' => empty($_REQUEST['category_id']) ? 0 : $_REQUEST['category_id'],
        );
        $params_merlion = array(
            'group_id' => empty($_REQUEST['group_id']) ? 'Order' : $_REQUEST['group_id'],
            'action' => 'attach',
        );    
        fn_api_merlion_get_categories($params_category, empty($settings['api_merlion_product_language']) ? CART_LANGUAGE : $settings['api_merlion_product_language']);
        fn_get_groups($params_merlion);         
    }

}
elseif($mode == 'change_group_comparison'){
    if(!empty($_REQUEST['group_id']) && !empty($_REQUEST['comparison'])){
        $params = array(
            'group_id' => $_REQUEST['group_id'],
            'action' => 'manage',
            'comparison' => $_REQUEST['comparison'] == "true" ? 1 : 0,
        );
        $result = fn_change_comparison_groups($params);
        if ($result) {
            fn_set_notification('N', __('notice'), __('api_merlion_notice.groups_update').__("feature_comparison"));
        }
        fn_get_groups($params);        
    }
}
elseif($mode == 'change_group_list_price'){
    if(!empty($_REQUEST['group_id']) && !empty($_REQUEST['list_price'])){
        $params = array(
            'group_id' => $_REQUEST['group_id'],
            'action' => 'manage',
            'list_price' => $_REQUEST['list_price'] == "true" ? 1 : 0,
        );
        $result = fn_change_list_price_groups($params);
        if ($result) {
            fn_set_notification('N', __('notice'), __('api_merlion_notice.groups_update').__("list_price"));
        }
        fn_get_groups($params);        
    }
}
elseif($mode == 'change_group_partnumber_name'){
    if(!empty($_REQUEST['group_id']) && !empty($_REQUEST['partnumber_name'])){
        $params = array(
            'group_id' => $_REQUEST['group_id'],
            'action' => 'manage',
            'partnumber_name' => $_REQUEST['partnumber_name'] == "true" ? 1 : 0,
        );
        $result = fn_change_partnumber_name_groups($params);
        if ($result) {
            fn_set_notification('N', __('notice'), __('api_merlion_notice.groups_update').__("api_merlion_products.partnumber_name"));
        }
        fn_get_groups($params);        
    }
}
elseif($mode == 'import'){
    $company_id = fn_get_default_company_id();
    $settings = fn_api_merlion_settings();
    $settings['api_merlion_last_items_update'] = (int)$settings['api_merlion_last_items_update'] ? fn_api_merlion_create_date((int)$settings['api_merlion_last_items_update'], 'Y-m-d H:i:s') : ($settings['api_merlion_import_items_update'] == 'Y' ? __('api_merlion_notice.warning'): __('api_merlion_errors.no_data'));
    $settings['api_merlion_last_features_update'] = (int)$settings['api_merlion_last_features_update'] ? fn_api_merlion_create_date((int)$settings['api_merlion_last_features_update'], 'Y-m-d H:i:s') : ($settings['api_merlion_import_features_update'] ? __('api_merlion_notice.warning'): __('api_merlion_errors.no_data'));
    $settings['api_merlion_last_images_update'] = (int)$settings['api_merlion_last_images_update'] ? fn_api_merlion_create_date((int)$settings['api_merlion_last_images_update'], 'Y-m-d H:i:s') : ($settings['api_merlion_import_images_update'] == 'Y' ? __('api_merlion_notice.warning'): __('api_merlion_errors.no_data')) ;
    $settings['api_merlion_last_items_update_stop'] = (int)$settings['api_merlion_last_items_update_stop'] ? fn_api_merlion_create_date((int)$settings['api_merlion_last_items_update_stop'], 'Y-m-d H:i:s') : ($settings['api_merlion_import_items_update'] == 'Y' ? __('api_merlion_notice.warning').$settings['api_merlion_last_items_update_stop']: __('api_merlion_errors.no_data'));
    $settings['api_merlion_last_features_update_stop'] = (int)$settings['api_merlion_last_features_update_stop'] ? fn_api_merlion_create_date((int)$settings['api_merlion_last_features_update_stop'], 'Y-m-d H:i:s'): ($settings['api_merlion_import_features_update'] == 'Y' ? __('api_merlion_notice.warning').$settings['api_merlion_last_features_update_stop']: __('api_merlion_errors.no_data'));
    $settings['api_merlion_last_images_update_stop'] = (int)$settings['api_merlion_last_images_update_stop'] ? fn_api_merlion_create_date((int)$settings['api_merlion_last_images_update_stop'], 'Y-m-d H:i:s') : ($settings['api_merlion_import_images_update'] == 'Y' ? __('api_merlion_notice.warning').$settings['api_merlion_last_images_update_stop']: __('api_merlion_errors.no_data')) ;
    Tygh::$app['view']->assign('api_merlion_import_items_extra', @unserialize($settings['api_merlion_import_items_extra']));
    Tygh::$app['view']->assign('api_merlion_import_images_extra', @unserialize($settings['api_merlion_import_images_extra']));
    Tygh::$app['view']->assign('api_merlion_import_company_id', $company_id);
    /* 
        version hack
        4.3 => list($sections, $patterns_products) = fn_get_patterns('products', 'import');
        4.4 => list($sections, $patterns_products) = fn_exim_get_patterns('products', 'import');
        
    */
    preg_match("/^([0-9]+)\.([0-9]+).([0-9]+).*$/" , PRODUCT_VERSION , $version);
    switch ($version[1].$version[2]) {
        case "43":
            list($sections, $patterns_products) = call_user_func_array ('fn_get_patterns', array('products', 'import'));
            break;
        case "44":
            list($sections, $patterns_products) = call_user_func_array ('fn_exim_get_patterns', array('products', 'import'));
            break;
        case "49":
            list($sections, $patterns_products) = call_user_func_array ('fn_exim_get_patterns', array('products', 'import'));
            break;
        default:
            throw new \Exception("No function <get_patterns> for version: ".var_export($version, true));
    }
    Tygh::$app['view']->assign('patterns_products', $patterns_products);
    Tygh::$app['view']->assign('current_api_merlion_settings', $settings);
    if(ApiMerlion::connect()){
        Tygh::$app['view']->assign('api_merlion_settings', true);
    }
}
elseif($mode == 'get_product'){
    if(defined('AJAX_REQUEST')){
        if(ApiMerlion::connect()){
            if(!empty($_REQUEST['product_code'])){
                $settings = fn_api_merlion_settings();
                if(!empty($settings['api_merlion_shipment_date']) && !empty($settings['api_merlion_shipment_method'])){
                    $page = NULL;
                    $count_rows = NULL;
                     $logger->message("settings parameters:", array('items' => array($_REQUEST['product_code']), 'api_merlion_product_available' => $settings['api_merlion_product_available'], 'api_merlion_shipment_method' => $settings['api_merlion_shipment_method'], 'api_merlion_shipment_date' => $settings['api_merlion_shipment_date']));
                    $product_schema = fn_get_schema('api_merlion', 'products');
                    $product = fn_api_merlion_get_products('', array($_REQUEST['product_code']),"0", $settings['api_merlion_shipment_method'], $settings['api_merlion_shipment_date'], '', $page, $count_rows);
                    Tygh::$app['view']->assign('api_merlion_products_schema', $product_schema);
                    Tygh::$app['view']->assign('api_merlion_products', $product);                    
                }
                else{
                    fn_set_notification('E', __('error'), __('api_merlion_errors.no_data').": ".__('api_merlion_settings.shipment_date').", ".__('api_merlion_settings.shipment_method'));
                }

            }
        } 
        else{
            fn_set_notification('E', __('notice'), ApiMerlion::$error);
        }
    }
}
elseif($mode == 'del_filters'){
    $deleted_desc = db_query("DELETE FROM ?:product_filter_descriptions WHERE ?:product_filter_descriptions.filter_id IN ( SELECT ?:product_filters.filter_id FROM ?:product_filters WHERE ?:product_filters.status = 'D')");
    $deleted_filters = db_query("DELETE FROM ?:product_filters WHERE ?:product_filters.status = 'D'");
    fn_set_notification('W', __('important'), 'Deleted filters: '.(string)$deleted_filters);
    fn_set_notification('W', __('important'), 'Deleted filter descriptions: '.(string)$deleted_desc);
    $ids_shares = db_get_fields("SELECT ?:ult_objects_sharing.share_object_id FROM ?:ult_objects_sharing LEFT JOIN ?:product_filters ON ?:product_filters.filter_id = ?:ult_objects_sharing.share_object_id WHERE ?:product_filters.filter_id IS NULL AND ?:ult_objects_sharing.share_object_type = 'product_filters'");
    $deleted_shares = db_query("DELETE FROM ?:ult_objects_sharing WHERE ?:ult_objects_sharing.share_object_type = 'product_filters' AND ?:ult_objects_sharing.share_object_id IN (?n)", $ids_shares);
    fn_set_notification('W', __('important'), 'Clears : '.(string)$deleted_shares);
}
elseif($mode == 'test'){
    // $logger = $GLOBALS['api_merlion_logger_products']->instance('m_import');
    // $logger->message('get request', $_REQUEST);
    // $dir = implode(DIRECTORY_SEPARATOR, array(rtrim(fn_get_files_dir_path(),'/'),'api_merlion'));
    // $akkom_source_patch = fn_get_files_dir_path()."import_akkom_products.csv";
    // $update_time = time();
    // $settings = fn_api_merlion_settings();
    // $logger->message('try file import', $akkom_source_patch);
    // $logger->message('try file import', var_export(file_exists($akkom_source_patch) && is_readable($akkom_source_patch), true));
    // if(file_exists($akkom_source_patch) && is_readable($akkom_source_patch)) {
    //     $logger = $GLOBALS['api_merlion_logger_products']->instance('m_import|items|akkom_items');
    //     $to_table_values = array(
    //         'Language' => empty($settings['api_merlion_product_language']) ? CART_LANGUAGE : $settings['api_merlion_product_language'],
    //         'check' => $update_time,
    //     );
    //     $csv = fn_api_merlion_csv_to_array($akkom_source_patch, "\t");
    //     if (is_array($csv)) {
    //         $update_products = fn_api_merlion_save_akkom_products($csv, $to_table_values);
    //     }
    //     if (isset($update_products) && is_array($update_products) && count($update_products) > 0) {
    //         $logger->message('truncate table api_merlion_akkom_product:', db_query("TRUNCATE TABLE ?:api_merlion_akkom_product"));
    //         foreach ($update_products as $key => $up_product) {
    //             db_query('INSERT INTO ?:api_merlion_akkom_product
    //             (`product_code`)
    //             VALUES(?s)', $up_product);
    //         }
    //         $import_schema_ap = fn_get_schema('import', 'akkom_category_products');
    //         $akkom_product_file_path = implode(DIRECTORY_SEPARATOR, array($dir,'akkom_products_'.implode('_',array((string)$update_time, (string)uniqid())).'.csv'));
    //         fn_api_merlion_create_products_file($akkom_product_file_path, $update_time, $import_schema_ap, array(), $settings, true , array(), 'A');
    //
    //         $pattern = fn_get_pattern_definition('products', 'import');
    //         if (($data = fn_get_csv($pattern, $akkom_product_file_path, $import_schema_ap['import_options'])) != false) {
    //             try{
    //                 fn_set_progress('echo', $logger->message('start akkom product import...'), true);
    //                 fn_import($pattern, $data, $import_schema_ap['import_options']);
    //                 fn_set_progress('echo', $logger->message('stop import...'), true);
    //                 if($settings['api_merlion_delete_import_file'] == 'Y'){
    //                     fn_rm($akkom_product_file_path);
    //                 }
    //             }
    //             catch(Exception $e){
    //                 $logger->message($e);
    //             }
    //         }
    //         else{
    //             $logger->message('Error on import!');
    //         }
    //
    //         $import_schema_af = fn_get_schema('import', 'features');
    //         $akkom_features_file_path = implode(DIRECTORY_SEPARATOR, array($dir,'akkom_features_'.implode('_',array((string)$update_time, (string)uniqid())).'.csv'));
    //         fn_api_merlion_create_features_file($akkom_features_file_path, $update_time, $import_schema_af, $settings, $update_products);
    //
    //         $pattern = fn_get_pattern_definition('products', 'import');
    //         if (($data = fn_get_csv($pattern, $akkom_features_file_path, $import_schema_af['import_options'])) != false) {
    //                 $categories = fn_api_merlion_features_clear_category();
    //                 try{
    //                     fn_set_progress('echo', $logger->message('start akkkom features import...'), true);
    //                     fn_import($pattern, $data, $import_schema_af['import_options']);
    //                     fn_set_progress('echo', $logger->message('stop import...'), true);
    //                     if($settings['api_merlion_delete_import_file'] == 'Y'){
    //                         fn_rm($akkom_features_file_path);
    //                     }
    //                 }
    //                 catch(Exception $e){
    //                     $logger->message($e);
    //                 }
    //                 fn_api_merlion_features_set_category($categories);
    //         }
    //         else{
    //             $logger->message('Error on import!');
    //         }
    //
    //
    //         $import_schema_ai = fn_get_schema('import', 'images');
    //         $akkom_images_file_path = implode(DIRECTORY_SEPARATOR, array($dir,'akkom_images_'.implode('_',array((string)$update_time, (string)uniqid())).'.csv'));
    //         fn_api_merlion_create_images_file($akkom_images_file_path, $update_time, $import_schema_ai, $settings, $update_products);
    //
    //         $pattern = fn_get_pattern_definition('product_images', 'import');
    //         if (($data = fn_get_csv($pattern, $akkom_images_file_path, $import_schema_ai['import_options'])) != false) {
    //             try{
    //                 fn_set_progress('echo', $logger->message('start akkom images import...'), true);
    //                 fn_import($pattern, $data, $import_schema_ai['import_options']);
    //                 fn_set_progress('echo', $logger->message('stop import...'), true);
    //
    //                 if($settings['api_merlion_delete_import_file'] == 'Y'){
    //                     fn_rm($akkom_images_file_path);
    //                 }
    //             }
    //             catch(Exception $e){
    //                 $logger->message($e);
    //             }
    //         }
    //         else{
    //             $logger->message('Error on import!');
    //         }
    //     }
    //
    // }
}

function fn_get_groups($params){
    $result = $child = $where = false;
    Tygh::$app['view']->assign('groups_level', 0);
    if($params['action'] == 'manage'){
        $where = array(
            'group_pid' =>  $params['group_id'],
        );
        $result = db_get_hash_array('SELECT * FROM ?:api_merlion_groups WHERE ?w ORDER BY ?:api_merlion_groups.name', 'group_id', $where);
    }
    elseif($params['action'] == 'attach'){
        $fields = array (
            '?:api_merlion_groups.name',
            '?:api_merlion_groups.group_id',
            '?:api_merlion_groups.category_id',
            '?:api_merlion_groups.status',
            '?:categories.id_path'
        );
        $where = db_quote('?:api_merlion_groups.group_pid = ?s and ?:api_merlion_groups.status = ?s', $params['group_id'], 'A');
        $result = db_get_hash_array('SELECT ' . implode(',', $fields) . ' FROM ?:api_merlion_groups LEFT JOIN ?:categories ON ?:categories.category_id = ?:api_merlion_groups.category_id WHERE ?p ORDER BY ?:api_merlion_groups.name', 'group_id', $where);       
    }
  
    if($result){
        Tygh::$app['view']->assign('groups_level', strlen(array_keys($result)[0])/2 );
        $where = false;
        if($params['action'] == 'manage'){
            $where = db_quote('?:api_merlion_groups.group_pid in (?a)',array_keys($result));
        }
        elseif($params['action'] == 'attach'){
            $where = db_quote('?:api_merlion_groups.group_pid in (?a) and ?:api_merlion_groups.status = ?s',array_keys($result), 'A');
        }
        if($where){
            $child = db_get_hash_array('SELECT ?:api_merlion_groups.group_pid, COUNT(*) as n FROM ?:api_merlion_groups WHERE ?p GROUP BY ?:api_merlion_groups.group_pid', 'group_pid', $where);             
        }
    }
    if($child){
        foreach($child as $group_id => $group_data){
            if(!$result[$group_id]['category_id'] && $params['action'] == 'attach'){
                $result[$group_id]['child'] = $group_data['n'];
            }
            elseif($params['action'] == 'manage'){
                $result[$group_id]['child'] = $group_data['n'];
            }
        }        
    }
    else{
        foreach($result as $group_id => $group_data){
            $result[$group_id]['child'] = 0;
        } 
    }
    Tygh::$app['view']->assign('group_id', $params['group_id']);
    Tygh::$app['view']->assign('groups_tree', $result);
    if(!$result){
        fn_set_notification('N', __('notice'), __('api_merlion_errors.no_data'));
    }
    return $result;
}

function fn_change_status_groups($params){
    $result = db_query("UPDATE ?:api_merlion_groups SET status = ?s WHERE ?w", $params['status'], array($params['id_name'] => $params['id']));
    $childs = db_get_fields('SELECT group_id FROM ?:api_merlion_groups WHERE ?:api_merlion_groups.group_pid = ?s', $params['id']);
    foreach($childs as $child_id){
        $child_params=array(
            "status" => $params['status'],
            'id_name' => $params['id_name'],
            'id' => $child_id
        );
        fn_change_status_groups($child_params);
    }
    return $result;
}

function fn_change_comparison_groups($params){
    $result = db_query("UPDATE ?:api_merlion_groups SET comparison = ?s WHERE ?w", $params['comparison'], array('group_id' => $params['group_id']));
    $childs = db_get_fields('SELECT group_id FROM ?:api_merlion_groups WHERE ?:api_merlion_groups.group_pid = ?s', $params['group_id']);
    foreach($childs as $child_id){
        $child_params=array(
            "comparison" => $params['comparison'],
            'group_id' => $child_id
        );
        fn_change_comparison_groups($child_params);
    }
    return $result;
}

function fn_change_list_price_groups($params){
    $result = db_query("UPDATE ?:api_merlion_groups SET list_price = ?s WHERE ?w", $params['list_price'], array('group_id' => $params['group_id']));
    $childs = db_get_fields('SELECT group_id FROM ?:api_merlion_groups WHERE ?:api_merlion_groups.group_pid = ?s', $params['group_id']);
    foreach($childs as $child_id){
        $child_params=array(
            "list_price" => $params['list_price'],
            'group_id' => $child_id
        );
        fn_change_list_price_groups($child_params);
    }
    return $result;
}

function fn_change_partnumber_name_groups($params){
    $result = db_query("UPDATE ?:api_merlion_groups SET partnumber_name = ?s WHERE ?w", $params['partnumber_name'], array('group_id' => $params['group_id']));
    $childs = db_get_fields('SELECT group_id FROM ?:api_merlion_groups WHERE ?:api_merlion_groups.group_pid = ?s', $params['group_id']);
    foreach($childs as $child_id){
        $child_params=array(
            "partnumber_name" => $params['partnumber_name'],
            'group_id' => $child_id
        );
        fn_change_partnumber_name_groups($child_params);
    }
    return $result;
}

function fn_change_deattach_groups($params){
    $childs = db_get_fields('SELECT group_id FROM ?:api_merlion_groups WHERE ?:api_merlion_groups.group_pid = ?s', $params['group_id']);
    foreach($childs as $child_id){
        $child_params=array(
            'group_id' => $child_id
        );
        fn_change_deattach_groups($child_params);
        db_query("UPDATE ?:api_merlion_groups SET category_id = ?i WHERE ?w", 0 , array('group_id' => $child_id));
    }
    return true;
}

function fn_api_merlion_get_categories($params, $lang_code = CART_LANGUAGE){
    $result = $child = false;
    Tygh::$app['view']->assign('categories_level', 0);
    $fields = array (
        '?:categories.category_id',
        '?:categories.parent_id',
        '?:categories.id_path',
        '?:category_descriptions.category',
        '?:categories.position',
        '?:categories.status',
        '?:api_merlion_groups.group_id'
    );

    $where = db_quote('?:categories.parent_id = ?s AND ?:categories.status = ?s', $params['category_id'],'A');        
        
    if($where){
        $result = db_get_hash_array('SELECT ' . implode(',', $fields) . " FROM ?:categories LEFT JOIN ?:category_descriptions ON ?:categories.category_id = ?:category_descriptions.category_id AND ?:category_descriptions.lang_code = ?s LEFT JOIN ?:api_merlion_groups ON ?:categories.category_id = ?:api_merlion_groups.category_id WHERE ?p ORDER BY ?:categories.position", 'category_id', $lang_code, $where);
    }
    if($result){
        Tygh::$app['view']->assign('categories_level', count(explode('/',current($result)['id_path'])));
        $where = db_quote('?:categories.parent_id in (?a) and ?:categories.status = ?s',array_keys($result), 'A');

        $child = db_get_hash_array('SELECT ?:categories.parent_id, COUNT(*) as n FROM ?:categories WHERE ?p GROUP BY ?:categories.parent_id', 'parent_id', $where);             

        
    }
    if($child){
        foreach($child as $category_id => $category_data){
            $result[$category_id]['child'] = $category_data['n'];
            // if(!$result[$category_id]['group_id']){
                
            // }
        }        
    }
    else{
        foreach($result as $category_id => $category_data){
            $result[$category_id]['child'] = 0;
        } 
    }
    Tygh::$app['view']->assign('category_id', $params['category_id']);
    Tygh::$app['view']->assign('categories_tree', $result);
    if(!$result){
        fn_set_notification('N', __('notice'), __('api_merlion_errors.no_data'));
    }
    return $result;
}

function fn_api_merlion_get_products($cat_id, $item_id, $available, $shipment_method, $shipment_date, $last_time_change, &$page, &$count_rows){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    $result = array();
    try{
        $count_rows = 0;
        $values = ApiMerlion::getItems($cat_id, $item_id, $shipment_method, $page? $page : 0, 500, $last_time_change);
        if($values && !array_key_exists('No', $values)){
            foreach($values as $key => $value){
                if(!empty($value['No'])){
                    $result[$value['No']] = $value;
                }
                else{
                    $logger->message("Not found key [No]", $values);
                    //break;
                }
                $count_rows++;
            }            
        }
        elseif($values && !empty($values['No'])){
            $result[$values['No']] = $values;
        }
    }
    catch(Exception $e){
        $logger->message($e, func_get_args());
    }
    if(!empty($result)){
        try{
            $update = array(
                'AvailableClient' => 0,
                'PriceClientRUB' => 0
            );
            $values = ApiMerlion::getItemsAvail('', $shipment_method, $shipment_date, $available, array_keys($result));
            if($values && !array_key_exists('No', $values)){
                 foreach($values as $key => $value){
                    if(!empty($value['No'])){
                        $result[$value['No']] = $result[$value['No']]+$value;
                    }
                    else{
                        $logger->message("Not found key [No]" , $values);
                        //break;
                    }
                }           
            }
            elseif($values){
                if(!empty($values['No'])){
                    $result[$values['No']] =  $result[$values['No']]+$values;
                }
                elseif(count($result) == 1){
                    $result_id = array_keys($result)[0];
                    $result[$result_id] =  $result[$result_id]+$update;
                }
            }
        }
        catch(Exception $e){
            $logger->message($e, func_get_args());
        }         
    }
    return $result;
}

function fn_api_merlion_get_features($cat_id, $item_id, &$page, $last_time_change, &$count_rows){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    $result = array();
    try{
        $count_rows = 0;
        $values = ApiMerlion::getItemsProperties($cat_id, $item_id, $page? $page : 0, 5000, $last_time_change);
        if($values){
            foreach($values as $key => $value){
                if(array_key_exists('No', $value)){
                    $result[$value['No']][] = $value;
                }
                else{
                    $logger->message("Not found key [No]", $values);
                    break;
                }
                $count_rows++;
            }            
        }
    }
    catch(Exception $e){
        $logger->message($e, func_get_args());
    }
    return $result;
}

function fn_api_merlion_get_images($cat_id, $item_id, &$page, $last_time_change, &$count_rows){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    $result = array();
    try{
        $count_rows = 0;
        $values = ApiMerlion::getItemsImages($cat_id, $item_id, $page? $page : 0, 5000, $last_time_change);
        if($values){
            foreach($values as $key => $value){
                if(array_key_exists('No', $value)){
                    $result[$value['No']][] = $value;
                    $logger->message(false,$value);
                }
                else{
                    $logger->message("Not found key [No]", $values);
                    break;
                }
                $count_rows++;
            }            
        }
    }
    catch(Exception $e){
        $logger->message($e, func_get_args());
    }
    return $result;
}

function fn_api_merlion_save_products($to_table, $extra_fields, $rrp = array()){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    $update = array(
        'AvailableClient' => 0,
        'PriceClientRUB' => 0
    );
    $extra_fields['Source'] = 'M';
    try{
        $saved = 0;
        foreach($to_table as $key => $value){
            if(array_key_exists('No', $value)
                // отключаем DEMO товары
                && (int)count(preg_grep("/\s+demo\s+/i", array($value['Name']))) < 1
            ) {
                if(array_key_exists('Last_time_modified', $value)){
                    $value['Last_time_modified'] = fn_api_merlion_create_date_mysql( fn_api_merlion_get_date($value['Last_time_modified']));
                }
                if(!empty($value['RRP']) && !empty($value['GroupCode3'])){
                    if(!in_array($value['GroupCode3'], $rrp)){
                        $value['RRP'] = 0;
                    }
                }
                if(array_key_exists('RRP_Date', $value)){
                    if(fn_api_merlion_get_date($value['RRP_Date'], true) < time()){
                        $value['RRP_Date'] = fn_api_merlion_create_date_mysql(fn_api_merlion_get_date($value['RRP_Date']));  
                    }
                    $value['RRP_Date'] = null;
                    
                }
                if(array_key_exists('DateExpectedNext', $value)){
                    $date = fn_api_merlion_create_date_mysql( fn_api_merlion_get_date($value['DateExpectedNext']));
                    $value['DateExpectedNext'] = !empty($date) ? $date : null;
                }
                if(!array_key_exists('AvailableClient', $value)){
                    $value = $value + $update;
                }
                else{
                    $saved++;
                }

                db_query('INSERT INTO ?:api_merlion_products ?e ON DUPLICATE KEY UPDATE ?u',$value+$extra_fields, $value+$extra_fields);                                                                
            }
            else{
                $logger->message("Not found key [No]", $to_table);
                break;
            }
        }
        $logger->message("save products: ".(string)$saved);
    }
    catch(Exception $e){
        $logger->message($e, func_get_args());
    }
}

function fn_api_merlion_save_features($to_table, $extra_fields){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    try{
        $saved = 0;
        foreach($to_table as $key => $features){
            foreach($features as $value){
                if(array_key_exists('No', $value)){
                    if(array_key_exists('Last_time_modified', $value)){
                        $value['Last_time_modified'] = fn_api_merlion_create_date_mysql( fn_api_merlion_get_date($value['Last_time_modified']));
                    }
                    $value['hash'] = md5(implode(';',array($value['No'],(string)$value['PropertyID'],$value['Value'])));
                    $value['PropertyName'] = ucfirst(trim($value['PropertyName']));
                    $value_feature = mb_strtolower(trim($value['Value']));
                    if($value_feature == 'да'){
                        $value['Value'] = $value_feature;
                    }
                    else{
                        $value['Value'] = ucfirst(trim($value['Value']));
                    }
                    $saved++;
                    db_query('INSERT INTO ?:api_merlion_features ?e ON DUPLICATE KEY UPDATE ?u',$value+$extra_fields, $value+$extra_fields);
                }
                else{
                    $logger->message("Not found key [No]", $features);
                    break;
                }                
            }
        }
        $logger->message("save features: ".(string)$saved);
    }
    catch(Exception $e){
        $logger->message($e, func_get_args());
    }
}

function fn_api_merlion_save_images($to_table, $extra_fields){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    try{ 
        $saved = 0;
        foreach($to_table as $key => $images){
            foreach($images as $value){
                if(array_key_exists('No', $value)){
                    if($value['SizeType'] == 'b'){
                        if(array_key_exists('Created', $value)){
                            $value['Created'] = fn_api_merlion_create_date_mysql( fn_api_merlion_get_date($value['Created']));
                        }
                        $value['hash'] = md5(implode('',array($value['No'], $value['SizeType'], $value['FileName'])));
                        $saved++;
                        db_query('INSERT INTO ?:api_merlion_images ?e ON DUPLICATE KEY UPDATE ?u',$value+$extra_fields, $value+$extra_fields);                        
                    }
                }
                else{
                    $logger->message("Not found key [No]", $images);
                    break;
                }                
            }
        }
        $logger->message("save images: ".(string)$saved);
    }
    catch(Exception $e){
        $logger->message($e, func_get_args());
    }
}

function fn_api_merlion_create_products_file($file_path, $update_time, $import_schema, $extra_fields, $settings, $with_price = true, $categories=array(), $source = 'M'){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    
    if(!$with_price){
        unset($import_schema['values']['Price']);
        unset($import_schema['compare']['PriceClientRUB']);
    }

    switch($import_schema['import_options']['delimiter']){
        case 'T':
            $delimiter = chr(9);
            break;
        case 'S':
            $delimiter = chr(59);
            break;
        case 'C':
            $delimiter = chr(44);
            break;
    }
    $offline = $settings['api_merlion_import_offline'] == 'Y' ? true : false;
    $logger->message('Offline work: ' . $offline ? 'YES' : 'NO');
    if($offline){
        $check = db_quote('`No` IN (?a) AND ', fn_api_merlion_get_active_products(array('?:products.product_code'), $categories));
    }
    else{
        $check = db_quote('`check` = ?i AND ', $update_time);
    }
    $ids_for_import = db_get_fields("SELECT `id` FROM ?:api_merlion_products WHERE ?p `AvailableClient` > 0 AND `PriceClientRUB` > 0 AND `Language` = ?s AND `Source` = ?s ", $check, empty($settings['api_merlion_product_language']) ? CART_LANGUAGE : $settings['api_merlion_product_language'], $source);
    $logger->message('Count products for import: '.(string)count($ids_for_import));
    $file_import = fopen($file_path, 'w');
    $logger->message('Create file for import: '.$file_path);
    // TODO: add option on import page
    $store = fn_get_company_name(fn_get_default_company_id());
    fputcsv($file_import, array_keys($import_schema['values']+$extra_fields), $delimiter);
    foreach($ids_for_import as $id){
        $product = db_get_row("SELECT ?p , `PartnumberName`, `Vendor_part` FROM ?:api_merlion_products WHERE `id` = ?i", db_quote('`'.implode('`,`', array_keys($import_schema['compare'])).'`'), (int)$id);
        $to_file = $import_schema['values'];
        $execute = true;
        if($settings['api_merlion_product_package'] == 'N'){
            if((int)$product["Min_Packaged"] > 1){
                $execute = false;
            }
        }
        if($execute){
            foreach($product as $key => $value){
                if($key == "Min_Packaged"){
                    if((int)$value < 1){
                        $value = 1;
                    }
                }
                if(array_key_exists($key,$import_schema['compare'])){
                    $to_file[$import_schema['compare'][$key]] = $value;
                }
            }
            $to_file["Store"] = $store;
            
            if(!empty($product['PartnumberName']) && !empty($product["Vendor_part"])){
                if($product['PartnumberName'] == 'Y' && array_key_exists('Product name', $to_file)){
                    $to_file["Product name"] = $to_file["Product name"] ." "."(".$product["Vendor_part"].")";
                }
            }
            $logger->message(false,$to_file);
            fputcsv($file_import, array_values($to_file+$extra_fields), $delimiter);            
        }
    }
    fclose($file_import);
}

function fn_api_merlion_create_features_file($file_path, $update_time, $import_schema, $settings, $only_selected = false){
    switch($import_schema['import_options']['delimiter']){
        case 'T':
            $delimiter = chr(9);
            break;
        case 'S':
            $delimiter = chr(59);
            break;
        case 'C':
            $delimiter = chr(44);
            break;
    }
    $offline = $settings['api_merlion_import_offline'] == 'Y' ? true : false;
    if($offline){
        $check = db_quote('');
    }
    else{
        $check = db_quote('`check` = ?i AND ', $update_time);
    }
    $file_import = fopen($file_path, 'w');
    // TODO: add option on import page
    $store = fn_get_company_name(fn_get_default_company_id());
    fputcsv($file_import, array_keys($import_schema['values']), $delimiter);
    if (!$only_selected) {
        $products = db_get_fields("SELECT `No` FROM ?:api_merlion_features WHERE ?p `No` IN (?a) GROUP BY `No`", $check, fn_api_merlion_get_active_products(array('?:products.product_code')));
    } else {
        $products = db_get_fields("SELECT `No` FROM ?:api_merlion_features WHERE ?p `No` IN (?a) GROUP BY `No`", $check, $only_selected);
    }
    foreach($products as $product){
        $product_values = db_get_row("SELECT ?p FROM ?:api_merlion_products WHERE `No` = ?s and `Language` = ?s", db_quote('`'.implode('`,`', array_keys($import_schema['products_compare'])).'`'), $product, empty($settings['api_merlion_product_language']) ? CART_LANGUAGE : $settings['api_merlion_product_language']);
        if($product_values){
            $features = db_get_array("SELECT ?p FROM ?:api_merlion_features WHERE ?p `No` = ?s", db_quote('`'.implode('`,`', $import_schema['features_fields']).'`'), $check, $product);
            if($features){
                $to_file = $import_schema['values'];
                if(array_key_exists("Store", $to_file)){
                    $to_file["Store"] = $store;
                }
                foreach($features as $feature_key => $feature_value){
                    $to_feature = $import_schema['features_values'];
                    foreach($feature_value as $key => $value){
                        $to_feature[$key] = $value;
                        // if($key == 'Value'){
                            // $to_feature['Type'] = is_numeric($value) ? 'N' : 'S';
                        // }
                    }
                    $features[$feature_key] = str_replace(array_keys($to_feature), array_values($to_feature),$import_schema['features_txt']);
                }
                $features[] = __('brand').str_replace(array_keys($product_values), array_values($product_values),$import_schema['features_brand_txt']);
                $to_file['Features'] = implode($import_schema['features_delimiter'],$features);
                foreach($product_values as $key => $value){
                    if(array_key_exists($import_schema['products_compare'][$key], $to_file)){
                        $to_file[$import_schema['products_compare'][$key]] = $value; 
                    }
                }
                fputcsv($file_import, array_values($to_file), $delimiter);                  
            }
        }
    }
    fclose($file_import);
}

function fn_api_merlion_create_images_file($file_path, $update_time, $import_schema, $settings, $only_selected = false){
    switch($import_schema['import_options']['delimiter']){
        case 'T':
            $delimiter = chr(9);
            break;
        case 'S':
            $delimiter = chr(59);
            break;
        case 'C':
            $delimiter = chr(44);
            break;
    }
    $offline = $settings['api_merlion_import_offline'] == 'Y' ? true : false;
    if($offline){
        $check = db_quote('');
    }
    else{
        $check = db_quote('`check` = ?i AND ', $update_time);
    }
    $file_import = fopen($file_path, 'w');
    fputcsv($file_import, array_keys($import_schema['values']), $delimiter);
    if(!$only_selected) {
        $products = db_get_fields("SELECT `No` FROM ?:api_merlion_images WHERE ?p `No` IN (?a) GROUP BY `No`", $check, fn_api_merlion_get_active_products(array('?:products.product_code')));
    } else {
        $products = db_get_fields("SELECT `No` FROM ?:api_merlion_images WHERE ?p `No` IN (?a) GROUP BY `No`", $check, $only_selected);
    }
    foreach($products as $product){

        $images = db_get_array("SELECT ?p FROM ?:api_merlion_images WHERE ?p `No` = ?s ORDER BY `ViewType` DESC", db_quote('`'.implode('`,`', $import_schema['images_values']).'`'), $check, $product);
        $type = null;
        foreach($images as $image){
            $to_file = $import_schema['values'];
            $to_file['Product code'] = $image['No'];
            $to_file['Pair type'] = $type==null ? 'M' : 'A' ;
            $to_file['Detailed image'] = $image['hash'].substr($image['FileName'],strrpos($image['FileName'],'.'));
            fputcsv($file_import, array_values($to_file), $delimiter); 
            $type=1;
        }
    }
    fclose($file_import);
}

function fn_api_merlion_download_image($update_time, $offline, $force=false){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    $cron_password = Settings::instance()->getSettingDataByName('api_merlion_import_cron_password')['value'];
    $dir = implode(DIRECTORY_SEPARATOR, array(rtrim(fn_get_files_dir_path(),'/'),'exim','backup','images'));
    fn_mkdir($dir);
    $products = fn_api_merlion_get_active_products(array('?:products.product_code'));
    if($offline){
        $check = db_quote('');
    }
    else{
        $check = db_quote('`check` = ?i AND', $update_time);
    }
    $files = db_get_array("SELECT `hash`, `FileName`, `Size` FROM `?:api_merlion_images` WHERE ?p `No` IN (?a) ", $check, $products);
    $logger->message('get images fo dowload: '.count($files));
    $opts = array(
      'http'=>array(
        'method'=>"GET",
        "timeout"=>60.0,
      )
    );
    $context = stream_context_create($opts); 
    if(count($files) > 0 ){
        foreach($files as $file){
            fn_set_progress('echo','get images...' , false);
            if(is_file(implode(DIRECTORY_SEPARATOR,array($dir,$file['hash'].substr($file['FileName'],strrpos($file['FileName'],'.'))))) && !$force){
                $status = stat(implode(DIRECTORY_SEPARATOR,array($dir,$file['hash'].substr($file['FileName'],strrpos($file['FileName'],'.')))));
            }else{
                $status = false;
            }
            if($status ? ((int)$status['size'] != (int)$file['Size'] ? true : false) : true){
                $execute = false;
                $connects = 0;
                //$save_size = true;
                $dowload_size = 0;
                try{
                    while(true){
                        while(true){
                            $code = get_http_response_code("http://img.merlion.ru/items/".$file['FileName']);
                            if($code == '200'){
                                $execute = true;
                                break;
                            }
                            elseif($code == '503'){
                                fn_set_progress('echo','error code: '.$code , false);
                                $logger->message(false, array("file"=>"http://img.merlion.ru/items/".$file['FileName'], "code" => $code, "sleep" => 5, "connects" => $connects));
                                $connects++;
                                sleep(5);
                            }
                            elseif($code == '404'){
                                break;
                            }
                            else{
                                fn_set_progress('echo','error code: '.$code , false);
                                $logger->message(false, array("file"=>"http://img.merlion.ru/items/".$file['FileName'], "code" => $code, "sleep" => 2, "connects" => $connects ));
                                $connects++;
                                sleep(2);
                            }
                            if($connects > 20){
                                break;
                            }
                        }
                        if($execute){
                            $tmpf_name = tempnam("/tmp", "api_merlion_image_");
                            try {
                                fn_set_progress('echo','get images...' , false);
                                $tmpfname = fopen($tmpf_name, 'w');
                                $save_file = implode(DIRECTORY_SEPARATOR,array($dir,$file['hash'].substr($file['FileName'],strrpos($file['FileName'],'.'))));
                                // $save_size = file_put_contents($save_file , fopen("http://img.merlion.ru/items/".$file['FileName'], 'r'));
                                $logger->message("try download file: "."http://img.merlion.ru/items/".$file['FileName']);
                                $curl = curl_init("http://img.merlion.ru/items/".$file['FileName']);
                                curl_setopt_array( $curl, array(
                                    CURLOPT_FILE            => $tmpfname,
                                    CURLOPT_SSL_VERIFYPEER  => FALSE,
                                    CURLOPT_SSL_VERIFYHOST  => FALSE,
                                    CURLOPT_HEADER          => FALSE,
                                    CURLOPT_TIMEOUT         => 60,
                                ));
                                curl_exec( $curl);
                                fclose($tmpfname);
                                curl_close( $curl);
                                $dowload_size = filesize($tmpf_name);
                            } catch (\Exception $e) {
                                $logger->message($e);
                            }
                            # $save_size = file_put_contents($tmpfname , file_get_contents("http://img.merlion.ru/items/".$file['FileName'], false, $context));
                            if((int)$dowload_size == (int)$file['Size']){
                                try {
                                    $command = "php " . Registry::get('config.dir')['root'] . "/" .Registry::get('config')['admin_index'] .  " --dispatch=api_merlion_images.check --p --path=" .escapeshellarg($tmpf_name) . " --cron_password=" . $cron_password ;
                                    $output=Array();
                                    $return_var=false;
                                    exec($command, $output, $return_var);
                                    $logger->message('check file:', $return_var===0? 'OK' : 'BAD');
                                    if($return_var === 0){
                                        $logger->message("file: ". $tmpf_name ." > download size: " . $dowload_size ."/ DB size: ". $file['Size']);
                                        rename($tmpf_name, $save_file);
                                        $logger->message("file: ". $tmpf_name ." > ".$save_file);
                                        chmod($save_file, 0770);
                                        chgrp($save_file , 'www-data');                                        
                                    }
                                    else{
                                        $logger->message("file: ".$file['hash']." remove from DB and dir!");
                                        db_query("DELETE FROM `?:api_merlion_images` WHERE `hash` = ?s ", $file['hash']);
                                        @unlink($save_file);
                                    }

                                } catch (\Exception $e) {
                                    $logger->message($e);
                                }
                            }
                            else{
                                $logger->message("ERROR > file: ". "http://img.merlion.ru/items/".$file['FileName'] ." > download size: " . $dowload_size."/ DB size: ". $file['Size']); 
                                $connects++;
                            }
                            @unlink($tmpf_name);
                        }
                        if($connects > 5 || $dowload_size){
                            break;
                        }                       
                    }
                }
                catch(Exception $e){
                    $logger->message($e);
                }
            }
        }
    }
}

function fn_api_merlion_features_sorting(){
    $products = fn_api_merlion_get_active_products(array('?:products.product_code','?:products.product_id'));
    $desc_fields = array(
        '?:product_feature_variants.variant_id',
        '?:product_feature_variants.position',
        '?:product_features_descriptions.description',
    );
    $desc_variant_join = " LEFT JOIN ?:product_feature_variants ON ?:product_feature_variants.variant_id = ?:product_features_values.variant_id ";
    $desc_variant_join .= "LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_feature_variants.feature_id";
    $feat_fields = array(
        '?:api_merlion_features.PropertyName',
        '?:api_merlion_features.Sorting',
    );
    foreach($products as $product){
        $variants = db_get_hash_array("SELECT ?p FROM ?:product_features_values ?p WHERE ?:product_features_values.product_id = ?i", 'description' ,db_quote(implode(' , ', $desc_fields)), $desc_variant_join, $product['product_id']);
        $features = db_get_hash_array( "SELECT ?p FROM ?:api_merlion_features WHERE ?:api_merlion_features.`No` = ?s" , 'PropertyName', db_quote(implode(' , ', $feat_fields)), $product['product_code']);
        if($variants && $features){
            foreach($features as $key => $value){
                if(array_key_exists($key, $variants)){
                    db_query("UPDATE ?:product_feature_variants SET ?:product_feature_variants.position = ?i WHERE ?:product_feature_variants.variant_id = ?i", $value['Sorting'], $variants[$key]['variant_id']);
                }
            }
        }
    }
}

function fn_api_merlion_create_features_description(){
    // select PropertyName from nakcs_api_merlion_features as t1
// left join nakcs_api_merlion_features_description as t2 on t2.description = t1.PropertyName
 // where t2.description is NULL group by t1.PropertyName
}

function fn_api_merlion_filters_create($settings){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    $company_id = fn_get_default_company_id();
    $filter_data_temp=array(
        "filter" => "",
        "company_id" => $company_id,
        "position" => 5000,
        "filter_type" => "",
        "round_to" => 0,
        "display" => "N",
        "display_count" => 10,
        "categories_path" => ""
    );
    $fields = array(
        "?:product_features.feature_id",
        "?:product_features.feature_type",
        "?:product_features_descriptions.description",
    );
    $join = " LEFT JOIN ?:product_filters ON ?:product_filters.feature_id = ?:product_features.feature_id ";
    $join .= " LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_features.feature_id ";
    $where = db_quote(" ?:product_filters.feature_id IS NULL ORDER BY ?:product_features.feature_id DESC");
    $filters = db_get_array("SELECT ?p FROM ?:product_features ?p WHERE ?p",db_quote(implode(' , ', $fields)), $join, $where);
    $logger->message('New filters: '.(string)count($filters));
    $fields = array(
        "?:products_categories.category_id"
    );
    $join = db_quote(" LEFT JOIN ?:products_categories ON ?:products_categories.product_id = ?:product_features_values.product_id ");
    foreach($filters as $filter){
        $disable = false;
        $where  = db_quote(" ?:product_features_values.feature_id = ?i ", $filter['feature_id']);
        $categories_path = db_get_fields("SELECT ?p FROM ?:product_features_values ?p WHERE ?p GROUP BY ?:products_categories.category_id ",db_quote(implode(' , ', $fields)), $join, $where);
        $filter_positions = db_get_fields("SELECT ?:product_feature_variants.position FROM ?:product_feature_variants WHERE ?:product_feature_variants.feature_id = ?i", $filter['feature_id']);
        $filter_data = $filter_data_temp;
        if ($filter['feature_type'] == ProductFeatures::NUMBER_FIELD || $filter['feature_type'] == ProductFeatures::NUMBER_SELECTBOX){
            $filter_data['filter_type'] = "R";
        }
        elseif($filter['feature_type'] == ProductFeatures::DATE){
            $filter_data['filter_type'] = "D";
        }
        else{
            $filter_data['filter_type'] = "F";
        }
        if($filter_positions){
            $filter_data['position'] = round(array_sum($filter_positions)/count($filter_positions))*10;
        }
        if($filter_data['position'] == 0 ){
            $filter_data['position'] = 5000;
            $disable = true;
        }
        $filter_data['filter_type'] = $filter_data['filter_type']."F-";
        $filter_data['filter'] = $filter['description'];
        $filter_data['filter_type'] = $filter_data['filter_type'] . $filter['feature_id'];
        $filter_data['categories_path'] = $categories_path ? trim(trim(implode(',', $categories_path)), ',') : "" ;
        if(!empty($filter_data['categories_path'])){
            $filter_id = fn_update_product_filter($filter_data, 0, DESCR_SL); 
            $share_data = array(
                'share_objects' => array(
                    'product_filters' => array(
                        $filter_id => array($company_id)
                    )
                )
            );     
            fn_ult_update_share_objects($share_data);
            if($settings['api_merlion_products_filters_enable'] == 'N' && $disable){
                db_query("UPDATE ?:product_filters SET ?u WHERE ?:product_filters.filter_id = ?i ", array("status" => "D"), $filter_id);
            }
        }
    }
    return count($filters);
}

function fn_api_merlion_binding_filters_to_categories(){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    $fields = array(
        "?:product_filters.filter_id"
    );
    $join = db_quote(" LEFT JOIN ?:products_categories ON ?:products_categories.category_id = ?:categories.category_id ");
    $join .= db_quote(" LEFT JOIN ?:product_features_values ON ?:product_features_values.product_id = ?:products_categories.product_id ");
    $join .= db_quote(" LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_features_values.feature_id ");
    $join .= db_quote(" LEFT JOIN ?:product_filters ON ?:product_filters.feature_id = ?:product_features.feature_id ");
    foreach(db_get_fields("SELECT ?:categories.category_id FROM ?:categories WHERE ?:categories.status = 'A'") as $category){
        $where  = db_quote("?:categories.category_id = ?i", $category);
        $filters = db_get_fields(
            "SELECT ?p FROM ?:categories ?p WHERE ?p AND ?:product_features.feature_id IS NOT NULL AND ?:product_filters.filter_id IS NOT NULL GROUP BY ?:product_filters.filter_id", 
            db_quote(implode(' , ', $fields)),
            $join,
            $where
        );
        if($filters){
            $filters = db_get_array("SELECT ?:product_filters.filter_id, ?:product_filters.categories_path FROM ?:product_filters WHERE ?:product_filters.filter_id IN (?n)", $filters);
            foreach($filters as $filter){
                $categories_path = explode(',', $filter['categories_path']);
                if(!in_array((string)$category, $categories_path)){
                    $categories_path[] = (string)$category;
                    db_query("UPDATE ?:product_filters SET ?:product_filters.categories_path = ?s WHERE ?:product_filters.filter_id = ?i", trim(trim(implode(',', $categories_path)), ','), $filter['filter_id']);
                }
            }
        }
    }
}

function fn_api_merlion_features_clear_category(){
    $result = db_get_hash_array("SELECT ?:product_features.feature_id, ?:product_features.categories_path FROM ?:product_features WHERE ?:product_features.categories_path  LIKE '%_%'", "feature_id");
    
    db_query("UPDATE ?:product_features SET ?u WHERE ?:product_features.feature_id IN (?n)", array("categories_path"=>""), array_keys($result));
    
    return $result;
}

function fn_api_merlion_features_set_category($update){
    foreach($update as $feature_id => $value){
        db_query("UPDATE ?:product_features SET ?u WHERE ?:product_features.feature_id = ?i", array("categories_path"=>$value['categories_path']), $feature_id);
    }
}

function fn_api_merlion_get_active_products($select_fields, $categories=array()){
    $join = db_quote(" LEFT JOIN ?:categories ON ?:categories.category_id = ?:api_merlion_groups.category_id ");
    $join .= db_quote(" LEFT JOIN ?:products_categories ON ?:products_categories.category_id = ?:categories.category_id ");
    $join .= db_quote(" LEFT JOIN ?:products ON ?:products.product_id = ?:products_categories.product_id ");
    $join .= db_quote(" LEFT JOIN ?:api_merlion_products ON ?:api_merlion_products.No = ?:products.product_code");
    $where = array(
        db_quote("?:api_merlion_groups.status = 'A'"),
        db_quote("?:categories.status = 'A'"),
        db_quote("?:products.status = 'A'"),
        db_quote("?:products.product_code IS NOT NULL"),
        db_quote("?:api_merlion_products.No IS NOT NULL"),
    );
    if(!empty($categories)){
        $where[] = db_quote("?:categories.category_id IN (?a) ", $categories);
    } 
    if (count($select_fields) == 1){
        return db_get_fields("SELECT ?p FROM ?:api_merlion_groups ?p WHERE ?p GROUP BY ?:products.product_code", db_quote(implode(' , ', $select_fields)), $join, implode(" AND ", $where)); 
    }
    elseif(count($select_fields) > 1){
        return db_get_array("SELECT ?p FROM ?:api_merlion_groups ?p WHERE ?p GROUP BY ?:products.product_code", db_quote(implode(' , ', $select_fields)), $join, implode(" AND ", $where)); 
    }
    else{
        return array();
    }
}

function fn_api_merlion_get_active_groups($categories=array()){
    $fields = array (
        '?:categories.category_id',
        '?:categories.id_path',
        '?:api_merlion_groups.group_id',
        '?:api_merlion_groups.comparison',
        '?:api_merlion_groups.partnumber_name',
    );
    $join = db_quote(' LEFT JOIN ?:api_merlion_groups ON ?:api_merlion_groups.category_id = ?:categories.category_id ');
    $where = array(
        db_quote(" WHERE ?:categories.status = 'A' AND ?:api_merlion_groups.status = 'A'"),
    );
    if(!empty($categories)){
        $where[] = db_quote("?:categories.category_id IN (?a) ", $categories);
    }
    return db_get_array('SELECT ?p FROM ?:categories ?p ?p',db_quote(implode(",", $fields)), $join, implode(" AND ", $where));
}

function fn_api_merlion_get_active_products_conditions($conditions=array(), $categories=array()){
    $join = db_quote(" LEFT JOIN ?:categories ON ?:categories.category_id = ?:api_merlion_groups.category_id ");
    $join .= db_quote(" LEFT JOIN ?:products_categories ON ?:products_categories.category_id = ?:categories.category_id ");
    $join .= db_quote(" LEFT JOIN ?:products ON ?:products.product_id = ?:products_categories.product_id ");
    $where = array(
        db_quote("?:api_merlion_groups.status = 'A'"),
        db_quote("?:categories.status = 'A'"),
        db_quote("?:products.status = 'A'"),
        db_quote("?:products.product_code IS NOT NULL"),
    );
    if(!empty($categories)){
        $where[] = db_quote("?:categories.category_id IN (?a) ", $categories);
    }    
    foreach($conditions as $join_table => $join_where){
        $join .= db_quote($join_table);
        $where[] = $join_where;
    }
    

    return db_get_fields("SELECT ?:products.product_code FROM ?:api_merlion_groups ?p WHERE ?p GROUP BY ?:products.product_code", $join, implode(" AND ", $where));
}

function fn_api_merlion_clear_unchecked($update_time, $settings){
    $update = array(
        'AvailableClient' => 0,
        'PriceClientRUB' => 0
    );
    $offline = $settings['api_merlion_import_offline'] == 'Y' ? true : false;
    if(!$offline){
        return db_query("UPDATE ?:api_merlion_products  SET ?u WHERE `check` != ?i AND `ActionNumber` = 0", $update, $update_time);
    }
    return 0;
}

function fn_api_merlion_nulled_products($settings, $check){
    $offline = $settings['api_merlion_import_offline'] == 'Y' ? true : false;
    if(!$offline){
        //$products = db_get_fields("SELECT `No` FROM ?:api_merlion_products WHERE `AvailableClient` = 0 AND `ActionNumber` = 0");
        $products = db_get_fields("SELECT ?:api_merlion_products.`No` FROM ?:api_merlion_products
left join ?:products on ?:products.product_code = ?:api_merlion_products.`No`
left join ?:images_links on ?:images_links.object_id = ?:products.product_id 
WHERE ((?:api_merlion_products.`AvailableClient` = 0 AND ?:api_merlion_products.`ActionNumber` = 0) or ?:images_links.object_id is null)
AND ?:api_merlion_products.check = ?i  AND ?:api_merlion_products.Source != 'A'
GROUP BY ?:api_merlion_products.`No`
", $check);

        if($products){
            return db_query("UPDATE ?:products  SET `amount` = 0 WHERE `product_code` IN (?a) ", $products);
        }
    }
    return 0;
}

function fn_api_merlion_nulled_products_on_all($settings, $check)
{
    $offline = $settings['api_merlion_import_offline'] == 'Y' ? true : false;
    if(!$offline){
        //$products = db_get_fields("SELECT `No` FROM ?:api_merlion_products WHERE `AvailableClient` = 0 AND `ActionNumber` = 0");
        $products = db_get_fields("SELECT ?:api_merlion_products.`No` FROM ?:api_merlion_products 
WHERE  ?:api_merlion_products.check != ?i AND ?:api_merlion_products.Source != 'A'", $check);

        if($products){
            return db_query("UPDATE ?:products  SET `amount` = 0 WHERE `product_code` IN (?a) ", $products);
        }
    }
    return 0;
}

function fn_api_merlion_yml_generate($code){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    $lang_code = DESCR_SL;
    if (Registry::isExist('languages.ru')) {
        $lang_code = 'ru';
    }
    $result = false;
    $company_id = fn_get_default_company_id();
    $price_id = fn_yml_get_price_id($code);
    $yml_enable = Settings::instance()->getSettingDataByName('api_merlion_yml_export_enable');
    $logger->message('YML enable', $yml_enable['value']);
    if($yml_enable['value'] == 'Y'){
        fn_set_progress('echo', $logger->message('YML generate...'), false);
        try{
            define('CONSOLE', true);
            $yml = new Yml2($company_id, $price_id, $lang_code, 0, true);
            $result = $yml->generate();
            # hack update url in file
            $config = Registry::get('config');
            $path = Registry::get('config.dir.files');
            $path .=  $company_id . '/';
            $filepath = $path . 'yml/' . 'ym' . '_' . $price_id . '.yml';
            $file = fopen($filepath, 'r+');
            $content = fread($file, filesize($filepath));
            fclose($file);
            $content = str_replace($config['admin_index'], $config['customer_index'], $content);
            $file = fopen($filepath, 'w');
            fwrite($file,$content);
            fclose($file);        
        }
        catch(Exception $e){
            $logger->message($e);
        }
    }
    return $result;
}

function fn_api_merlion_get_subcategory($categories, &$result){
    foreach($categories as $c){
        $result[]=$c;
        $get_categories = fn_get_subcategories($c);
        if($get_categories){
            $clear_categories = array();
            foreach($get_categories as $cc){
                $clear_categories[]=$cc['category_id'];
            }
            $get_categories=NULL;
            fn_api_merlion_get_subcategory($clear_categories, $result);
        }
    }
}

function fn_api_merlion_csv_to_array($filename='', $delimiter=',')
{
    if(!file_exists($filename) || !is_readable($filename))
        return FALSE;

    $header = NULL;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== FALSE)
    {
        while (($row = fgetcsv($handle, 16000, $delimiter)) !== FALSE)
        {
            if(!$header)
                $header = $row;
            else
                $data[] = array_combine($header, $row);
        }
        fclose($handle);
    }
    return $data;
}

function fn_api_merlion_save_akkom_products($to_table, $extra_fields){
    $logger = $GLOBALS['api_merlion_logger_products']->instance(__FUNCTION__);
    $product_for_update = array();
    $update = array(
        'AvailableClient' => 0,
        'PriceClientRUB' => 0
    );
    try{
        db_query("UPDATE ?:api_merlion_products SET `AvailableClient` = '0' WHERE `Source` = ?s", 'A');
        $saved = 0;
        foreach($to_table as $key => $value){
            if(array_key_exists('Product code', $value)){
                $value['Product code'] = trim($value['Product code']);
                if(!in_array($value['Product code'], $product_for_update)) {
                    $product_for_update[] = $value['Product code'];
                }
                $logger->message("get product:", var_export($value, true));
                // get merlion product
                if(array_key_exists('Merlion code', $value) && $value['Merlion code'] !== 0) {
                    $merlion_product = db_get_row("SELECT * FROM ?:api_merlion_products WHERE `No` = ?s", $value['Merlion code']);
                    $site_product = db_get_row(
                        "SELECT * FROM ?:products AS PR  ?p  WHERE PR.product_code = ?s",
                        db_quote(
                            implode( " ", array(
                                "LEFT JOIN ?:product_prices AS PRP ON PRP.product_id = PR.product_id",
                                "LEFT JOIN ?:product_sales AS PRS ON PRS.product_id = PR.product_id"
                            ))
                        ),
                        $value['Merlion code']
                    );
                    $logger->message("Merlion and site product", var_export(($merlion_product && $site_product), true));
                    if($merlion_product && $site_product) {
                        $product_for_update[] = $merlion_product['No'];
                        /*unset($merlion_product['id']); //\'id\' => \'27181\',
                        $merlion_product['No'] = $value['Product code']; // \'No\' => \'692114\',
                        $merlion_product['Name'] = $value['Product name'];// \'Name\' => \'Жесткий диск WD Original SATA-III 1Tb WD10EZEX Caviar Blue (7200rpm) 64Mb 3.5"\',
                        // \'Brand\' => \'WD\',
                        // \'Vendor_part\' => \'WD10EZEX\',
                        // \'Size\' => \'\',
                        // \'EOL\' => \'0\',
                        // \'Warranty\' => \'24\',
                        // \'Weight\' => \'0.5\',
                        // \'Volume\' => \'0.00142\',
                        // \'Min_Packaged\' => \'0\',
                        // \'GroupName1\' => \'КОМПЛЕКТУЮЩИЕ ДЛЯ КОМПЬЮТЕРОВ\',
                        // \'GroupName2\' => \'Жесткие Диски\',
                        // \'GroupName3\' => \'SATA\',
                        // \'GroupCode1\' => \'А1\',
                        // \'GroupCode2\' => \'А107\',
                        // \'GroupCode3\' => \'А10703\',
                        // \'IsBundle\' => \'0\',
                        // \'ActionDesc\' => \'\',
                        // \'ActionWWW\' => \'\',
                        // \'Last_time_modified\' => \'2017-10-22 16:07:51\',
                        unset($merlion_product['PriceClient']);// \'PriceClient\' => \'50\',
                        unset($merlion_product['PriceClient_RG']);// \'PriceClient_RG\' => \'50\',
                        unset($merlion_product['PriceClient_MSK']);// \'PriceClient_MSK\' => \'50\',
                        $merlion_product['AvailableClient'] = $value['Quantity'];// \'AvailableClient\' => \'0\',
                        unset($merlion_product['AvailableClient_RG']);// \'AvailableClient_RG\' => \'0\',
                        unset($merlion_product['AvailableClient_MSK']);// \'AvailableClient_MSK\' => \'2\',
                        unset($merlion_product['AvailableExpected']);// \'AvailableExpected\' => \'200\',
                        // \'AvailableExpectedNext\' => \'0\',
                        // \'DateExpectedNext\' => NULL,
                        $merlion_product['RRP'] = $value['List price'];// \'RRP\' => \'0\',
                        // \'RRP_Date\' => NULL,
                        $merlion_product['PriceClientRUB'] = $value['Price'];// \'PriceClientRUB\' => \'0\',
                        unset($merlion_product['PriceClientRUB_RG']);// \'PriceClientRUB_RG\' => \'2875.59\',
                        unset($merlion_product['PriceClientRUB_MSK']);// \'PriceClientRUB_MSK\' => \'2875.59\',
                        unset($merlion_product['Online_Reserve']);// \'Online_Reserve\' => \'2\',
                        unset($merlion_product['ReserveCost']);// \'ReserveCost\' => \'0.1432\',
                        // \'Category\' => \'Компьютеры и периферия///Комплектующие///Жесткие диски\',
                        // \'Comparison\' => \'Y\',
                        // \'PartnumberName\' => \'N\',
                        // \'Language\' => \'ru\',
                        unset($merlion_product['check']);// \'check\' => \'1508698802\',
                        $merlion_product['ActionNumber'] = (int)$value['Action Number'];// \'ActionNumber\' => \'0\',
                        // \'Short description\' => NULL,
                        $merlion_product['Source'] = 'A';// \'Source\' => \'M\',
                        $insert = $merlion_product;
                        if(!empty(trim($value['Short description']))) {
                            $Description = "<div class=\"product-spec-wrap__body\" style=\"box-sizing: border-box; margin-bottom: 30px; border-top-width: 0px; color: #2b2b2b; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: normal;\">";
                            $arrRows = explode("#", $value['Short description']);
                            foreach ($arrRows as $row) {
                                $arrList = explode(":", $row);
                                $Description .= "<dl class=\"product-spec\" id=\"product-spec-\" style=\"box-sizing: border-box; position: relative; margin: 0px; color: #404040; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;\">";
                                $Description .= "<dt class=\"product-spec__name\" style=\"box-sizing: border-box; margin: 0px 0px 7px; display: inline-block; width: 388.797px; vertical-align: top; background: inherit;\"><span class=\"product-spec__name-inner\" style=\"box-sizing: border-box; position: relative; padding: 0px 10px 0px 0px; z-index: 2; background: inherit;\">{$arrList[0]}</span></dt>";
                                $Description .= "<dd class=\"product-spec__value\" style=\"box-sizing: border-box; margin: 0px 0px 7px; display: inline-block; width: 421.188px; vertical-align: bottom; background: inherit;\"><span class=\"product-spec__value-inner\" style=\"box-sizing: border-box; position: relative; padding: 0px 0px 0px 10px; z-index: 2; display: block; background: inherit;\">{$arrList[1]}&nbsp;</span></dd>";
                                $Description .= "</dl>";
                            }
                            $Description .= "</div>";
                            $insert['ShortDescription'] = $Description;
                        } else {
                            $insert['ShortDescription'] = "";// \'Short description\' => NULL,
                        }
                        // copy images

                        $merlion_images = db_get_array("SELECT * FROM ?:api_merlion_images  WHERE `No` = ?s", $value['Merlion code']);

                        if (is_array($merlion_images) && count($merlion_images) > 0) {
                            $extra_fields_image = array(
                                'check' => $extra_fields['check']
                            );

                            foreach ($merlion_images as $key => $image) {
                                $image['No'] = $value['Product code'];
                                $image['hash'] = md5(implode('',array($image['No'], $image['SizeType'], $image['FileName'])));
                                $res = db_query('INSERT INTO ?:api_merlion_images ?e ON DUPLICATE KEY UPDATE ?u',array_merge($image, $extra_fields_image), array_merge($image, $extra_fields_image));
                                if($res) {
                                    $logger->message("image copy", var_export($image, true));
                                }
                            }
                        }

                        // copy features

                        $merlion_features = db_get_array("SELECT * FROM ?:api_merlion_features WHERE `No` = ?s", $value['Merlion code']);

                        if (is_array($merlion_images) && count($merlion_images) > 0) {
                            $extra_fields_feature = array(
                                'check' => $extra_fields['check']
                            );

                            foreach ($merlion_features as $feature) {
                                $feature['No'] = $value['Product code'];
                                $feature['hash'] = md5(implode(';',array($feature['No'],(string)$feature['PropertyID'],$feature['Value'])));
                                $res = db_query('INSERT INTO ?:api_merlion_features ?e ON DUPLICATE KEY UPDATE ?u',array_merge($feature, $extra_fields_feature), array_merge($feature, $extra_fields_feature));
                                if($res) {
                                    $logger->message("feature copy", var_export($feature, true));
                                }
                            }
                        }*/

                        $update_merlion = array();
                        $update_merlion['AvailableClient'] = $value['Quantity'];
                        $update_merlion['PriceClientRUB'] = ((float)$value['Price'] < (float)$site_product['price']) ? $value['Price'] : $site_product['price'];
                        $update_merlion['RRP'] = $value['List price'];
                        $update_merlion['Source'] = 'A';
                        $update_merlion['ActionNumber'] = (int)$value['Action Number'];
                        $update_merlion['check'] = $extra_fields['check'];
                        $res = db_query('INSERT INTO ?:api_merlion_products ?e ON DUPLICATE KEY UPDATE ?u',
                            array_merge($merlion_product, $update_merlion),
                            array_merge($merlion_product, $update_merlion)
                        );
                        if($res) {
                            $logger->message("Update merlion product [{$merlion_product['No']}]");
                            $saved++;
                        }
                        if((int)$value['Quantity'] > 0) {
                            $res = db_query("UPDATE ?:products SET `amount` = ?i WHERE `product_id` = ?i", $value['Quantity'], $site_product['product_id']);
                            if($res) {
                                $logger->message("Update site [{$merlion_product['No']}] set amount = {$value['Quantity'] }");
                            }
                        }
                        if((int)$value['List price'] > 0) {
                            $res = db_query("UPDATE ?:products SET `list_price` = ?d WHERE `product_id` = ?i", $value['List price'], $site_product['product_id']);
                            if($res) {
                                $logger->message("Update site [{$merlion_product['No']}] set list price = {$value['List price'] }");
                            }
                        }
                        if ((float)$value['Price'] < (float)$site_product['price']) {
                            $res = db_query("UPDATE ?:product_prices SET `price` = ?d WHERE `product_id` = ?i",$value['Price'] ,$site_product['product_id']);
                            if($res) {
                                $logger->message("Update site  [{$merlion_product['No']}] set price = {$value['Price'] }");
                            }
                        }
                        $insert = false;

                    }
                }
                if(!isset($insert)){
                    /*
                    'Product code' => 'Р8032',
                    'Product name' => '1Tb WD <7200rpm>64Mb SATA-III WD10EZEX Blue',
                    'Language' => 'ru',
                    'List price' => '3172.00',
                    'Price' => '2900.00',
                    'Status' => 'A',
                    'Quantity' => '34',
                    'Category' => '',
                    'Short description' => '',
                    'Merlion code' => '692114',
                     */
                    $insert = array();
                    if (empty($value['Category']))
                        continue;
                    $category = explode('/', $value['Category']);
                    if (count($category) > 0) {
                        array_shift($category);
                        $category = implode("///", $category);
                        $value['Category'] = $category;
                    } else {
                        $value['Category'] = '';
                    }
                    $insert['No'] = $value['Product code']; // \'No\' => \'692114\',
                    $insert['Name'] = $value['Product name'];// \'Name\' => \'Жесткий диск WD Original SATA-III 1Tb WD10EZEX Caviar Blue (7200rpm) 64Mb 3.5"\',
                    $insert['AvailableClient'] = $value['Quantity'];// \'AvailableClient\' => \'0\',
                    $insert['RRP'] = $value['List price'];// \'RRP\' => \'0\',
                    $insert['PriceClientRUB'] = $value['Price'];// \'PriceClientRUB\' => \'0\',
                    $insert['Language'] = $value['Language'];// \'PriceClientRUB\' => \'0\',
                    $insert['Category'] = $value['Category'];// \'PriceClientRUB\' => \'0\'
                    $insert['Comparison'] = 'N';// \'PriceClientRUB\' => \'0\'
                    $insert['PartnumberName'] = 'N';// \'PriceClientRUB\' => \'0\'
                    $insert['ActionNumber'] = (int)$value['Action Number'];// \'ActionNumber\' => \'0\',
                    if(!empty(trim($value['Short description']))) {
                        $Description = "<div class=\"product-spec-wrap__body\" style=\"box-sizing: border-box; margin-bottom: 30px; border-top-width: 0px; color: #2b2b2b; font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: normal;\">";
                        $arrRows = explode("#", $value['Short description']);
                        foreach ($arrRows as $row) {
                            $arrList = explode(":", $row);
                            $Description .= "<dl class=\"product-spec\" id=\"product-spec-\" style=\"box-sizing: border-box; position: relative; margin: 0px; color: #404040; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;\">";
	                        $Description .= "<dt class=\"product-spec__name\" style=\"box-sizing: border-box; margin: 0px 0px 7px; display: inline-block; width: 388.797px; vertical-align: top; background: inherit;\"><span class=\"product-spec__name-inner\" style=\"box-sizing: border-box; position: relative; padding: 0px 10px 0px 0px; z-index: 2; background: inherit;\">{$arrList[0]}</span></dt>";
	                        $Description .= "<dd class=\"product-spec__value\" style=\"box-sizing: border-box; margin: 0px 0px 7px; display: inline-block; width: 421.188px; vertical-align: bottom; background: inherit;\"><span class=\"product-spec__value-inner\" style=\"box-sizing: border-box; position: relative; padding: 0px 0px 0px 10px; z-index: 2; display: block; background: inherit;\">{$arrList[1]}&nbsp;</span></dd>";
	                        $Description .= "</dl>";
                        }
                        $Description .= "</div>";
                        $insert['ShortDescription'] = $Description;
                    } else {
                        $insert['ShortDescription'] = "";// \'Short description\' => NULL,
                    }
                    $insert['Source'] = 'A';// \'Source\' => \'M\',
                }

                if(!empty($insert)) {
                    if(!array_key_exists('AvailableClient', $insert)){
                        $insert = array_merge($insert, $update);
                    }

                    $logger->message("akkom insert: ", var_export($insert, true));
                    $logger->message("extra insert: ", var_export($extra_fields, true));
                    $res = db_query('INSERT INTO ?:api_merlion_products ?e ON DUPLICATE KEY UPDATE ?u',array_merge($insert,$extra_fields), array_merge($insert,$extra_fields));
                    $logger->message("insert result: ", $res);
                    if ($res) {
                        $saved++;
                    }
                }

                unset($insert);
            }
            else{
                $logger->message("Not found key [No]", $to_table);
            }
        }
        $logger->message("save products: ".(string)$saved);
    }
    catch(Exception $e){
        $logger->message($e, func_get_args());
    }

    return $product_for_update;
}

function fn_api_merlion_nulled_akkom_products($settings, $codes = array()){
    $offline = $settings['api_merlion_import_offline'] == 'Y' ? true : false;
    if(!$offline && count($codes) > 0){
        $products = db_get_fields("SELECT `No` FROM ?:api_merlion_products WHERE `Source` = 'A' AND `No` NOT IN (?a)", $codes);
        if($products){
            return db_query("UPDATE ?:products  SET `amount` = 0 WHERE `product_code` IN (?a) ", $products);
        }
    }
    return 0;
}

function fn_api_merlion_nulled_action_products($settings, $codes = array()) {
    $offline = $settings['api_merlion_import_offline'] == 'Y' ? true : false;
    if(!$offline && count($codes) > 0){
        return db_query("UPDATE ?:api_merlion_products  SET `ActionNumber` = 0 WHERE `No` NOT IN (?a) ", $codes);
    }
    return 0;
}