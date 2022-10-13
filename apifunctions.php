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
$stock_category = $tbpref."stock_category";
$prices = $tbpref."prices";
$tax_types = $tbpref."tax_types";

function inventory_all() {
    global $conn, $stock_master, $stock_moves;

    $stmt = $conn->prepare("SELECT stock_id, description, bar_id, category_id, tax_type_id FROM $stock_master");
    $stmt->execute();
    $stmt->bind_result($id, $des, $bid, $cid, $tid);
    $arr = array();
    $result = array();

    while ($stmt->fetch()) {
        $stock = array();
        $stock['stock_id'] = $id;
        $stock['bar_id'] = $bid;
        $stock['description'] = $des;
        $stock['category_id'] = $cid;
        $stock['tax_id'] = $tid;
        array_push($arr, $stock);
    }
    $stmt->close();

    foreach($arr as $arritem) {
        $id = $arritem['stock_id'];

        $item['stock_id'] = $id;
        $item['bar_id'] = $arritem['bar_id'];
        $item['description'] = $arritem['description'];
        $item['quantity'] = inventory_quantity($id);
        $item['price'] = inventory_price($id);
        $item['category'] = inventory_category($arritem['category_id']);
        $item['tax_rate'] = inventory_tax($arritem['tax_id'])."%";
        array_push($result, $item);
    }

    return $result;
}

function inventory($id) {
    $id = preg_replace("/\\//", "", $id);
    global $conn, $stock_master, $stock_moves;

    $stmt = $conn->prepare("SELECT description, bar_id, category_id, tax_type_id FROM $stock_master WHERE $stock_master.stock_id = ? ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($des, $bid, $cid, $tid);
    $stock = array();
    while ($stmt->fetch()) {
        $stock['description'] = $des;
        $stock['bar_id'] = $bid;
    }
    $stmt->close();
    if ($stock) {
        $stock['quantity'] = inventory_quantity($id);
        $stock['price'] = inventory_price($id);
        $stock['category'] = inventory_category($cid);
        $stock['tax_rate'] = inventory_tax($tid)."%";
    }
    return $stock;
}

function inventory_quantity($id) {
    global $conn, $stock_master, $stock_moves;
    $sum = 0;

    $stmt = $conn->prepare("SELECT SUM(qty) FROM $stock_moves WHERE stock_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($qty);
    $stmt->fetch();
    $sum += $qty;

    return $sum;
}

function inventory_price($id) {
    global $conn, $prices;
    $sum = 0;

    $stmt = $conn->prepare("SELECT price FROM $prices WHERE stock_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($qty);
    $stmt->fetch();
    $stmt->close();
    $sum += $qty;

    return $sum;
}

function inventory_category($id) {
    global $conn, $stock_category;
    $category = "";

    $stmt = $conn->prepare("SELECT description FROM $stock_category WHERE category_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($category);
    $stmt->fetch();
    $stmt->close();

    return $category;
}

function inventory_tax($id) {
    global $conn, $tax_types;
    $tax = 0;

    $stmt = $conn->prepare("SELECT rate FROM $tax_types WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($tax);
    $stmt->fetch();
    $stmt->close();

    return $tax;
}