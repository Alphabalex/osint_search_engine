<?php
require __DIR__ . '/../vendor/autoload.php';

use Eaglewatch\SearchEngines\Pipl;
use Eaglewatch\SearchEngines\Darkdump;

// advanced search
$fields = array(
    new PiplApi_Address(array("country" => "US", "state" => "KS", "city" => "Metropolis")),
    new PiplApi_Address(array("country" => "US", "state" => "KS", "city" => "Smallville")),
    new PiplApi_Name(array("first" => "Clark", "middle" => "Joseph", "last" => "Kent")),
    new PiplApi_Job(array("title" => "Field Reporter")),
);
$person = new PiplApi_Person($fields);
$search = array('person' => $person);

$search = array('email' => 'clark.kent@example.com', 'first_name' => 'Clark', 'last_name' => 'Kent');
$pipl = new Darkdump();
$response = $pipl->search("guns", 100, true, false, true);

print_r($response);
