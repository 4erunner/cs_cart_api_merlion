<?php
/*
*
*/

use Tygh\Settings; 
use Tygh\Registry; 

if ( !defined('AREA') ) { die('Access denied'); }

class ApiMerlionLocalValues{
    
    protected static $values = array();
    
    public function __get($name){
        if(array_key_exists($name, self::$values)){
            return self::$values[$name];
        }
        else{
            return NULL;
        }
    }
    
    public function __set($name, $value){
        self::$values[$name] = $value;
    }
}

$GLOBALS['api_merlion_local_values']  = new ApiMerlionLocalValues();

function fn_objectToArray($d) {
    if (is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }

    if (is_array($d)) {
        /*
        * Return array converted to object
        * Using __FUNCTION__ (Magic constant)
        * for recursive call
        */
        return array_map(__FUNCTION__, $d);
    }
    else {
    // Return array
    return $d;
    }
}

function fn_api_merlion_settings(){
    return Settings::instance()->getValues('api_merlion', 'ADDON')['api_merlion_config'];
}

function fn_api_merlion_create_date($date=NULL, $format='Y-m-d\TH:i:s'){
    try{
        switch(gettype($date)){
            case "integer":
                return date($format, $date ? $date : time());
                break;
            case "object":
                return $date->format($format);
                break;
            default:
                return date($format, time());
                break;
        }        
    }
    catch(Exception $e){
        error_log($e->getMessage());
    } 
}

function fn_api_merlion_get_date($date, $format='Y-m-d\TH:i:s', $timestamp = false){
    if($date){
        if($timestamp){
            return date_create_from_format($format, $date)->getTimestamp();
        }
        else{
            return date_create_from_format($format, $date);        
        }        
    }
    return '';
}

function fn_api_merlion_create_date_mysql($date, $format='Y-m-d H:i:s'){
    try{
        switch(gettype($date)){
            case "iteger":
                return date($format, $date ? $date : time());
                break;
            case "object":
                return $date->format($format);
                break;
        }        
    }
    catch(Exception $e){
        error_log($e->getMessage());
    } 
    return '';
}

function fn_api_merlion_install(){
    $install_schema = fn_get_schema('api_merlion', 'install', 'php', true);
    foreach($install_schema['table'] as $table => $value){
        db_query($value);
        foreach($install_schema['op']['install'] as $op){
            if(stristr($op, "INSERT")){
                preg_match_all('(`[^\s]*`)', $op, $match);
                if(!empty($match[0][1])){
                    if((int)db_get_row('SELECT count(*) AS `count` FROM '.str_replace('<table>', $table, $match[0][1]))['count']>0){
                        db_query(str_replace('<table>', $table, $op));
                    }
                }
            }
            elseif(stristr($op, "TRUNCATE")){
                if((int)db_get_row(str_replace('<table>', $table,'SELECT count(*) AS `count` FROM `?:backup_<table>`'))['count']>0){
                    db_query(str_replace('<table>', $table, $op));
                }
            }
            else{
                db_query(str_replace('<table>', $table, $op));
            } 
        }
    }
}

function fn_api_merlion_restore_settings(){
    $settings_path = implode(DIRECTORY_SEPARATOR, array(rtrim(Registry::get('config.dir')['root'], DIRECTORY_SEPARATOR), 'app', 'addons', 'api_merlion', 'settings'));
    if(file_exists($settings_path)){
        $settings = file_get_contents($settings_path);
        if($settings){
            $settings = unserialize($settings);
            foreach($settings as $setting){
                db_query("UPDATE ?:settings_objects SET `value` = ?s WHERE `name` = ?s", $setting['value'], $setting['name']);
            }
        }
    }
    @unlink($settings_path);
}

function fn_api_merlion_uninstall(){
    $install_schema = fn_get_schema('api_merlion', 'install');
    foreach($install_schema['table'] as $table => $value){
        db_query($value);
        foreach($install_schema['op']['uninstall'] as $op){
            if(stristr($op, "INSERT")){
                preg_match_all('(`[^\s]*`)', $op, $match);
                if(!empty($match[0][1])){
                    if((int)db_get_row('SELECT count(*) AS `count` FROM '.str_replace('<table>', $table, $match[0][1]))['count']>0){
                        db_query(str_replace('<table>', $table, $op));
                    }
                }
            }
            else{
                db_query(str_replace('<table>', $table, $op));
            }
        }
    }
}

function fn_api_merlion_save_settings(){
    $settings_path = implode(DIRECTORY_SEPARATOR, array(rtrim(Registry::get('config.dir')['root'], DIRECTORY_SEPARATOR), 'app', 'addons', 'api_merlion', 'settings'));
    $settings = db_get_array("SELECT `name`, `value` FROM ?:settings_objects WHERE `name` LIKE ?l", 'api_merlion%');
    file_put_contents($settings_path, serialize($settings));
}

function get_http_response_code($url) {
    $headers = get_headers($url);
    return substr($headers[0], 9, 3);
}

function fn_api_merlion_get_product_features_post(&$data, $params = NULL, $has_ungroupped = NULL, &$position = 0){
    if(($runtime = $GLOBALS['api_merlion_local_values']->registry_runtime) === NULL){
        $GLOBALS['api_merlion_local_values']->registry_runtime = $runtime = Registry::get('runtime');
    }
    if ($runtime['controller'] == 'products' && $runtime['mode'] == 'view' && $data){
        if(($settings = $GLOBALS['api_merlion_local_values']->api_merlion_settings) === NULL){
            $GLOBALS['api_merlion_local_values']->api_merlion_settings = $settings = fn_api_merlion_settings();
        }
        if(empty($settings['api_merlion_sort_features']) || $settings['api_merlion_sort_features'] == 'Y'){
            $sort_data = array();
            $sort_count = 0;
            foreach($data as $key => $value){
                if(!empty($value['variants'])){
                    $variant_position = 0;
                    fn_api_merlion_get_product_features_post($value['variants'], null, null, $variant_position);
                    $sort_data[$variant_position][] = $value;
                    $position += $variant_position;
                }
                elseif(!empty($value['subfeatures'])){
                    $subfeatures_position = 0;
                    fn_api_merlion_get_product_features_post($value['subfeatures'], null, null, $subfeatures_position);
                    $sort_data[$subfeatures_position][] = $value;
                }
                elseif(array_key_exists('position',$value)){
                    $position += (int)$value['position'];
                    $sort_data[(int)$value['position']][] = $value;
                }
                $sort_count++;
            }
            if($data){
                $position = round($position/$sort_count);
            }
            ksort($sort_data);
            $data = array();
            foreach($sort_data as $sorting){
                foreach($sorting as $sort){
                    $data[] = $sort;
                }
            }    
        }
    }
}

function fn_api_merlion_get_order_info($order, $additional_data){
    fn_set_session_data('api_merlion_order_products', array('order_id' => $order['order_id'], 'products' => $order['products']), COOKIE_ALIVE_TIME);
}

?>
