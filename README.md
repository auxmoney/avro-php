# auxmoney/avro-php

A PHP library that provides schema-based Avro data serialization and deserialization.

It is based on the latest changes from the original Apache Avro implementation available at https://github.com/apache/avro, and it is designed to be an augmented version of the original library.

The features added by this library are:
- Basic support for logical types
- Default values for fields
- Serialization of objects through getters or public properties

## Installation

To install auxmoney/avro-php, you can use Composer:

```bash
composer require auxmoney/avro-php
```

## Usage

### Encoding/Decoding Data

Try out the example scripts `examples/encoding.php` and `examples/decoding.php`:
```bash
php -r 'require "vendor/autoload.php"; require "examples/encoding.php";'
php -r 'require "vendor/autoload.php"; require "examples/decoding.php";'
```

### Logical Types

Although this library does not provide an implementation for any logical type, it is possible to use them by providing the implementation to the AvroIODatumReader and AvroIODatumWriter classes.

The logical type must implement the interface Auxmoney\Avro\Datum\LogicalTypeInterface.

Try out the example script `examples/logical-type.php`:
```bash
php -r 'require "vendor/autoload.php"; require "examples/logical-type.php";'
```

## Documentation

For more detailed documentation on usage, schema design, and advanced features like schema evolution, please refer to the official Avro documentation.

## Contribution

Contributions are welcome! If you find a bug or want to suggest a new feature, feel free to open an issue or submit a pull request.

The code under the `apache` directory is a copy of the original Apache Avro library, and should have only minimal changes to adapt it to the new features. The code under the `src` directory is the new code added by this library.

The branch `upstream` is used to track the original Apache Avro library, and the branch `master` is used to track the changes made by this library.

To retrieve changes from the original library, you can use the shell script `update-upstream.sh`.

## License

This library is licensed under the Apache License 2.0.

## Changelog

For the detailed changelog, please refer to the Releases page.

