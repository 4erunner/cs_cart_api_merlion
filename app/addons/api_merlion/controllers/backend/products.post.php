<?php
 /*
*/

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }
$settings = fn_api_merlion_settings();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if ($mode == 'update') {
    if (Registry::get('runtime.company_id') && fn_allowed_for('ULTIMATE') || fn_allowed_for('MULTIVENDOR')) {
        Registry::set('navigation.tabs.api_merlion', array(
            'title' => __('api_merlion_menu'),
            'js' => true
        ));
    }
}
