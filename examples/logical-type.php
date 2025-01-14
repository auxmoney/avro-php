<?php

use Auxmoney\Avro\AvroFactory;
use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;

class Base64ExampleType implements LogicalTypeInterface
{
    public function isValid(mixed $datum): bool
    {
        return is_string($datum);
    }

    public function denormalize(mixed $datum): mixed
    {
        return base64_decode($datum);
    }

    public function normalize(mixed $datum): mixed
    {
        return base64_encode($datum);
    }
}

class Base64ExampleTypeFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'base64-example';
    }

    public function create(array $attributes): Auxmoney\Avro\Contracts\LogicalTypeInterface
    {
        return new Base64ExampleType();
    }
}

$logicalTypes = [];
$avroFactory = AvroFactory::create([new Base64ExampleTypeFactory()]);

$schema = '{"type": "string", "logicalType": "base64-example"}';

$writer = $avroFactory->createWriter($schema);
$writeBuffer = $avroFactory->createStringBuffer();
$writer->write('Hello, World!', $writeBuffer);
var_dump($writeBuffer->__toString());

$readBuffer = $avroFactory->createReadableStreamFromString("(SGVsbG8sIFdvcmxkIQ==");
$reader = $avroFactory->createReader($schema);
var_dump($reader->read($readBuffer));