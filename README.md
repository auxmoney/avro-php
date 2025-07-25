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

