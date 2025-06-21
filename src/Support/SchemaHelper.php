<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Support;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use JsonException;

/**
 * @phpstan-type PrimitiveType "int"|"long"|"float"|"double"|"string"|"bytes"|"boolean"|"null"
 * @phpstan-type PrimitiveSchema array{type: PrimitiveType}&array<string, mixed>
 * @phpstan-type RecordSchema array{type: "record", fields: array<array{name: string, type: array<mixed>|string, default?: mixed}>}
 * @phpstan-type EnumSchema array{type: "enum", symbols: array<string>, default?: string}
 * @phpstan-type ArraySchema array{type: "array", items: array<mixed>|string}
 * @phpstan-type MapSchema array{type: "map", values: array<mixed>|string}
 * @phpstan-type FixedSchema array{type: "fixed", size: positive-int}
 * @phpstan-type UnionSchema array{type: "union", branches: array<array<mixed>|string>}
 * @phpstan-type NormalizedSchema PrimitiveSchema|RecordSchema|EnumSchema|ArraySchema|MapSchema|FixedSchema|UnionSchema
 */
class SchemaHelper
{
    public function __construct(
        private readonly LogicalTypeResolver $logicalTypeResolver,
    ) {
    }

    /**
     * @return array<mixed>|string
     * @throws InvalidSchemaException
     */
    public function parseSchema(string $writerSchema): array|string
    {
        try {
            $schema = json_decode($writerSchema, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidSchemaException('Unable to parse AVRO schema as JSON', previous: $exception);
        }

        if (!is_string($schema) && !is_array($schema)) {
            throw new InvalidSchemaException('AVRO schema must be a string or an array');
        }

        return $schema;
    }

    /**
     * @param array<mixed> $schema
     * @throws InvalidSchemaException
     */
    public function getLogicalType(array $schema): ?LogicalTypeInterface
    {
        if (!isset($schema['logicalType'])) {
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
     * @param array<mixed>|string $schema
     * @return NormalizedSchema
     * @throws InvalidSchemaException
     */
    public function normalizeSchema(array|string $schema): array
    {
        if (is_string($schema)) {
            return $this->normalizeSchema(['type' => $schema]);
        }

        if ($this->isArrayIndexed($schema)) {
            return ['type' => 'union', 'branches' => $this->getUnionBranches($schema)];
        }

        if (!isset($schema['type']) || !is_string($schema['type'])) {
            throw new InvalidSchemaException('AVRO schema must be a string or an array with a "type" key');
        }

        return match ($schema['type']) {
            'record' => $this->validateRecordSchema($schema),
            'enum' => $this->validateEnumSchema($schema),
            'array' => $this->validateArraySchema($schema),
            'map' => $this->validateMapSchema($schema),
            'fixed' => $this->validateFixedSchema($schema),
            'int', 'long', 'float', 'double', 'string', 'bytes', 'boolean', 'null' => $this->getPrimitiveSchema($schema),
            default => throw new InvalidSchemaException("AVRO schema type '{$schema['type']}' is not supported"),
        };
    }

    /**
     * @param array<mixed> $schema
     * @return RecordSchema
     * @throws InvalidSchemaException
     */
    private function validateRecordSchema(array $schema): array
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

            if (!is_string($field['type']) && !is_array($field['type'])) {
                throw new InvalidSchemaException('AVRO record schema field type must be a string or an array');
            }
        }

        /** @var RecordSchema $schema */
        return $schema;
    }

    /**
     * @param array<mixed> $schema
     * @return EnumSchema
     * @throws InvalidSchemaException
     */
    private function validateEnumSchema(array $schema): array
    {
        if (!isset($schema['symbols']) || !is_array($schema['symbols'])) {
            throw new InvalidSchemaException('AVRO enum schema is missing symbols');
        }

        if (empty($schema['symbols'])) {
            throw new InvalidSchemaException('AVRO enum schema symbols cannot be empty');
        }

        foreach ($schema['symbols'] as $symbol) {
            $this->validateEnumSymbol($symbol);
        }

        if (array_key_exists('default', $schema)) {
            $this->validateEnumSymbol($schema['default']);
        }

        /** @var EnumSchema $schema */
        return $schema;
    }

    /**
     * @param array<mixed> $schema
     * @return ArraySchema
     * @throws InvalidSchemaException
     */
    private function validateArraySchema(array $schema): array
    {
        if (!isset($schema['items'])) {
            throw new InvalidSchemaException('AVRO array schema is missing items');
        }

        if (!is_array($schema['items']) && !is_string($schema['items'])) {
            throw new InvalidSchemaException('AVRO array schema items must be an array or a string');
        }

        /** @var ArraySchema $schema */
        return $schema;
    }

    /**
     * @param array<mixed> $schema
     * @return MapSchema
     * @throws InvalidSchemaException
     */
    private function validateMapSchema(array $schema): array
    {
        if (!isset($schema['values'])) {
            throw new InvalidSchemaException('AVRO map schema is missing values');
        }

        if (!is_array($schema['values']) && !is_string($schema['values'])) {
            throw new InvalidSchemaException('AVRO map schema values must be an array or a string');
        }

        /** @var MapSchema $schema */
        return $schema;
    }

    /**
     * @param array<mixed> $array
     */
    private function isArrayIndexed(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * @throws InvalidSchemaException
     */
    private function validateEnumSymbol(mixed $symbol): void
    {
        if (!is_string($symbol)) {
            throw new InvalidSchemaException('AVRO enum schema symbols must be strings');
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $symbol)) {
            throw new InvalidSchemaException("AVRO enum symbol '{$symbol}' is not a valid identifier");
        }
    }

    /**
     * @param array<mixed> $schema
     * @return array<array<mixed>|string>
     * @throws InvalidSchemaException
     */
    private function getUnionBranches(array $schema): array
    {
        $branches = [];
        foreach ($schema as $branch) {
            if (!is_string($branch) && !is_array($branch)) {
                throw new InvalidSchemaException('AVRO union branches must be strings or arrays');
            }

            $branches[] = $branch;
        }

        return $branches;
    }

    /**
     * @param array<mixed> $schema
     * @return FixedSchema
     * @throws InvalidSchemaException
     */
    private function validateFixedSchema(array $schema): array
    {
        if (!isset($schema['size']) || !is_int($schema['size']) || $schema['size'] <= 0) {
            throw new InvalidSchemaException('AVRO fixed schema is missing or has invalid size');
        }

        /** @var FixedSchema $schema */
        return $schema;
    }

    /**
     * @param array<mixed> $schema
     * @return PrimitiveSchema
     */
    private function getPrimitiveSchema(array $schema): array
    {
        /** @var PrimitiveSchema $schema */
        return $schema;
    }
}
