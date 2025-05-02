<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Auxmoney\Avro\AvroFactory;

$schemaJson = '{
    "type": "record",
    "name": "User",
    "fields": [
        {"name": "name", "type": "string"},
        {"name": "age", "type": "int"}
    ]
}';

$avroFactory = AvroFactory::create();
$reader = $avroFactory->createReader($schemaJson);
$buffer = $avroFactory->createReadableStreamFromString(hex2bin('104a6f686e20446f653c'));
$decodedData = $reader->read($buffer);
var_dump($decodedData);