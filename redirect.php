<?php
if(empty($_GET['path'])) exit('{"error": "no path"}');

$resource = 'http://127.0.0.1:7860' . $_GET['path'];
$context = null;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'content' => http_build_query($_POST)
        )
    ));
}

readfile($resource, false, $context);
