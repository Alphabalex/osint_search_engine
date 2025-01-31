<?php
require __DIR__ . '/../vendor/autoload.php';

use Eaglewatch\SearchEngines\Pipl;


$search = array('email' => 'clark.kent@example.com', 'first_name' => 'Clark','last_name' => 'Kent');   
$pipl = new Pipl();
$response = $pipl->search($search);

print_r($response);
