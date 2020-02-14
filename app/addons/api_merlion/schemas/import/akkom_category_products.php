<?php
$schema = array();

$schema['compare'] = array(
    "No" => "Product code",
    "Name" => "Product name",
    "RRP" => "List price",
    "PriceClientRUB" => "Price",
    "AvailableClient" => "Quantity",
    "Min_Packaged" => "Quantity step",
    "Weight" => "Weight",
    "Category" => "Category",
    "Language" => "Language",
    "Comparison" => "Feature comparison",
    "ShortDescription" => "Description"
);

$schema['values'] = array(
    "Product code" => "Product code",
    "Language" => "Language",
    "Product name" => "Product name",
    "Category" => "Category",
    "List price" => "List price",
    "Price" => "Price",
    "Quantity" => "Quantity",
    "Feature comparison" => "Feature comparison",
    "Store" => "",    
    "Quantity step" => "Quantity step",
    "Weight" => "Weight",
    "Description" => "Description"
);

$schema['import_options'] = array (
  'category_delimiter' => '///',
  'features_delimiter' => '///',
  'images_path' => 'exim/backup/images/',
  'files_path' => 'exim/backup/downloads/',
  'delete_files' => 'N',
  'reset_inventory' => 'N',
  'price_dec_sign_delimiter' => '.',
  'delimiter' => 'T',
);

return $schema;