# auxmoney/avro-php

A PHP library that provides schema-based Avro data serialization and deserialization.

It started as a fork of the original Apache Avro implementation available at https://github.com/apache/avro, but now it has been completely rewritten. Some of the original functionality has been removed, and new features have been added.

The features added by this library are:
- Basic support for logical types
- Default values for record fields
- Developer-friendly error messages for schema validation
- Serialization of objects through getters or public properties
- Schema resolution including promotion of primitive types
- Configurable block count and block size for array and map encoding

## Installation

To install auxmoney/avro-php, you can use Composer:

```bash
composer require auxmoney/avro-php
```

## Usage

### Encoding/Decoding Data

Try out the example scripts `examples/encoding.php` and `examples/decoding.php`:
```bash
php examples/encoding.php
php examples/decoding.php
```

### Logical Types

Although this library does not provide an implementation for any logical type, it is possible to use them by providing the factory implementation to `Auxmoney\Avro\AvroFactory::create`.

The logical type factory must implement the interface `Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface`.

Try out the example script `examples/logical-type.php`:
```bash
php examples/logical-type.php
```

## Documentation

For more detailed documentation on usage, schema design, and advanced features like schema evolution, please refer to the official Avro documentation.

## Contribution

Contributions are welcome! If you find a bug or want to suggest a new feature, feel free to open an issue or submit a pull request.

## License

This library is licensed under the Apache License 2.0.

## Changelog

For the detailed changelog, please refer to the Releases page.

