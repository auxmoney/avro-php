# auxmoney/avro-php

A PHP library that provides schema-based Avro data serialization and deserialization.

It is based on the latest changes from the original Apache Avro implementation available at https://github.com/apache/avro, and it is intended as add-on to the original library.

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

### Encoding Data

```php
use Apache\Avro\Datum\AvroIOBinaryEncoder;
use Apache\Avro\IO\AvroStringIO;
use Apache\Avro\Schema\AvroSchema;
use Auxmoney\Avro\Datum\AvroIODatumWriter;

$schemaJson = '{
    "type": "record",
    "name": "User",
    "fields": [
        {"name": "name", "type": "string"},
        {"name": "age", "type": "int"}
    ]
}';

$schema = AvroSchema::parse($schemaJson);

$data = [
    'name' => 'John Doe',
    'age' => 30
];

$writer = new AvroIODatumWriter();
$io = new AvroStringIO();
$writer->writeData($schema, $data, new AvroIOBinaryEncoder($io));
$encodedData = $io->string();
```

### Decoding Data

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Apache\Avro\Datum\AvroIOBinaryDecoder;
use Apache\Avro\IO\AvroStringIO;
use Apache\Avro\Schema\AvroSchema;
use Auxmoney\Avro\Datum\AvroIODatumReader;

$schemaJson = '{
    "type": "record",
    "name": "User",
    "fields": [
        {"name": "name", "type": "string"},
        {"name": "age", "type": "int"}
    ]
}';

$schema = AvroSchema::parse($schemaJson);

$reader = new AvroIODatumReader();
$io = new AvroStringIO(hex2bin('104a6f686e20446f653c'));
$data = $reader->readData($schema, $schema, new AvroIOBinaryDecoder($io));

var_export($data);
```

This will output:

```php
array (
  'name' => 'John Doe',
  'age' => 30,
)
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

