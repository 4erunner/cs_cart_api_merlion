<?php
/*
*/
namespace Tygh;

use DateTime;

class ApiMerlionLogger
{
    private static $settings;
    public static $dir;
    private $controller = '';
    private $_function = '';
    private $file;
    private $pid;
    
    function __construct()
    {
        $args = func_get_args(); 
        $num = func_num_args();
        self::$settings = fn_api_merlion_settings();
        self::$dir = implode(DIRECTORY_SEPARATOR, array(rtrim(fn_get_files_dir_path(),'/'),'api_merlion','logs'));
        if(self::$settings['api_merlion_logging'] == 'Y' && $num == 1){
            $this->controller = $args[0];
            $this->file = implode(DIRECTORY_SEPARATOR, array(self::$dir, fn_api_merlion_create_date(NULL, "Ymd").'_'.($this->controller ? $this->controller : 'general').'.log'));
            $this->pid = (string)getmypid();
            
            fn_mkdir(self::$dir);
            if (!file_exists($this->file)) {
                fclose(fopen($this->file, 'w'));
                chmod($this->file, 0770 );
            }
            if (!is_writeable($this->file)) {
                // throw new \Exception($this->file ." is not a valid file path");
                error_log("ApiMerlionLogger.php: ".$this->file." is not a valid file path");
                error_log("ApiMerlionLogger.php: disable logging");
                self::$settings['api_merlion_logging'] = 'N';
            }
        }
    }
    
    public function message($message, $object = NULL, $force = false)
    { 
        $uid = (string)uniqid();
        $result = $message;
        $trace = false;
        if($message && gettype($message)=="object"){
                $result = $message->getMessage();
                if(defined('DEBUG_MODE')){
                    if(DEBUG_MODE){
                        $trace = $message->getTraceAsString();
                    }
                } elseif (isset(self::$settings['api_merlion_debug'])) {
                    if (self::$settings['api_merlion_debug']  == 'Y') {
                        $trace = $message->getTraceAsString();
                    }
                }
                $message = $result;
        }
        if(self::$settings['api_merlion_logging'] == 'Y' || $force){
            $datetime = new DateTime();
            $datetime =  $datetime->format(DATE_ATOM);
            if($message || $object !== NULL){
                if($message){
                    self::_put($uid, $datetime, $message);
                }
                if($trace){
                    self::_put($uid, $datetime, $trace);
                }
                if($object !== NULL){
                    self::_put($uid, $datetime, '' , $object);
                }
            }
        }
        return $result;
    }
    
    public function instance($function){
        $result = clone $this;
        $result->_function = $function;
        return $result;
    }
    
    private function _put($uid, $datetime, $message, $object = NULL){
        file_put_contents( 
            $this->file, 
            "#[".$this->pid. "][" .$uid. "][" .$datetime. "][" .$this->controller. "]".($this->_function ? "[" . $this->_function. "]" : "") . ($message ? " message > " . $message : "") . ($object ? " values > " . var_export($object, true) : "") . "\n", 
            FILE_APPEND
        );  
    }
}
