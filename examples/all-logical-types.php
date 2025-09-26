<?php

require_once __DIR__ . '/autoload.php';

use Auxmoney\Avro\AvroFactory;
use Auxmoney\Avro\Contracts\Options;
use Auxmoney\Avro\LogicalType\Factory\DecimalFactory;
use Auxmoney\Avro\LogicalType\Factory\UuidFactory;
use Auxmoney\Avro\LogicalType\Factory\DateFactory;
use Auxmoney\Avro\LogicalType\Factory\TimeMillisFactory;
use Auxmoney\Avro\LogicalType\Factory\TimeMicrosFactory;
use Auxmoney\Avro\LogicalType\Factory\TimestampMillisFactory;
use Auxmoney\Avro\LogicalType\Factory\TimestampMicrosFactory;
use Auxmoney\Avro\LogicalType\Factory\LocalTimestampMillisFactory;
use Auxmoney\Avro\LogicalType\Factory\LocalTimestampMicrosFactory;
use Auxmoney\Avro\LogicalType\Factory\DurationFactory;

// Register all logical type factories
$options = new Options(logicalTypeFactories: [
    new DecimalFactory(),
    new UuidFactory(),
    new DateFactory(),
    new TimeMillisFactory(),
    new TimeMicrosFactory(),
    new TimestampMillisFactory(),
    new TimestampMicrosFactory(),
    new LocalTimestampMillisFactory(),
    new LocalTimestampMicrosFactory(),
    new DurationFactory(),
]);

$avroFactory = AvroFactory::create($options);

// Example schemas for all logical types
$examples = [
    'decimal' => [
        'schema' => '{"type": "bytes", "logicalType": "decimal", "precision": 10, "scale": 2}',
        'data' => '123.45'
    ],
    'uuid' => [
        'schema' => '{"type": "fixed", "name": "UUID", "size": 16, "logicalType": "uuid"}',
        'data' => '550e8400-e29b-41d4-a716-446655440000'
    ],
    'date' => [
        'schema' => '{"type": "int", "logicalType": "date"}',
        'data' => new DateTime('2023-12-25')
    ],
    'time-millis' => [
        'schema' => '{"type": "int", "logicalType": "time-millis"}',
        'data' => DateTime::createFromFormat('H:i:s.v', '14:30:25.123')
    ],
    'time-micros' => [
        'schema' => '{"type": "long", "logicalType": "time-micros"}',
        'data' => DateTime::createFromFormat('H:i:s.u', '14:30:25.123456')
    ],
    'timestamp-millis' => [
        'schema' => '{"type": "long", "logicalType": "timestamp-millis"}',
        'data' => new DateTime('2023-12-25T14:30:25.123Z')
    ],
    'timestamp-micros' => [
        'schema' => '{"type": "long", "logicalType": "timestamp-micros"}',
        'data' => DateTime::createFromFormat('Y-m-d\TH:i:s.uP', '2023-12-25T14:30:25.123456Z')
    ],
    'local-timestamp-millis' => [
        'schema' => '{"type": "long", "logicalType": "local-timestamp-millis"}',
        'data' => new DateTime('2023-12-25 14:30:25.123')
    ],
    'local-timestamp-micros' => [
        'schema' => '{"type": "long", "logicalType": "local-timestamp-micros"}',
        'data' => DateTime::createFromFormat('Y-m-d H:i:s.u', '2023-12-25 14:30:25.123456')
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
    
    $inputDisplay = '';
    if (is_array($example['data'])) {
        $inputDisplay = json_encode($example['data']);
    } elseif ($example['data'] instanceof DateTimeInterface) {
        $inputDisplay = $example['data']->format('Y-m-d H:i:s.u P');
    } else {
        $inputDisplay = (string) $example['data'];
    }
    echo "Input data: {$inputDisplay}\n";
    
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