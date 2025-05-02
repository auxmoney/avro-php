<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Auxmoney\Avro\AvroFactory;
use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;

class Base64ExampleType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ValidationContextInterface $context): bool
    {
        if (!is_string($datum)) {
            $context->addError('expected string, got ' . gettype($datum));
            return false;
        }

        return true;
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