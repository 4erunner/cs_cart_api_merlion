<?php
$schema = array();

$schema['compare'] = array(
    "No" => "Product code",
    "RRP" => "List price",
    "PriceClientRUB" => "Price",
    "AvailableClient" => "Quantity",
    "Min_Packaged" => "Quantity step",
    "Language" => "Language",
);

$schema['values'] = array(
    "Product code" => "Product code",
    "Language" => "Language",
    "List price" => "List price",
    "Price" => "Price",
    "Store" => "",
    "Quantity" => "Quantity",
    "Quantity step" => "Quantity step",
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