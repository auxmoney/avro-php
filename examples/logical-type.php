<?php

require_once __DIR__ . '/autoload.php';

use Auxmoney\Avro\AvroFactory;
use Auxmoney\Avro\ValueObject\Decimal;

$avroFactory = AvroFactory::create();

$schema = '{"type": "long", "logicalType": "timestamp-millis"}';
$encodedData = "\x00";

$reader = $avroFactory->createReader($schema);
$buffer = $avroFactory->createReadableStreamFromString($encodedData);
$decodedData = $reader->read($buffer);

var_dump($decodedData);


$schema = '{"type": "bytes", "logicalType": "decimal", "precision": 10, "scale": 4}';
$decodedData = Decimal::fromString('3.14159');

$writer = $avroFactory->createWriter($schema);
$buffer = $avroFactory->createStringBuffer();
$writer->write($decodedData, $buffer);
var_dump(bin2hex($buffer->__toString()));