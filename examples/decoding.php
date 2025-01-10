<?php

use Auxmoney\Avro\AvroFactory;

$schemaJson = '{
    "type": "record",
    "name": "User",
    "fields": [
        {"name": "name", "type": "string"},
        {"name": "age", "type": "int"}
    ]
}';

$data = [
    'name' => 'John Doe',
    'age' => 30
];

$avroFactory = new AvroFactory();
$reader = $avroFactory->createReader($schemaJson);
$buffer = $avroFactory->createReadableStreamFromString(hex2bin('104a6f686e20446f653c'));
$decodedData = $reader->read($buffer);
var_dump($decodedData);