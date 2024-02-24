<?php

$data = file_get_contents(__DIR__ . '/PGN.json');
$array = json_decode($data, true);

$content = var_export($array, true);

file_put_contents(__DIR__ . "/array.txt", $content);

