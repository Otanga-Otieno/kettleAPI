<?php

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require "config_db.php";

global $db_connections;
$dbconn = $db_connections[0];
$tbpref = $dbconn['tbpref'];

$conn = new mysqli($dbconn['host'], $dbconn['dbuser'], $dbconn['dbpassword'], $dbconn['dbname']);
$item_codes = $tbpref."item_codes";
$stock_master = $tbpref."stock_master";
$stock_moves = $tbpref."stock_moves";

function inventory_all() {
    global $conn, $stock_master, $stock_moves;

    $stmt = $conn->prepare("SELECT $stock_master.stock_id, $stock_master.description, $stock_master.material_cost FROM $stock_master");
    $stmt->execute();
    $stmt->bind_result($id, $des, $cost);
    $arr = array();
    while ($stmt->fetch()) {
        $stock = array();
        $stock['stock_id'] = $id;
        $stock['description'] = $des;
        //$stock['quantity'] = $qty;
        $stock['price'] = $cost;
        array_push($arr, $stock);
    }
    return $arr;
}

function inventory($id) {
    $id = preg_replace("/\\//", "", $id);
    global $conn, $stock_master, $stock_moves;

    $stmt = $conn->prepare("SELECT SUM($stock_moves.qty) AS quantity, $stock_master.description, $stock_master.material_cost FROM $stock_master INNER JOIN $stock_moves ON $stock_moves.stock_id=$stock_master.stock_id WHERE $stock_master.stock_id = ? AND $stock_master.stock_id=$stock_moves.stock_id GROUP BY $stock_master.stock_id");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($qty, $des, $cost);
    $stock = array();
    while ($stmt->fetch()) {
        $stock['description'] = $des;
        $stock['quantity'] = $qty;
        $stock['price'] = $cost;
    }
    return $stock;
}