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
$sales_orders = $tbpref."sales_orders";
$sales_order_details = $tbpref."sales_order_details";

function inventory_all() {
    global $conn, $stock_master, $stock_moves;

    $stmt = $conn->prepare("SELECT stock_id, description, bar_id, bar_code, category_id, tax_type_id FROM $stock_master");
    $stmt->execute();
    $stmt->bind_result($id, $des, $bid, $bcode, $cid, $tid);
    $arr = array();
    $result = array();

    while ($stmt->fetch()) {
        $stock = array();
        $stock['stock_id'] = $id;
        $stock['bar_id'] = $bid;
        $stock['bar_code'] = $bcode;
        $stock['description'] = $des;
        $stock['category_id'] = $cid;
        $stock['tax_id'] = $tid;
        array_push($arr, $stock);
    }
    $stmt->close();

    foreach($arr as $arritem) {
        $id = $arritem['stock_id'];
        $price = inventory_price($id);
        $quantity = inventory_quantity($id);

        if ($price <= 0) continue;

        if ($quantity > 0) {
            $whse = inventory_locations($id);
            foreach ($whse as $w) {
                $item['stock_id'] = $id;
                $item['bar_id'] = $arritem['bar_id'];
                $item['bar_code'] = $arritem['bar_code'];
                $item['description'] = $arritem['description'];
                $item['quantity'] = $w['qty'];
                $item['price_id'] = inventory_priceId($id);
                $item['price'] = $price;
                $item['category'] = inventory_category($arritem['category_id']);
                $item['tax_rate'] = inventory_tax($arritem['tax_id'])."%";
                $item['warehouse'] = $w['loc_code'];
                array_push($result, $item);
            }
        } else {
            $item['stock_id'] = $id;
            $item['bar_id'] = $arritem['bar_id'];
            $item['bar_code'] = $arritem['bar_code'];
            $item['description'] = $arritem['description'];
            $item['quantity'] = $quantity;
            $item['price_id'] = inventory_priceId($id);
            $item['price'] = $price;
            $item['category'] = inventory_category($arritem['category_id']);
            $item['tax_rate'] = inventory_tax($arritem['tax_id'])."%";
            $item['warehouse'] = null;//inventory_locations($id);
            array_push($result, $item);
        }

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

function inventory_description($id) {
    global $conn, $stock_master;

    $stmt = $conn->prepare("SELECT description FROM $stock_master WHERE stock_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($desc);
    $stmt->fetch();
    $stmt->close();

    return $desc;
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

function inventory_priceId($id) {
    global $conn, $prices;

    $stmt = $conn->prepare("SELECT id FROM $prices WHERE stock_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($pid);
    $stmt->fetch();
    $stmt->close();

    return $pid;
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

function inventory_locations($id) {
    global $conn, $stock_moves;
    $result = array();

    $stmt = $conn->prepare("SELECT loc_code, SUM(qty) FROM $stock_moves WHERE stock_id = ? GROUP BY loc_code");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($loc, $qty);
    while($stmt->fetch()) {
        $arr['loc_code'] = $loc;
        $arr['qty'] = $qty;
        array_push($result, $arr);
    }
    return $result;
}

function invoice($item) {
    global $conn, $sales_orders, $sales_order_details, $stock_moves;
    $order_no = nextOrder(); //$item->order_id;
    $order_total = $item->total_amount;
    $order_date = $item->sale_date;
    $items = $item->items;

    $debtor = 1;
    $from_loc = $items[0]->loc_code;
    $date = date('Y-m-d');
    $deliverto = "Gatanga Road";

    $stmt = $conn->prepare("INSERT INTO $sales_orders(order_no, ord_date, total, debtor_no, branch_code, delivery_address, from_stk_loc, delivery_date, reference, customer_ref) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $order_no, $order_date, $order_total, $debtor, $debtor, $deliverto, $from_loc, $date, $order_no, $order_no);
    $stmt->execute();

    foreach($items as $itm) {
        $id = $itm->stock_id;
        $qty = $itm->qty;
        $loc = $itm->loc_code;
        $dsc = inventory_description($id);
        $price = $itm->unit_price;
        stockMovesInvoice($id, $order_no, $order_date, $price, $qty, $loc);
        lineInvoice($order_no, $id, $dsc, $price, -$qty);
    }

    return $order_no;

}


function stockMovesInvoice($stock_id, $order_no, $order_date, $price, $quantity, $loc) {
    global $conn, $stock_moves;

    $quantity *= -1;

    $stmt2 = $conn->prepare("INSERT INTO $stock_moves(stock_id, trans_no, tran_date, price, qty, loc_code) VALUES(?,?,?,?,?,?)");
    $stmt2->bind_param("ssssss", $stock_id, $order_no, $order_date, $price, $quantity, $loc);
    $stmt2->execute();
}

function lineInvoice($order_no, $stock_id, $stock_description, $price, $quantity) {
    global $conn, $sales_order_details;

    $quantity *= -1;

    $stmt2 = $conn->prepare("INSERT INTO $sales_order_details(order_no, stk_code, description, unit_price, quantity) VALUES(?,?,?,?,?)");
    $stmt2->bind_param("sssss", $order_no, $stock_id, $stock_description, $price, $quantity);
    $stmt2->execute();
}

function nextOrder() {
    global $conn, $sales_orders;

    $stmt = $conn->prepare("SELECT order_no FROM $sales_orders ORDER BY order_no DESC LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($result);
    $stmt->fetch();
    return $result + 1;

}
