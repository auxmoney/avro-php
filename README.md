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

### Logical Types

Although this library does not provide an implementation for any logical type, it is possible to use them by providing the implementation to the AvroIODatumReader and AvroIODatumWriter classes.

The logical type must implement the interface Auxmoney\Avro\Datum\LogicalTypeInterface.

```php
use Apache\Avro\Datum\AvroIOBinaryDecoder;
use Apache\Avro\Datum\AvroIOBinaryEncoder;
use Apache\Avro\IO\AvroStringIO;
use Apache\Avro\Schema\AvroSchema;
use Auxmoney\Avro\Datum\AvroIODatumReader;
use Auxmoney\Avro\Datum\AvroIODatumWriter;
use Auxmoney\Avro\Datum\LogicalTypeInterface;

class Base64ExampleType implements LogicalTypeInterface
{
    public function getName(): string
    {
        return 'base64-example';
    }

    public function isValid(AvroSchema $schema, mixed $datum): bool
    {
        return is_string($datum);
    }

    public function normalize(AvroSchema $writersSchema, mixed $datum): mixed
    {
        return base64_encode($datum);
    }

    public function denormalize(AvroSchema $writersSchema, AvroSchema $readersSchema, mixed $datum): mixed
    {
        return base64_decode($datum);
    }
};

$base64ExampleType = new Base64ExampleType();

$logicalTypes = [
    $base64ExampleType->getName() => $base64ExampleType,
];

$reader = new AvroIODatumReader($logicalTypes);
$writer = new AvroIODatumWriter($logicalTypes);

$schema = AvroSchema::parse('{"type": "string", "logicalType": "base64-example"}');
$data = 'Hello, World!';
$io = new AvroStringIO();
$writer->writeData($schema, $data, new AvroIOBinaryEncoder($io));
$encodedData = $io->string();
echo $encodedData . PHP_EOL; // Outputs: "SGVsbG8sIFdvcmxkIQ=="

$io = new AvroStringIO($encodedData);
$data = $reader->readData($schema, $schema, new AvroIOBinaryDecoder($io));
echo $data . PHP_EOL; // Outputs: "Hello, World!"
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

