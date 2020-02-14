<?php

$schema = array();

$schema['getShipmentDates'] = array(
    'parameters' => array(
        'code' => 'string',
        'ShipmentMethodCode' => 'string',
    ),
    'response' => array(
        'getShipmentDatesResult',
        'item'
    ),
);

$schema['getShipmentMethods'] = array(
    'parameters' => array(
        'code' => 'string',
    ),
    'response' => array(
        'getShipmentMethodsResult',
        'item',
    ),
);

$schema['getShipmentAgents'] = array(
    'parameters' => array(
        'code' => 'string',
    ),
    'response' => array(
        'getShipmentAgentsResult',
        'item',
    ),
);

$schema['getCounterAgent'] = array(
    'parameters' => array(
        'code' => 'string',
    ),
    'response' => array(
        'getCounterAgentResult',
        'item'
    ),
);

$schema['getRepresentative'] = array(
    'parameters' => array(
        'CounterAgentCode' => 'string',
    ),
    
    'response' => array(
        'getRepresentativeResult',
        'item'
    ),
);    

$schema['getEndPointDelivery'] = array(
    'parameters' => array(
        'id' => 'integer',
        'ShippingAgentCode' => 'string'
    ),
    
    'response' => array(
        'getEndPointDeliveryResult',
        'item'
    ),
); 

$schema['getPackingTypes'] = array(
    'parameters' => array(
        'сode' => 'string'
    ),
    
    'response' => array(
        'getPackingTypesResult',
        'item'
    ),
);     

$schema['getCatalog'] = array(
    'parameters' => array(
        'cat_id' => 'string',
    ),
    'response' => array(
        'getCatalogResult',
        'item'
    ),
); 

$schema['getItems'] = array(
    'parameters' => array(
        'cat_id' => 'string',
        'item_id' => 'array',
        'shipment_method' => 'string',
        'page' => 'integer',
        'rows_on_page' => 'integer',
        'last_time_change' => 'string',
    ),
    'response' => array(
        'getItemsResult',
        'item'
    ),
);

$schema['getItemsAvail'] = array(
    'parameters' => array(
        'cat_id' => 'string',
        'shipment_method' => 'string',
        'shipment_date' => 'string',
        'only_avail' => 'string',
        'item_id' => 'array',
    ),
    
    'response' => array(
        'getItemsAvailResult',
        'item'
    ),
);
 
$schema['getItemsProperties'] = array(
    'parameters' => array(
        'cat_id' => 'string',
        'item_id'  =>  'array',
        'page' => 'integer',
        'rows_on_page' => 'integer',
        'last_time_change' => 'string',
    ),
    
    'response' => array(
        'getItemsPropertiesResult',
        'item'
    ),
);

$schema['getItemsImages'] = array(
    'parameters' => array(
        'cat_id' => 'string',
        'item_id'  =>  'array',
        'page' => 'integer',
        'rows_on_page' => 'integer',
        'last_time_change' => 'string',
    ),
    
    'response' => array(
        'getItemsImagesResult',
        'item'
    ),
);

$schema['getOrdersList'] = array(
    'parameters' => array(
        'document_no' => 'string',
    ),
    
    'response' => array(
        'getOrdersListResult',
        'item'
    ),
); 
   
$schema['getOrderLines'] = array(
    'parameters' => array(
        'document_no' => 'string',
        'details' => 'string'
    ),
    
    'response' => array(
        'getOrderLinesResult',
        'item'
    ),
);  
   
$schema['getCommandResult'] = array(
    'parameters' => array(
        'operation_no' => 'string',
    ),
    
    'response' => array(
        'getCommandResultResult',
        'item'
    ),
    
    'results' => array(
        "Активно" => false,
        "Подготовка" => false,
        "Обработка" => false,
        "Сделано" => true,
        "Ошибка" => true,
        "Отменено" => true,
    ),
);

$schema['setOrderHeaderCommand'] = array(
    'parameters' => array(
        'document_no' => 'string',
        'shipment_method' => 'string',
        'shipment_date' => 'string',
        'counter_agent' => 'string',
        'shipment_agent' => 'string',
        'end_customer' => 'string',
        'comment' => 'string',
        'representative' => 'string',
        'endpoint_delivery_id' => 'integer',
        'packing_type' => 'string',
    ),
    
    'response' => array(
        'setOrderHeaderCommandResult'
    ),
);     

$schema['setDeleteOrderCommand'] = array(
    'parameters' => array(
        'document_no' => 'string'
    ),
    
    'response' => array(
        'setDeleteOrderCommandResult'
    ),
);     

$schema['setOrderLineCommand'] = array(
    'parameters' => array(
        'document_no' => 'string',
        'item_no' => 'string',
        'qty' => 'integer',
        'price' => 'float',
    ),
    
    'response' => array(
        'setOrderLineCommandResult'
    ),
);    

// $schema[''] = array(
    // 'parameters' => array(
    
    // ),
    
    // 'response' => array(
    
    // ),
// );     
return $schema;      