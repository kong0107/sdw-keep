<?php

try {
    if(empty($_POST)) {
        http_response_code(400);
        exit();
    }

    $filepath = "inputs/{$_POST['batch_name']}.json";

    $parameters = json_decode(file_get_contents($filepath));

    foreach($_POST as $key => $value) {
        if($key === 'sd_model_checkpoint') {
            $parameters->override_settings = array('sd_model_checkpoint' => $value);
            continue;
        }
        if(isset($parameters->$key)) {
            if(is_numeric($value)) $value = floatval($value);
            $parameters->$key = $value;
        }
    }

    $output = json_encode($parameters, JSON_PRETTY_PRINT);
    file_put_contents($filepath, $output);
    echo $output;
}
catch (Exception $e) {
    http_response_code(400);
    exit;
}