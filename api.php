<?php

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require "apifunctions.php";

$uri = $_SERVER['REQUEST_URI'];
$arr = array();

//Inventory API
if (preg_match("/^\\/api.php\\/inventory[\\/[0-9]*]?$/", $uri)) {
    $id = preg_replace("/^\\/api.php\\/inventory[\\/]?/", "", $uri);
    
    if (!$id) {
        $arr = json_encode(inventory_all());
    } else {
        $arr = json_encode(inventory($id));
    }

    header('Content-Type: application/json; charset=utf-8');
    echo($arr);
    
} else if ($uri == "/wiki") {
    header('Location: wiki/index.php');
}


