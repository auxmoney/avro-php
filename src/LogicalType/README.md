# AVRO Logical Types Implementation

This directory contains implementations of all standard AVRO logical types as defined by the Apache Avro specification.

## Overview

Logical types provide a way to represent higher-level data types using Avro's existing primitive types while adding semantic meaning and validation. Each logical type consists of:

1. A **LogicalType** class that implements `LogicalTypeInterface`
2. A **Factory** class that implements `LogicalTypeFactoryInterface`

## Implemented Logical Types

### 1. Decimal (`decimal`)
- **Underlying type**: `bytes` or `fixed`
- **Purpose**: Arbitrary-precision signed decimal numbers
- **Attributes**: 
  - `precision` (required): Maximum number of digits
  - `scale` (optional, default 0): Number of digits after decimal point
- **Example**: `{"type": "bytes", "logicalType": "decimal", "precision": 10, "scale": 2}`

### 2. UUID (`uuid`)
- **Underlying type**: `string`
- **Purpose**: Universally unique identifiers
- **Format**: RFC-4122 compliant UUID string (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)
- **Example**: `{"type": "string", "logicalType": "uuid"}`

### 3. Date (`date`)
- **Underlying type**: `int`
- **Purpose**: Calendar date with no time or timezone
- **Storage**: Number of days since Unix epoch (1970-01-01)
- **Example**: `{"type": "int", "logicalType": "date"}`

### 4. Time - Millisecond precision (`time-millis`)
- **Underlying type**: `int`
- **Purpose**: Time of day with millisecond precision
- **Storage**: Number of milliseconds after midnight (0-86399999)
- **Example**: `{"type": "int", "logicalType": "time-millis"}`

### 5. Time - Microsecond precision (`time-micros`)
- **Underlying type**: `long`
- **Purpose**: Time of day with microsecond precision
- **Storage**: Number of microseconds after midnight (0-86399999999)
- **Example**: `{"type": "long", "logicalType": "time-micros"}`

### 6. Timestamp - Millisecond precision (`timestamp-millis`)
- **Underlying type**: `long`
- **Purpose**: Instant in time with millisecond precision (UTC)
- **Storage**: Number of milliseconds since Unix epoch
- **Example**: `{"type": "long", "logicalType": "timestamp-millis"}`

### 7. Timestamp - Microsecond precision (`timestamp-micros`)
- **Underlying type**: `long`
- **Purpose**: Instant in time with microsecond precision (UTC)
- **Storage**: Number of microseconds since Unix epoch
- **Example**: `{"type": "long", "logicalType": "timestamp-micros"}`

### 8. Local Timestamp - Millisecond precision (`local-timestamp-millis`)
- **Underlying type**: `long`
- **Purpose**: Local timestamp without timezone information
- **Storage**: Number of milliseconds from 1970-01-01 00:00:00.000
- **Example**: `{"type": "long", "logicalType": "local-timestamp-millis"}`

### 9. Local Timestamp - Microsecond precision (`local-timestamp-micros`)
- **Underlying type**: `long`
- **Purpose**: Local timestamp without timezone information
- **Storage**: Number of microseconds from 1970-01-01 00:00:00.000000
- **Example**: `{"type": "long", "logicalType": "local-timestamp-micros"}`

### 10. Duration (`duration`)
- **Underlying type**: `fixed` with size 12
- **Purpose**: Amount of time (months, days, milliseconds)
- **Storage**: Three little-endian unsigned 32-bit integers
- **Example**: `{"type": "fixed", "name": "Duration", "size": 12, "logicalType": "duration"}`

## Usage

### Basic Setup

```php
use Auxmoney\Avro\AvroFactory;
use Auxmoney\Avro\Contracts\Options;
use Auxmoney\Avro\LogicalType\Factory\DecimalLogicalTypeFactory;
use Auxmoney\Avro\LogicalType\Factory\DateLogicalTypeFactory;
// ... import other factories as needed

$options = new Options(logicalTypeFactories: [
    new DecimalLogicalTypeFactory(),
    new DateLogicalTypeFactory(),
    // ... add other factories as needed
]);

$avroFactory = AvroFactory::create($options);
```

### Example: Working with Decimal

```php
$schema = '{"type": "bytes", "logicalType": "decimal", "precision": 10, "scale": 2}';
$writer = $avroFactory->createWriter($schema);
$buffer = $avroFactory->createStringBuffer();

// Write decimal value
$writer->write('123.45', $buffer);

// Read it back
$readBuffer = $avroFactory->createReadableStreamFromString($buffer->__toString());
$reader = $avroFactory->createReader($schema);
$value = $reader->read($readBuffer); // Returns: "123.45"
```

### Example: Working with Date

```php
$schema = '{"type": "int", "logicalType": "date"}';
$writer = $avroFactory->createWriter($schema);
$buffer = $avroFactory->createStringBuffer();

// Write date (accepts string, DateTime, or integer)
$writer->write('2023-12-25', $buffer);

// Read it back
$readBuffer = $avroFactory->createReadableStreamFromString($buffer->__toString());
$reader = $avroFactory->createReader($schema);
$value = $reader->read($readBuffer); // Returns: "2023-12-25"
```

### Example: Working with Duration

```php
$schema = '{"type": "fixed", "name": "Duration", "size": 12, "logicalType": "duration"}';
$writer = $avroFactory->createWriter($schema);
$buffer = $avroFactory->createStringBuffer();

// Write duration as array [months, days, milliseconds]
$writer->write([12, 30, 86400000], $buffer);

// Read it back
$readBuffer = $avroFactory->createReadableStreamFromString($buffer->__toString());
$reader = $avroFactory->createReader($schema);
$value = $reader->read($readBuffer); 
// Returns: ["months" => 12, "days" => 30, "milliseconds" => 86400000]
```

## Input Format Support

Each logical type supports multiple input formats for convenience:

### Decimal
- String: `"123.45"`
- Numeric: `123.45`

### UUID
- String: `"550e8400-e29b-41d4-a716-446655440000"`

### Date
- String: `"2023-12-25"` (YYYY-MM-DD)
- DateTime object
- Integer: days since epoch

### Time (millis/micros)
- String: `"14:30:25.123"` or `"14:30:25.123456"`
- DateTime object
- Integer: milliseconds/microseconds since midnight

### Timestamp (millis/micros)
- String: ISO 8601 format or `"Y-m-d H:i:s"`
- DateTime object
- Integer: milliseconds/microseconds since Unix epoch

### Local Timestamp (millis/micros)
- String: `"2023-12-25 14:30:25.123"` (without timezone)
- DateTime object
- Integer: milliseconds/microseconds

### Duration
- Array: `[months, days, milliseconds]`
- Object with `getMonths()`, `getDays()`, `getMilliseconds()` methods
- 12-byte binary string (direct format)

## Validation

All logical types perform validation during the write process:

- **Format validation**: Ensures input matches expected format
- **Range validation**: Checks values are within valid ranges
- **Type validation**: Verifies input is of acceptable type

Validation errors are reported through the `ValidationContextInterface` when available.

## Normalization and Denormalization

- **normalize()**: Converts input data to the underlying Avro type format for serialization
- **denormalize()**: Converts stored data back to a readable format after deserialization

## Examples

See `examples/all-logical-types.php` for a comprehensive demonstration of all logical types in action.

## Specification Compliance

These implementations follow the Apache Avro specification for logical types:
- [Avro 1.8.0 Specification](https://avro.apache.org/docs/1.8.0/spec.html#Logical+Types)
- [Avro 1.11.0 Specification](https://avro.apache.org/docs/1.11.0/spec.html#Logical+Types)