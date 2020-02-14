<?php
/*
*/
namespace Tygh;

use Tygh\Registry;
use Tygh\ApiMerlionLogger;
use SoapClient;
use SoapVar;
use SoapFault;
use SoapParam;

class ApiMerlion{
    
    private static $wdsl_work_url = 'https://api.merlion.com/dl/mlservice3?wsdl';
    private static $wdsl_test_url = 'https://apitest.merlion.com/dl/mlservice3?wsdl';
    private static $client_url = NULL;
    private static $_client = NULL;
    private static $_settings;
    private static $api_schema;
    private static $logger;
    public static $error;
    public static $status;
       
    
    public static function connect($reconnect = False)
    {
        self::$_settings = fn_api_merlion_settings();
        self::$logger = new ApiMerlionLogger('ApiMerlion');
        $url = self::$_settings['api_merlion_version'] == "work" ? self::$wdsl_work_url : self::$wdsl_test_url;
        self::$status = $result = false;
        if(!self::$_client && !$reconnect && self::$client_url == $url){
            self::$status = $result = true;
        }
        else{
            ini_set("soap.wsdl_cache_enabled", "0");
            self::$client_url = $url;
            self::$api_schema = fn_get_schema('api_merlion', 'api');
            if(!empty(self::$_settings['api_merlion_user']) && !empty(self::$_settings['api_merlion_password'])){
                try {
                    self::$_client = new SoapClient(
                        self::$client_url, 
                        array(
                            'login'=> self::$_settings['api_merlion_user'],
                            'password' => self::$_settings['api_merlion_password'],
                            "stream_context" => stream_context_create(
                                array(
                                    'ssl' => array(
                                        'verify_peer'       => false,
                                        'verify_peer_name'  => false,
                                    ),
									'http' => array(
										'user_agent' => 'PHPSoapClient',
										'ignore_errors' => true
									)
									
                                )
                            ),
							'user_agent' => 'PHPSoapClient',
							'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
							'trace' => 1, 
							'exceptions' => true, 
							'cache_wsdl' => WSDL_CACHE_NONE,
							'features' => SOAP_SINGLE_ELEMENT_ARRAYS
                        )
                    );
                    $result = true;
                    self::$status = true;
                } 
                catch (SoapFault $e) {
                    self::$logger->message($e->faultstring);
                    self::$error = __('api_merlion_errors.error_connection');
                }
                catch(Exception $e){
                    self::$logger->message($e);
                    self::$error = __('api_merlion_errors.error_connection');
                }            
            }
            else{
                self::$error = __('api_merlion_errors.error_authorization');
            }
        }
        if(self::$_settings['api_merlion_version'] != "work"){
            fn_set_notification('E', __('notice'), 'API MERLION in TEST MODE');
        }
        return $result;
    }
    
    public static function getShipmentDates(){
        /*
                getShipmentDates {
                 string code;
                }
            
            getShipmentDatesResponse getShipmentDates(getShipmentDates $parameters)
            
                getShipmentDatesResponse {
                 ArrayOfShipmentDatesResult getShipmentDatesResult;
                }
                
                ArrayOfShipmentDatesResult {
                 ShipmentDatesResult item;
                }
                
                ShipmentDatesResult {
                 string Date;
                }

        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args());
    }
    
    public static function getShipmentMethods(){
        /*
                struct getShipmentMethods {
                 string code;
                }
            
            getShipmentMethodsResponse getShipmentMethods(getShipmentMethods $parameters)
            
                struct getShipmentMethodsResponse {
                 ArrayOfShipmentMethodsResult getShipmentMethodsResult;
                }
                
                struct ArrayOfShipmentMethodsResult {
                 ShipmentMethodsResult item;
                }
                
                struct ShipmentMethodsResult {
                 string Code;
                 string Description;
                 int IsDefault;
                }
        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args());
    }
    
    public static function getShipmentAgents(){
        /*
                struct getShipmentAgents {
                 string code;
                }
            
            getShipmentAgentsResponse getShipmentAgents(getShipmentAgents $parameters)
            
                struct getShipmentAgentsResponse {
                 ArrayOfDictionaryResult getShipmentAgentsResult;
                }
                
                struct ArrayOfDictionaryResult {
                    DictionaryResult item;
                }
                
                struct DictionaryResult {
                    string Code;
                    string Description;
                }
        */
        
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args());
    }
    
    public static function getCounterAgent(){
        /*
                struct getCounterAgent {
                 string code;
                }
            
            getCounterAgentResponse getCounterAgent(getCounterAgent $parameters)
            
                struct getCounterAgentResponse {
                    ArrayOfDictionaryResult getCounterAgentResult;
                }
                
                struct ArrayOfDictionaryResult {
                    DictionaryResult item;
                }
                
                struct DictionaryResult {
                    string Code;
                    string Description;
                }
        */
        
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args());
    }

    public static function getRepresentative(){
        /*
        
                struct getRepresentative {
                    string CounterAgentCode;
                }
            
            getRepresentativeResponse getRepresentative(getRepresentative $parameters)
            
                struct getRepresentativeResponse {
                    ArrayOfRepresentativeResult getRepresentativeResult;
                }
                
                struct ArrayOfRepresentativeResult {
                    RepresentativeResult item;
                }
                
                struct RepresentativeResult {
                    string Representative;
                    string CounterAgentCode;
                    string StartDate;
                    string EndDate;
                }
            
        */
        
        self::$error = self::$status  = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }
    
    public static function getEndPointDelivery(){
        /*
        
                struct getEndPointDelivery {
                    int id;
                    string ShippingAgentCode;
                }
                
            getEndPointDeliveryResponse getEndPointDelivery(getEndPointDelivery $parameters)
            
                struct getEndPointDeliveryResponse {
                    ArrayOfEndPointDeliveryResult getEndPointDeliveryResult;
                }

                struct ArrayOfEndPointDeliveryResult {
                    EndPointDeliveryResult item;
                }
        
                struct EndPointDeliveryResult {
                    int ID;
                    string Endpoint_address;
                    string Endpoint_contact;
                    string ShippingAgentCode;
                    string City;
                }
        
        */
        
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }
    
    public static function getPackingTypes(){
        /*
        
                struct getPackingTypes {
                    string code;
                }
            
            getPackingTypesResponse getPackingTypes(getPackingTypes $parameters)
            
                struct getPackingTypesResponse {
                    ArrayOfDictionaryResult getPackingTypesResult;
                }
                
                struct ArrayOfDictionaryResult {
                    DictionaryResult item;
                }
                
                struct DictionaryResult {
                    string Code;
                    string Description;
                }
        
        */
        
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }
    
    public static function getCountry(){
        /*
        
                struct getCountry {
                    string code;
                }
            
            getCountryResponse getCountry(getCountry $parameters)
            
                struct getCountryResponse {
                    ArrayOfDictionaryResult getCountryResult;
                }
                
                struct ArrayOfDictionaryResult {
                    DictionaryResult item;
                }
                
                struct DictionaryResult {
                    string Code;
                    string Description;
                }
        
        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }
    
    public static function getCurrencyRate(){
        /*
            
                struct getCurrencyRate {
                    string date;
                }
            
            getCurrencyRateResponse getCurrencyRate(getCurrencyRate $parameters)
            
                struct getCurrencyRateResponse {
                    ArrayOfCurrencyRateResult getCurrencyRateResult;
                }
            
                struct ArrayOfCurrencyRateResult {
                    CurrencyRateResult item;
                }
            
                struct CurrencyRateResult {
                    string Code;
                    string Date;
                    float ExchangeRate;
                }
            
        */
        
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }
    
    public static function getCatalog(){
        /*
                struct getCatalog {
                 string cat_id;
                }        
            
            getCatalogResponse getCatalog(getCatalog $parameters)

                struct getCatalogResponse {
                 ArrayOfCatalogResult getCatalogResult;
                }
                
                struct ArrayOfCatalogResult {
                 CatalogResult item;
                }    
                
                struct CatalogResult {
                 string ID;
                 string ID_PARENT;
                 string Description;
                }
        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args());
    }
    
    public static function getItems(){
        $result = false;
        /*
                struct getItems {
                    string cat_id;
                    ArrayOfString item_id;  =>  struct ArrayOfString {
                                                    string item;
                                                }
                    string shipment_method;
                    int page;
                    int rows_on_page;
                    string last_time_change;
                }
            
            getItemsResponse getItems(getItems $parameters)
            
                struct getItemsResponse {
                    ArrayOfItemsResult getItemsResult;
                }
                
                struct ArrayOfItemsResult {
                    ItemsResult item;
                }
                
                struct ItemsResult {
                    string No;
                    string Name;
                    string Brand;
                    string Vendor_part;
                    string Size;
                    int EOL;
                    int Warranty;
                    float Weight;
                    float Volume;
                    int Min_Packaged;
                    string GroupName1;
                    string GroupName2;
                    string GroupName3;
                    string GroupCode1;
                    string GroupCode2;
                    string GroupCode3;
                    int IsBundle;
                    string ActionDesc;
                    string ActionWWW;
                    string Last_time_modified;
                }
        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 2);
    }
    
    public static function getItemsAvail(){
        $result = false;
        /*
                struct getItemsAvail {
                    string cat_id;
                    string shipment_method;
                    string shipment_date;
                    string only_avail;
                    ArrayOfString item_id;  =>  struct ArrayOfString {
                                                    string item;
                                                }
                }            
            
            getItemsAvailResponse getItemsAvail(getItemsAvail $parameters)
            
                struct getItemsAvailResponse {
                    ArrayOfItemsAvailResult getItemsAvailResult;
                }
                
                struct ArrayOfItemsAvailResult {
                    ItemsAvailResult item;
                }
                
                struct ItemsAvailResult {
                    string No;
                    float PriceClient;
                    float PriceClient_RG;
                    float PriceClient_MSK;
                    int AvailableClient;
                    int AvailableClient_RG;
                    int AvailableClient_MSK;
                    int AvailableExpected;
                    int AvailableExpectedNext;
                    string DateExpectedNext;
                    float RRP;
                    string RRP_Date;
                    float PriceClientRUB;
                    float PriceClientRUB_RG;
                    float PriceClientRUB_MSK;
                    int Online_Reserve;
                    float ReserveCost;
                }
        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args());
    }
    
    public static function getItemsProperties(){
        /*
                struct getItemsProperties {
                    string cat_id;
                    ArrayOfString item_id;  =>  struct ArrayOfString {
                                                    string item;
                                                }
                    int page;
                    int rows_on_page;
                    string last_time_change;
                }
                
            getItemsPropertiesResponse getItemsProperties(getItemsProperties $parameters)
            
                struct getItemsPropertiesResponse {
                    ArrayOfItemsPropertiesResult getItemsPropertiesResult;
                }
                
                struct ArrayOfItemsPropertiesResult {
                    ItemsPropertiesResult item;
                }
                
                struct ItemsPropertiesResult {
                    string No;
                    int PropertyID;
                    string PropertyName;
                    int Sorting;
                    string Value;
                    string Last_time_modified;
                }
            
        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }
    
    public static function getItemsImages(){
        /*
            
                struct getItemsImages {
                    string cat_id;
                    ArrayOfString item_id;  =>  struct ArrayOfString {
                                                    string item;
                                                }
                    int page;
                    int rows_on_page;
                    string last_time_change;
                }
            
            getItemsImagesResponse getItemsImages(getItemsImages $parameters)
            
                struct getItemsImagesResponse {
                    ArrayOfItemsImagesResult getItemsImagesResult;
                }
                
                struct ArrayOfItemsImagesResult {
                    ItemsImagesResult item;
                }
                
                struct ItemsImagesResult {
                    string No;
                    string ViewType;
                    string SizeType;
                    string FileName;
                    string Created;
                    int Size;
                    int Width;
                    int Height;
                }
            
        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }
    
    public static function getOrdersList(){
        /*
        
                struct getOrdersList {
                    string document_no;
                }

            getOrdersListResponse getOrdersList(getOrdersList $parameters)
            
                struct getOrdersListResponse {
                    ArrayOfOrdersListResult getOrdersListResult;
                }
                
                struct ArrayOfOrdersListResult {
                    OrdersListResult item;
                }
                
                struct OrdersListResult {
                    string document_no;
                    string PostedDocumentNo;
                    string TNN;
                    string OrderDate;
                    string Manager;
                    string Contact;
                    string ShipmentMethod;
                    string ShipmentMethodCode;
                    string ShipmentDate;
                    string ActualShipmentDate;
                    string CounterpartyClient;
                    string CounterpartyClientCode;
                    string ShippingAgent;
                    string ShippingAgentCode;
                    string EndCustomer;
                    string PostingDescription;
                    string Weight;
                    float Volume;
                    float Amount;
                    float AmountRUR;
                    string WillDeleteTomorrow;
                    string Status;
                    string EndPointCity;
                    string EndPointAdress;
                    string EndPointContact;
                    string PackingType;
                    string Representative;
                }

        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }
    
    public static function getOrderLines(){
        /*
                
                struct getOrderLines {
                    string document_no;
                    string details;
                }
                
            getOrderLinesResponse getOrderLines(getOrderLines $parameters)
        
                struct getOrderLinesResponse {
                    ArrayOfOrderLinesResult getOrderLinesResult;
                }
                
                struct ArrayOfOrderLinesResult {
                    OrderLinesResult item;
                }
        
                struct OrderLinesResult {
                    string item_no;
                    string document_no;
                    int qty;
                    int desire_qty;
                    int shipped_qty;
                    float price;
                    float amount;
                    float desire_price;
                    float weight;
                    float volume;
                    float ReserveTime;
                }
        
        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }
        
    public static function getCommandResult(){
        /*
        
                struct getCommandResult {
                    string operation_no;
                }
            
            getCommandResultResponse getCommandResult(getCommandResult $parameters)
            
                struct getCommandResultResponse {
                    ArrayOfCommandResult getCommandResultResult;
                }
                
                struct ArrayOfCommandResult {
                    CommandResult item;
                }
                
                struct CommandResult {
                    int operation_no;
                    string CreateTime;
                    string ProcessingTime;
                    string EndingTime;
                    string ProcessingResult;
                    string DocumentNo;
                    string DocumentNo2;
                    string ProcessingResultComment;
                    string ErrorText;
                    string ProcessingReserved;
                    int OperationLineNo;
                }
            
        */
        $logger = self::$logger->instance(__FUNCTION__);
        self::$error = self::$status = $result = false;
        $i = 0;
        $j = 0;
        while ($i < 10){
            sleep(5);
            try {
                $result = self::_call(__FUNCTION__, func_get_args(), 1);
                if(array_key_exists('operation_no', $result)){
                    $logger->message('[getCommandResult] return: ', $result);
                    if(self::$api_schema[__FUNCTION__]['results'][$result['ProcessingResult']]){
                        break;
                    }
                    else{
                        $result = false;
                        if(self::$api_schema[__FUNCTION__]['results'][$result['ProcessingResult']] == "Обработка"){
                            if($j > 10){
                                $i = 11;
                            }
                            $i--;
                            $j++;
                            sleep(15);
                        }
                    }                
                }
            }
            catch (Exception $e){
                self::$logger->message($e);
                if(!empty($result)){
                    self::$logger->message('response: ', $result);
                }
            }
            $i++;
        }
        return $result;
    }    
    
    public static function setOrderHeaderCommand(){
        /*
        
                struct setOrderHeaderCommand {
                    string document_no;
                    string shipment_method;
                    string shipment_date;
                    string counter_agent;
                    string shipment_agent;
                    string end_customer;
                    string comment;
                    string representative;
                    int endpoint_delivery_id;
                    string packing_type;
                }
        
            setOrderHeaderCommandResponse setOrderHeaderCommand(setOrderHeaderCommand $parameters)
            
                struct setOrderHeaderCommandResponse {
                    int setOrderHeaderCommandResult;
                }
        
        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }
    
    public static function setDeleteOrderCommand(){
        /*
        
                struct setDeleteOrderCommand {
                    string document_no;
                }
            
            setDeleteOrderCommandResponse setDeleteOrderCommand(setDeleteOrderCommand $parameters)
            
                struct setDeleteOrderCommandResponse {
                    int setDeleteOrderCommandResult;
                }
        
        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }    

    public static function setOrderLineCommand(){
        /*
        
                struct setOrderLineCommand {
                    string document_no;
                    string item_no;
                    int qty;
                    float price;
                }
        
            setOrderLineCommandResponse setOrderLineCommand(setOrderLineCommand $parameters)
            
                struct setOrderLineCommandResponse {
                    int setOrderLineCommandResult;
                }
        
        */
        self::$error = self::$status = false;
        return self::_call(__FUNCTION__, func_get_args(), 1);
    }
    
    // public static function METHOD(){
        // self::$status = $result = false;
        // return self::_call(__FUNCTION__, func_get_args(), 1);
    // }
    
    private static function _call($func, $args, $force_timeout=0){
        $result = self::_try_function($func, $args, $force_timeout);
        if($result){
            try{
                $result = self::_response($func, $result);
                self::$status = true;
            }
            catch(Exception $e){
                self::$error = self::$logger->message($e, func_get_args());
            }            
        }
        return $result;
    }
        
    private static function _try_function($func, $args, $force_timeout=0){
        $result = false;
        $i = 0;
        while ($i < 10) {
            $timeout = 2;
            try {
                $result = call_user_func_array(array(self::$_client, $func), array(self::_parameters($func, $args)));
                break;
            } 
            catch (SoapFault $e){
                self::$error = self::$logger->message($e, func_get_args());
                self::$logger->message(false, $result);
                if (defined('AJAX_REQUEST')){
                    fn_set_progress('echo', 'ApiMerlion get data error: '.self::$error, false);
                }
                if(strpos(trim(self::$error), 'Rate limit') === 0 && strpos(self::$error, $func)){
                    $pattern = '/.*exceeded\:\s+\(([0-9]+) times for ([0-9]+) seconds\).*/';
                    preg_match($pattern, self::$error, $matches);
                    if(!empty($matches[2])){
                        $timeout = (int)$matches[2];
                        fn_set_notification('E', __('notice'), 'Wait: '.$timeout.'! Error:'.self::$error);
                        if (defined('AJAX_REQUEST')){
                            fn_set_progress('echo', 'Wait: '.$timeout.'! Error:'.self::$error, false);
                        }
                    }
                }
                elseif(trim(self::$error) == 'Database Error!'){
                    $result = false;
                    break;
                }
            }
            catch(Exception $e){
                self::$error = self::$logger->message($e, func_get_args());
                if (defined('AJAX_REQUEST')){
                    fn_set_progress('echo', 'ApiMerlion get data error: '.self::$error, false);
                }
            }     
            sleep($timeout);
            $i++;
        }
        if($force_timeout){
            sleep($force_timeout);
        }
        return $result;
    }
    
    private static function _parameters($func, $args){
        $result = array();
        try{
            $index = 0;
            foreach(self::$api_schema[$func]['parameters'] as $key => $value){
                if(array_key_exists($index, $args)){
                    if(gettype($args[$index]) != $value){
                        settype($args[$index],$value);
                    }
                    $result[$key] = $args[$index];
                }
                $index++;
            }
        }catch(Exception $e){
            self::$error = self::$logger->message($e, func_get_args());
        }
        return $result;
    }
    
    
    private static function _response($func, $response){
        $result = null;
        try{
            $response = fn_objectToArray($response);
            foreach(self::$api_schema[$func]['response'] as $value){
                $response = $response[$value];
            }
            $result = $response;
        }catch(Exception $e){
            self::$error = self::$logger->message($e, func_get_args());
        }
        return $result;
    }

}