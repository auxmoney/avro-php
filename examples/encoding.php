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
$writer = $avroFactory->createWriter($schemaJson);
$buffer = $avroFactory->createStringBuffer();
$writer->write($data, $buffer);
$encodedData = $buffer->__toString();
var_dump(bin2hex($encodedData));