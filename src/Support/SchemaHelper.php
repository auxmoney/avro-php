<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Support;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Generator;
use JsonException;

class SchemaHelper
{
    public function __construct(
        private readonly LogicalTypeResolver $logicalTypeResolver,
    ) {
    }

    /**
     * @return array<mixed>
     * @throws InvalidSchemaException
     */
    public function parseSchema(string $writerSchema): array
    {
        try {
            $schema = json_decode($writerSchema, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidSchemaException('Unable to parse AVRO schema as JSON', previous: $exception);
        }

        if (!is_array($schema)) {
            throw new InvalidSchemaException('Expected AVRO schema to be an array');
        }

        return $schema;
    }

    /**
     * @param string|array<mixed> $schema
     * @throws InvalidSchemaException
     */
    public function getLogicalType(string|array $schema): ?LogicalTypeInterface
    {
        if (!is_array($schema) || !isset($schema['logicalType'])) {
            return null;
        }

        $logicalType = $schema['logicalType'];
        if (!is_string($logicalType)) {
            throw new InvalidSchemaException('AVRO logical type must be a string');
        }

        $logicalTypeFactory = $this->logicalTypeResolver->resolve($logicalType);
        if ($logicalTypeFactory === null) {
            return null;
        }

        return $logicalTypeFactory->create($schema);
    }

    /**
     * @param array<mixed> $schema
     * @return Generator<array{name: string, type: mixed, default?: mixed}>
     * @throws InvalidSchemaException
     */
    public function getRecordFields(array $schema): Generator
    {
        if (!isset($schema['fields'])) {
            throw new InvalidSchemaException('AVRO record schema is missing fields');
        }

        if (!is_array($schema['fields'])) {
            throw new InvalidSchemaException('AVRO record schema fields must be an array');
        }

        foreach ($schema['fields'] as $field) {
            if (!is_array($field)) {
                throw new InvalidSchemaException('AVRO record schema field must be an array');
            }

            if (!isset($field['name']) || !is_string($field['name'])) {
                throw new InvalidSchemaException('AVRO record schema field is missing name');
            }

            if (!isset($field['type'])) {
                throw new InvalidSchemaException('AVRO record schema field is missing type');
            }

            $typedField = [
                'name' => $field['name'],
                'type' => $field['type'],
            ];
            if (isset($field['default'])) {
                $typedField['default'] = $field['default'];
            }

            yield $typedField;
        }
    }

    /**
     * @param array<mixed> $schema
     * @return array<mixed>|string
     * @throws InvalidSchemaException
     */
    public function getArrayItems(array $schema): array|string
    {
        if (!isset($schema['items'])) {
            throw new InvalidSchemaException('AVRO array schema is missing items');
        }

        if (!is_array($schema['items']) && !is_string($schema['items'])) {
            throw new InvalidSchemaException('AVRO array schema items must be an array or a string');
        }

        return $schema['items'];
    }

    /**
     * @param array<mixed> $schema
     * @return array<mixed>|string
     * @throws InvalidSchemaException
     */
    public function getMapValues(array $schema): array|string
    {
        if (!isset($schema['values'])) {
            throw new InvalidSchemaException('AVRO map schema is missing values');
        }

        if (!is_array($schema['values']) && !is_string($schema['values'])) {
            throw new InvalidSchemaException('AVRO map schema values must be an array or a string');
        }

        return $schema['values'];
    }

    /**
     * @param array<mixed> $schema
     * @return array<string>
     * @throws InvalidSchemaException
     */
    public function getEnumSymbols(array $schema): array
    {
        if (!isset($schema['symbols']) || !is_array($schema['symbols'])) {
            throw new InvalidSchemaException('AVRO enum schema is missing symbols');
        }

        if (empty($schema['symbols'])) {
            throw new InvalidSchemaException('AVRO enum schema symbols cannot be empty');
        }

        $symbols = [];
        foreach ($schema['symbols'] as $symbol) {
            if (!is_string($symbol)) {
                throw new InvalidSchemaException('AVRO enum schema symbols must be strings');
            }

            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $symbol)) {
                throw new InvalidSchemaException("AVRO enum symbol '{$symbol}' is not a valid identifier");
            }

            $symbols[] = $symbol;
        }

        return $symbols;
    }
}
