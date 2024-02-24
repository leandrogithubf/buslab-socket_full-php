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

function isBinary($str)
{
    return preg_match('~[^\x20-\x7E\t\r\n]~', $str) > 0;
}

function separtedBinarieFiles(array $bytesArray)
{
    $arraySeparated = [];
    $count = 0;
    $length = count($bytesArray);
    for ($i=0; $i < $length; $i++) {
        if ($i == 0) {
            $arraySeparated[$count][] = $bytesArray[$i];
            continue;
        }

        if ($i == $length) {
            $arraySeparated[$count][] = $bytesArray[$i];
            continue;
        }

        if ($bytesArray[$i - 1] == 248 && $bytesArray[$i] == 248) {
            $count++;
            $arraySeparated[$count][] = $bytesArray[$i];
            continue;
        }

        $arraySeparated[$count][] = $bytesArray[$i];
    }

    return $arraySeparated;
}

$currentFile = $files['J1708_hexadecimal'];

$fs = fopen($currentFile['path'], 'r');
$content = fread($fs, filesize($currentFile['path']));
fclose($fs);

if (isBinary($content)) {
    $readBuffer = [...unpack('C*', $content)];
    $dataLength = count($readBuffer);

    $rows = separtedBinarieFiles($readBuffer);

    foreach ($rows as $row) {
        $package = \App\Decoder3::decode($row, false);
    }

} else {
    $rows = explode("\n", $content);
    foreach($rows as $row) {        
        if (substr($row, 0, 2) === "F8") {
            
            $package = \App\Decoder3::decodeHexadecimal($row);

        } else {
            $readBuffer = str_split($row);
            $byteArray = array_map(function($char) { return unpack('C', $char)[1]; }, $readBuffer);
            $package = \App\Decoder3::decode($byteArray, false);

            $readBuffer = null;
            $byteArray = null;
        }        
    }
}


