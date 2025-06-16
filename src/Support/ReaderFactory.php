<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Support;

use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Deserialization\ArrayReader;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\Deserialization\BooleanReader;
use Auxmoney\Avro\Deserialization\DoubleReader;
use Auxmoney\Avro\Deserialization\EnumReader;
use Auxmoney\Avro\Deserialization\FixedReader;
use Auxmoney\Avro\Deserialization\FloatReader;
use Auxmoney\Avro\Deserialization\LogicalTypeReader;
use Auxmoney\Avro\Deserialization\LongReader;
use Auxmoney\Avro\Deserialization\MapReader;
use Auxmoney\Avro\Deserialization\NullReader;
use Auxmoney\Avro\Deserialization\PropertyReader;
use Auxmoney\Avro\Deserialization\RecordReader;
use Auxmoney\Avro\Deserialization\StringReader;
use Auxmoney\Avro\Deserialization\UnionReader;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;

class ReaderFactory
{
    public function __construct(
        private readonly BinaryDecoder $decoder,
        private readonly SchemaHelper $schemaHelper,
    ) {
    }

    /**
     * @throws InvalidSchemaException
     */
    public function create(string $writerSchema, ?string $readerSchema = null): ReaderInterface
    {
        $schema = $this->schemaHelper->parseSchema($writerSchema);

        return $this->getSchemaReader($schema);
    }

    /**
     * @throws InvalidSchemaException
     */
    private function getSchemaReader(mixed $schema): ReaderInterface
    {
        // TODO: implement schema resolution
        if (!is_string($schema) && !is_array($schema)) {
            throw new InvalidSchemaException('AVRO schema must be a string or an array');
        }

        $rawReader = $this->getRawReader($schema);

        $logicalType = $this->schemaHelper->getLogicalType($schema);
        if ($logicalType !== null) {
            return new LogicalTypeReader($rawReader, $logicalType);
        }

        return $rawReader;
    }

    /**
     * @param string|array<mixed> $schema
     * @throws InvalidSchemaException
     */
    private function getRawReader(string|array $schema): ReaderInterface
    {
        if (is_string($schema)) {
            return match ($schema) {
                'null' => new NullReader(),
                'boolean' => new BooleanReader(),
                'int', 'long' => new LongReader($this->decoder),
                'float' => new FloatReader($this->decoder),
                'double' => new DoubleReader($this->decoder),
                'bytes', 'string' => new StringReader($this->decoder),
                default => throw new InvalidSchemaException("Unknown AVRO schema type '{$schema}'"),
            };
        }

        if ($this->isArrayIndexed($schema)) {
            return new UnionReader(array_map(fn ($branch) => $this->getSchemaReader($branch), $schema), $this->decoder);
        }

        if (!isset($schema['type'])) {
            throw new InvalidSchemaException('AVRO schema is missing type');
        }

        return match ($schema['type']) {
            'record' => $this->getRecordReader($schema),
            'array' => $this->getArrayReader($schema),
            'enum' => $this->getEnumReader($schema),
            'map' => $this->getMapReader($schema),
            'fixed' => $this->getFixedReader($schema),
            default => $this->getSchemaReader($schema['type']),
        };
    }

    /**
     * @param array<mixed> $array
     */
    private function isArrayIndexed(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * @param array<mixed> $schema
     * @throws InvalidSchemaException
     */
    private function getRecordReader(array $schema): RecordReader
    {
        $propertyReaders = [];

        foreach ($this->schemaHelper->getRecordFields($schema) as $field) {
            $propertyTypeReader = $this->getSchemaReader($field['type']);
            $hasDefault = array_key_exists('default', $field);
            $propertyReaders[] = new PropertyReader($propertyTypeReader, $field['name'], $hasDefault, $field['default'] ?? null);
        }

        return new RecordReader($propertyReaders);
    }

    /**
     * @param array<mixed> $schema
     * @throws InvalidSchemaException
     */
    private function getArrayReader(array $schema): ArrayReader
    {
        $items = $this->schemaHelper->getArrayItems($schema);

        return new ArrayReader($this->getSchemaReader($items), $this->decoder);
    }

    /**
     * @param array<mixed> $schema
     * @throws InvalidSchemaException
     */
    private function getEnumReader(array $schema): EnumReader
    {
        $symbols = $this->schemaHelper->getEnumSymbols($schema);

        return new EnumReader($symbols, $this->decoder);
    }

    /**
     * @param array<mixed> $schema
     * @throws InvalidSchemaException
     */
    private function getMapReader(array $schema): ReaderInterface
    {
        $values = $this->schemaHelper->getMapValues($schema);

        return new MapReader($this->getSchemaReader($values), $this->decoder);
    }

    /**
     * @param array<mixed> $schema
     * @throws InvalidSchemaException
     */
    private function getFixedReader(array $schema): FixedReader
    {
        return new FixedReader($this->schemaHelper->getFixedSize($schema));
    }
}
