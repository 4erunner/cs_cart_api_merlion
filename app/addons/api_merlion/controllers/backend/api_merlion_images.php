<?php
/*
*/
use Tygh\Settings;
use Tygh\ApiMerlionLogger;

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


$GLOBALS['api_merlion_logger_images'] = $logger = new ApiMerlionLogger('api_merlion_images');

//if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $logger->message(false, $_REQUEST);
    if ($mode == 'check') {
        $logger = $GLOBALS['api_merlion_logger_images']->instance('check');
        
        if (!empty($_REQUEST['path'])){
            /** @var \Imagine\Image\ImagineInterface $imagine */
            $imagine = Tygh::$app['image'];
            try {
                $image = $imagine->open($_REQUEST['path']);
                $logger->message(false, $image);
            } catch (\Exception $e) {
                $logger->message($e);
                fn_print_r('1');
                exit(1);
            }
            fn_print_r('0');
            exit(0);
        }
    }
//}