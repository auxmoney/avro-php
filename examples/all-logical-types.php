<?php

require_once __DIR__ . '/autoload.php';

use Auxmoney\Avro\AvroFactory;
use Auxmoney\Avro\Contracts\Options;
use Auxmoney\Avro\LogicalType\Factory\DecimalLogicalTypeFactory;
use Auxmoney\Avro\LogicalType\Factory\UuidLogicalTypeFactory;
use Auxmoney\Avro\LogicalType\Factory\DateLogicalTypeFactory;
use Auxmoney\Avro\LogicalType\Factory\TimeMillisLogicalTypeFactory;
use Auxmoney\Avro\LogicalType\Factory\TimeMicrosLogicalTypeFactory;
use Auxmoney\Avro\LogicalType\Factory\TimestampMillisLogicalTypeFactory;
use Auxmoney\Avro\LogicalType\Factory\TimestampMicrosLogicalTypeFactory;
use Auxmoney\Avro\LogicalType\Factory\LocalTimestampMillisLogicalTypeFactory;
use Auxmoney\Avro\LogicalType\Factory\LocalTimestampMicrosLogicalTypeFactory;
use Auxmoney\Avro\LogicalType\Factory\DurationLogicalTypeFactory;

// Register all logical type factories
$options = new Options(logicalTypeFactories: [
    new DecimalLogicalTypeFactory(),
    new UuidLogicalTypeFactory(),
    new DateLogicalTypeFactory(),
    new TimeMillisLogicalTypeFactory(),
    new TimeMicrosLogicalTypeFactory(),
    new TimestampMillisLogicalTypeFactory(),
    new TimestampMicrosLogicalTypeFactory(),
    new LocalTimestampMillisLogicalTypeFactory(),
    new LocalTimestampMicrosLogicalTypeFactory(),
    new DurationLogicalTypeFactory(),
]);

$avroFactory = AvroFactory::create($options);

// Example schemas for all logical types
$examples = [
    'decimal' => [
        'schema' => '{"type": "bytes", "logicalType": "decimal", "precision": 10, "scale": 2}',
        'data' => '123.45'
    ],
    'uuid' => [
        'schema' => '{"type": "string", "logicalType": "uuid"}',
        'data' => '550e8400-e29b-41d4-a716-446655440000'
    ],
    'date' => [
        'schema' => '{"type": "int", "logicalType": "date"}',
        'data' => '2023-12-25'
    ],
    'time-millis' => [
        'schema' => '{"type": "int", "logicalType": "time-millis"}',
        'data' => '14:30:25.123'
    ],
    'time-micros' => [
        'schema' => '{"type": "long", "logicalType": "time-micros"}',
        'data' => '14:30:25.123456'
    ],
    'timestamp-millis' => [
        'schema' => '{"type": "long", "logicalType": "timestamp-millis"}',
        'data' => '2023-12-25T14:30:25.123Z'
    ],
    'timestamp-micros' => [
        'schema' => '{"type": "long", "logicalType": "timestamp-micros"}',
        'data' => '2023-12-25T14:30:25.123456Z'
    ],
    'local-timestamp-millis' => [
        'schema' => '{"type": "long", "logicalType": "local-timestamp-millis"}',
        'data' => '2023-12-25 14:30:25.123'
    ],
    'local-timestamp-micros' => [
        'schema' => '{"type": "long", "logicalType": "local-timestamp-micros"}',
        'data' => '2023-12-25 14:30:25.123456'
    ],
    'duration' => [
        'schema' => '{"type": "fixed", "name": "Duration", "size": 12, "logicalType": "duration"}',
        'data' => [12, 30, 86400000] // 12 months, 30 days, 1 day in milliseconds
    ]
];

echo "Testing all AVRO logical types:\n\n";

foreach ($examples as $typeName => $example) {
    echo "=== {$typeName} ===\n";
    echo "Schema: {$example['schema']}\n";
    echo "Input data: " . (is_array($example['data']) ? json_encode($example['data']) : $example['data']) . "\n";
    
    try {
        $writer = $avroFactory->createWriter($example['schema']);
        $writeBuffer = $avroFactory->createStringBuffer();
        
        $writer->write($example['data'], $writeBuffer);
        $serialized = $writeBuffer->__toString();
        
        echo "Serialized: " . bin2hex($serialized) . "\n";
        
        $readBuffer = $avroFactory->createReadableStreamFromString($serialized);
        $reader = $avroFactory->createReader($example['schema']);
        $deserialized = $reader->read($readBuffer);
        
        echo "Deserialized: " . (is_array($deserialized) ? json_encode($deserialized) : $deserialized) . "\n";
        echo "✓ Success\n\n";
        
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "All logical types demonstration completed!\n";