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

$datum = [
    'name' => 'John Doe',
    'age' => 30
];

$avroFactory = AvroFactory::create();
$writer = $avroFactory->createWriter($schemaJson);
$buffer = $avroFactory->createStringBuffer();
$writer->write($datum, $buffer);
$encodedData = $buffer->__toString();
var_dump(bin2hex($encodedData));