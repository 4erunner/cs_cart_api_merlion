<?php
$schema = array();

$schema['products_compare'] = array(
    "No" => "Product code",
    "Language" => "Language",
    "Brand" => ""
);

$schema['features_fields'] = array(
    'PropertyName',
    'Value',
);

$schema['features_txt'] = 'PropertyName: Type[Value]';

$schema['features_values'] = array(
    'PropertyName' => 'PropertyName',
    'Value' => 'Value',
    'Type' => 'S',
);

$schema['features_brand_txt'] = ': E[Brand]';

$schema['features_delimiter'] = '; ';

$schema['values'] = array(
    "Product code" => "Product code",
    "Language" => "Language",
    "Store" => "",
    "Features" => "",
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