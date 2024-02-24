#!usr/bin/env php
<?php

require_once(__DIR__ . '/../app.php');

$files = [
    'binary_data' => [
        'name' => 'binary_data',
        'path' => __DIR__ . '/../mocks/Binary data.txt'        
    ],
    'J1708_hexadecimal' => [
        'name' => 'J1708_binary',
        'path' => __DIR__ . '/../mocks/J1708 data binary format.txt'        
    ],
    'J1708_text' => [
        'name' => 'J1708_text',
        'path' => __DIR__ . '/../mocks/J1708 data text format.txt'        
    ],    
    'J1939_binary' => [
        'name' => 'J1939_binary',
        'path' => __DIR__ . '/../mocks/J1939 data binary format.txt'        
    ],
    'J1939_text' => [
        'name' => 'J1939_text',
        'path' => __DIR__ . '/../mocks/J1939 data text format.txt'        
    ],
    'hexadecimal' => [
        'name' => 'hexadecimal',
        'path' => __DIR__ . '/../mocks/Hex data.txt'        
    ],
    'vin' => [
        'name' => 'vin',
        'path' => __DIR__ . '/../mocks/VIN data text format.txt'        
    ],
    'vin_hex' => [
        'name' => 'vin_binary',
        'path' => __DIR__ . '/../mocks/VIN data binary format.txt'        
    ],
    'text_data' => [
        'name' => 'text_data_1',
        'path' => __DIR__ . '/../mocks/Text data.txt'        
    ]
];

$currentFile = $files['J1939_binary'];

$file = fopen($currentFile['path'], "r") or die("Unable to open file!");
$data = fread($file, filesize($currentFile['path']));
fclose($file);

echo ('Received data: length: ' . strlen($data) . "\n");
$data = explode("\n", trim($data));
echo ('Received messages: qtt: ' . count($data) . "\n");

foreach ($data as $key => $message) {

    $date = \DateTime::createFromFormat('Y-m-d H:i:s', substr($message, 16, 19));

    if ($date instanceof \DateTime) {
        $isMockData = true;
        $obdDeviceNumber = substr($message, 0, 15);
        $message = substr($message, 36);
    } else {
        $isMockData = false;
        $obdDeviceNumber = null;
    }

    try {                    
        $package = \App\Decoder2::decode($message);
        
        foreach ($package as $row) {
            $json = json_encode($row);

            $fp = fopen($directories['obd_raw_data'] . '/' . date('YmdH') . "-{$currentFile['name']}.txt", 'a');
            fwrite($fp, $json. "\n");
            fclose($fp);

            if (!isset($row['serial'])) {
                echo 'No OBD Serial sent on package' . "\n";
                continue;
            }
        }
    } catch (\Exception $e) {
        echo 'An exception occured: ' . $e->getMessage() . "\n\n\n";

        $connection->write('Error: ' . $e->getMessage() . "\n");
    }
}
