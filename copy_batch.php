<?php

if (empty($_GET['batch_name'])) {
    http_response_code(400);
    exit;
}

$from = "inputs/{$_GET['batch_name']}.json";
$to = "inputs/{$_GET['batch_name']}_copy.json";

if (!copy($from, $to)) {
    http_response_code(400);
    exit;
}
