# AVRO Logical Types Implementation - Complete

This document summarizes the complete implementation of all AVRO logical types as specified in the Apache Avro specification.

## Implementation Summary

✅ **ALL 10 STANDARD LOGICAL TYPES IMPLEMENTED**

### Implemented Files

#### Core Logical Type Classes (10 types)
1. `src/LogicalType/DecimalLogicalType.php` - Decimal logical type
2. `src/LogicalType/UuidLogicalType.php` - UUID logical type  
3. `src/LogicalType/DateLogicalType.php` - Date logical type
4. `src/LogicalType/TimeMillisLogicalType.php` - Time (millisecond precision)
5. `src/LogicalType/TimeMicrosLogicalType.php` - Time (microsecond precision)
6. `src/LogicalType/TimestampMillisLogicalType.php` - Timestamp (millisecond precision)
7. `src/LogicalType/TimestampMicrosLogicalType.php` - Timestamp (microsecond precision)
8. `src/LogicalType/LocalTimestampMillisLogicalType.php` - Local timestamp (millisecond precision)
9. `src/LogicalType/LocalTimestampMicrosLogicalType.php` - Local timestamp (microsecond precision)
10. `src/LogicalType/DurationLogicalType.php` - Duration logical type

#### Factory Classes (10 factories)
1. `src/LogicalType/Factory/DecimalLogicalTypeFactory.php`
2. `src/LogicalType/Factory/UuidLogicalTypeFactory.php`
3. `src/LogicalType/Factory/DateLogicalTypeFactory.php`
4. `src/LogicalType/Factory/TimeMillisLogicalTypeFactory.php`
5. `src/LogicalType/Factory/TimeMicrosLogicalTypeFactory.php`
6. `src/LogicalType/Factory/TimestampMillisLogicalTypeFactory.php`
7. `src/LogicalType/Factory/TimestampMicrosLogicalTypeFactory.php`
8. `src/LogicalType/Factory/LocalTimestampMillisLogicalTypeFactory.php`
9. `src/LogicalType/Factory/LocalTimestampMicrosLogicalTypeFactory.php`
10. `src/LogicalType/Factory/DurationLogicalTypeFactory.php`

#### Documentation and Examples
- `src/LogicalType/README.md` - Comprehensive documentation
- `examples/all-logical-types.php` - Complete usage examples
- `test-logical-types.php` - Basic validation tests

## Logical Types Overview

| Logical Type | Underlying AVRO Type | Factory Name | Description |
|-------------|---------------------|--------------|-------------|
| decimal | bytes/fixed | `decimal` | Arbitrary-precision decimal numbers |
| uuid | string | `uuid` | RFC-4122 UUID strings |
| date | int | `date` | Calendar dates (days since epoch) |
| time-millis | int | `time-millis` | Time of day (milliseconds since midnight) |
| time-micros | long | `time-micros` | Time of day (microseconds since midnight) |
| timestamp-millis | long | `timestamp-millis` | UTC timestamps (milliseconds since epoch) |
| timestamp-micros | long | `timestamp-micros` | UTC timestamps (microseconds since epoch) |
| local-timestamp-millis | long | `local-timestamp-millis` | Local timestamps (milliseconds) |
| local-timestamp-micros | long | `local-timestamp-micros` | Local timestamps (microseconds) |
| duration | fixed[12] | `duration` | Time durations (months, days, milliseconds) |

## Key Features

### ✅ Complete AVRO Specification Compliance
- All logical types follow Apache Avro specification (versions 1.8.0 - 1.11.0+)
- Proper underlying type usage and storage formats
- Correct normalization/denormalization

### ✅ Robust Input Validation
- Type validation (string, int, DateTime objects, etc.)
- Format validation (regex patterns, range checks)
- Precision and scale validation for decimals
- UUID format validation (RFC-4122)

### ✅ Clean Input Format Support
- **Decimal**: String, numeric values
- **UUID**: String format
- **Date**: DateTimeInterface objects, integers
- **Time**: DateTimeInterface objects, integers
- **Timestamp**: DateTimeInterface objects, integers
- **Duration**: Arrays, objects with methods, binary strings

### ✅ Proper Binary Encoding
- **Decimal**: Two's complement big-endian encoding
- **Duration**: Little-endian 32-bit integers (3x4 bytes)
- **All others**: Direct underlying type encoding

### ✅ Error Handling
- Validation context integration
- Detailed error messages
- Graceful handling of invalid inputs

## Dependencies Added

Updated `composer.json` to include:
```json
{
    "require": {
        "php-64bit": ">=8.2",
        "ext-bcmath": "*"
    }
}
```

The `ext-bcmath` extension is required for arbitrary-precision decimal arithmetic in the `DecimalLogicalType`.

## Usage Example

```php
use Auxmoney\Avro\AvroFactory;
use Auxmoney\Avro\Contracts\Options;
use Auxmoney\Avro\LogicalType\Factory\DecimalLogicalTypeFactory;
use Auxmoney\Avro\LogicalType\Factory\DateLogicalTypeFactory;
// ... other factories

$options = new Options(logicalTypeFactories: [
    new DecimalLogicalTypeFactory(),
    new DateLogicalTypeFactory(),
    // ... all other factories
]);

$avroFactory = AvroFactory::create($options);

// Use any logical type
$schema = '{"type": "bytes", "logicalType": "decimal", "precision": 10, "scale": 2}';
$writer = $avroFactory->createWriter($schema);
// ... use writer
```

## Testing

Run the provided test files to verify the implementation:

1. **Basic logical type tests**: `php test-logical-types.php`
2. **Complete integration tests**: `php examples/all-logical-types.php`

## Specification References

- [Apache Avro 1.8.0 Specification - Logical Types](https://avro.apache.org/docs/1.8.0/spec.html#Logical+Types)
- [Apache Avro 1.11.0 Specification - Logical Types](https://avro.apache.org/docs/1.11.0/spec.html#Logical+Types)

---

**Implementation Status**: ✅ COMPLETE - All 10 standard AVRO logical types implemented with full specification compliance.