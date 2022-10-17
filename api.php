<?php

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require "apifunctions.php";

$uri = $_SERVER['REQUEST_URI'];
$arr = array();

//Inventory API
if (preg_match("/^\\/api.php\\/inventory[\\/[A-Z0-9]*]?$/", $uri)) {
    $id = preg_replace("/^\\/api.php\\/inventory[\\/]?/", "", $uri);
    
    if (!$id) {
        $arr = json_encode(inventory_all());
    } else {
        $arr = json_encode(inventory($id));
    }

    header('Content-Type: application/json; charset=utf-8');
    echo($arr);
    
} else if ($uri == "/api.php/wiki") {

    header('Content-Type: text/html; charset=utf-8');
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo nl2br("<h3>/api.php/inventory</h3>");
    echo nl2br("Return all inventory items (stock_id, bar_id, description, quantity, price, category, tax_rate)");

    echo nl2br("\n\n");
    echo nl2br("<h3>/api.php/inventory/{stock_id}</h3>");
    echo nl2br("Return stock item details (description, bar_id, quantity, price, category, tax_rate)");

    echo nl2br("\n\n");
    echo nl2br("<h3>/api.php/invoice</h3>");
    echo nl2br("Post sales invoice - POST method");
    echo nl2br("\n{sale_date, order_id, total_amount}");


} else if (preg_match("/^\\/api.php\\/invoice[\\/]?$/", $uri)) {

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    //header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents("php://input"));
    $response = invoice($data);
    echo json_encode($response);

}


