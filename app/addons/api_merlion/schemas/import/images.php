<?php
$schema = array();

// Product code
// Pair type - М А
// Thumbnail
// Detailed image - name 
// Position

$schema['images_values'] = array(
    'hash' => 'hash',
    'No' => 'No',
    'FileName' => 'FileName',
);

$schema['values'] = array(
    "Product code" => "",
    "Pair type" => "",
    "Detailed image" => "",
);

$schema['import_options'] =   array(
    'images_path' => 'exim/backup/images/',
    'remove_images' => 'Y',
    'delimiter' => 'T',
);

return $schema;