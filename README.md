# auxmoney/avro-php

A PHP library that provides schema-based Avro data serialization and deserialization.

Key features:
- Support for logical types
- Developer-friendly error messages for schema validation
- Schema resolution including promotion of primitive types
- Serialization of objects through getters or public properties
- Default values for record fields
- Configurable block count and block size for array and map encoding

## Installation

To install auxmoney/avro-php, you can use Composer:

```bash
composer require auxmoney/avro-php
```

## Usage

### Encoding/Decoding Data

Try out the example scripts:
```bash
php examples/encoding.php
php examples/decoding.php
php examples/logical-type.php
```

### Logical Types

It is possible to configure logical types in a few different ways:

#### Using Default Logical Types
There are built-in implementations for all logical types described in the AVRO specification, except for `timestamp-nanos` and `local-timestamp-nanos`, because PHP's DateTime doesn't have nanosecond precision.

To use the default logical types, simply create an AvroFactory without any options:
```php
$avroFactory = AvroFactory::create();
```

#### Overriding Logical Types
You can override default logical types or add custom ones by providing factory implementations:
```php
$defaultLogicalTypeFactories = AvroFactory::getDefaultLogicalTypeFactories();
$defaultLogicalTypeFactories['custom'] = new MyCustomLogicalTypeFactory();
$options = new Options(logicalTypeFactories: $defaultLogicalTypeFactories);
$avroFactory = AvroFactory::create($options);
```

#### Disabling Logical Types
To disable all logical type processing and treat them as their underlying primitive types:
```php
$options = new Options(logicalTypeFactories: []);
$avroFactory = AvroFactory::create($options);
```

### Value Objects for Logical Types

Some logical types work with their respective value objects to provide type safety and better representation of the data:

- `decimal`: `Auxmoney\Avro\ValueObject\Decimal`
- `duration`: `Auxmoney\Avro\ValueObject\Duration`
- `time-millis`: `Auxmoney\Avro\ValueObject\TimeOfDay`
- `time-micros`: `Auxmoney\Avro\ValueObject\TimeOfDay`
- `uuid`: `Auxmoney\Avro\ValueObject\Uuid`

The `local-timestamp-*` and `timestamp-*` types are serialized from/deserialized to `DateTimeInterface`.

## Documentation

For more detailed documentation on usage, schema design, and advanced features like schema evolution, please refer to the official Avro documentation.

## Contribution

Contributions are welcome! If you find a bug or want to suggest a new feature, feel free to open an issue or submit a pull request.

## Development Setup

This project uses VS Code Dev Containers for development. This ensures a consistent development environment across all contributors.

### Using VS Code Dev Container (Recommended)

1. Install the [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers) in VS Code
2. Open the project in VS Code
3. When prompted, click "Reopen in Container" or use `Ctrl+Shift+P` → "Dev Containers: Reopen in Container"

The container includes:
- PHP 8.3 with Xdebug
- Composer for dependency management
- PHPUnit for testing
- Node.js for test generation tools
- Git and GitHub CLI

### Manual Docker Usage

You can also use the containers manually:

```bash
# Using the convenience script (recommended)
./docker-dev run --rm dev
./docker-dev run --rm dev vendor/bin/phpunit
./docker-dev run --rm test-generator

# Or using docker compose directly
docker compose -f .devcontainer/docker-compose.yaml run --rm dev
docker compose -f .devcontainer/docker-compose.yaml run --rm dev vendor/bin/phpunit
docker compose -f .devcontainer/docker-compose.yaml run --rm test-generator
```

For more details, see [.devcontainer/README.md](.devcontainer/README.md).

## License

This library is licensed under the Apache License 2.0.

## Changelog

For the detailed changelog, please refer to the Releases page.

