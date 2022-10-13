<?php

echo nl2br("<h3>/api.php/inventory</h3>");
echo nl2br("Return all inventory items (stock_id, bar_id, description, quantity, price, category, tax_rate)");

echo nl2br("\n\n");

echo nl2br("<h3>/api.php/inventory/{stock_id}</h3>");
echo nl2br("Return stock item details (description, bar_id, quantity, price, category, tax_rate)");