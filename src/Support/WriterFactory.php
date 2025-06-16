<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Support;

use Auxmoney\Avro\Contracts\WriterInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\Serialization\ArrayWriter;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Serialization\BooleanWriter;
use Auxmoney\Avro\Serialization\DoubleWriter;
use Auxmoney\Avro\Serialization\EnumWriter;
use Auxmoney\Avro\Serialization\FixedWriter;
use Auxmoney\Avro\Serialization\FloatWriter;
use Auxmoney\Avro\Serialization\LogicalTypeWriter;
use Auxmoney\Avro\Serialization\LongWriter;
use Auxmoney\Avro\Serialization\MapWriter;
use Auxmoney\Avro\Serialization\NullWriter;
use Auxmoney\Avro\Serialization\PropertyWriter;
use Auxmoney\Avro\Serialization\RecordWriter;
use Auxmoney\Avro\Serialization\StringWriter;
use Auxmoney\Avro\Serialization\UnionWriter;

class WriterFactory
{
    public function __construct(
        private readonly BinaryEncoder $encoder,
        private readonly SchemaHelper $schemaHelper,
    ) {
    }

    /**
     * @throws InvalidSchemaException
     */
    public function create(string $writer): ValidatorWriter
    {
        $schema = $this->schemaHelper->parseSchema($writer);

        return new ValidatorWriter($this->getSchemaWriter($schema));
    }

    /**
     * @throws InvalidSchemaException
     */
    private function getSchemaWriter(mixed $schema): WriterInterface
    {
        if (!is_string($schema) && !is_array($schema)) {
            throw new InvalidSchemaException('AVRO schema must be a string or an array');
        }

        $rawWriter = $this->getRawWriter($schema);

        $logicalType = $this->schemaHelper->getLogicalType($schema);
        if ($logicalType !== null) {
            return new LogicalTypeWriter($rawWriter, $logicalType);
        }

        return $rawWriter;
    }

    /**
     * @param string|array<mixed> $schema
     * @throws InvalidSchemaException
     */
    private function getRawWriter(string|array $schema): WriterInterface
    {
        if (is_string($schema)) {
            return match ($schema) {
                'null' => new NullWriter(),
                'boolean' => new BooleanWriter(),
                'int', 'long' => new LongWriter($this->encoder),
                'float' => new FloatWriter($this->encoder),
                'double' => new DoubleWriter($this->encoder),
                'bytes', 'string' => new StringWriter($this->encoder),
                default => throw new InvalidSchemaException("Unknown AVRO schema type '{$schema}'"),
            };
        }

        if ($this->isArrayIndexed($schema)) {
            return new UnionWriter(array_map(fn ($branch) => $this->getSchemaWriter($branch), $schema), $this->encoder);
        }

        if (!isset($schema['type'])) {
            throw new InvalidSchemaException('AVRO schema is missing type');
        }

        return match ($schema['type']) {
            'record' => $this->getRecordWriter($schema),
            'array' => $this->getArrayWriter($schema),
            'enum' => $this->getEnumWriter($schema),
            'map' => $this->getMapWriter($schema),
            'fixed' => $this->getFixedWriter($schema),
            default => $this->getSchemaWriter($schema['type']),
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
    private function getRecordWriter(array $schema): RecordWriter
    {
        $propertyWriters = [];

        foreach ($this->schemaHelper->getRecordFields($schema) as $field) {
            $propertyTypeWriter = $this->getSchemaWriter($field['type']);
            $hasDefault = array_key_exists('default', $field);
            $propertyWriters[] = new PropertyWriter($propertyTypeWriter, $field['name'], $hasDefault, $field['default'] ?? null);
        }

        return new RecordWriter($propertyWriters);
    }

    /**
     * @param array<mixed> $schema
     * @throws InvalidSchemaException
     */
    private function getArrayWriter(array $schema): ArrayWriter
    {
        $items = $this->schemaHelper->getArrayItems($schema);

        return new ArrayWriter($this->getSchemaWriter($items), $this->encoder);
    }

    /**
     * @param array<mixed> $schema
     * @throws InvalidSchemaException
     */
    private function getEnumWriter(array $schema): EnumWriter
    {
        $symbols = $this->schemaHelper->getEnumSymbols($schema);

        return new EnumWriter($symbols, $this->encoder);
    }

    /**
     * @param array<mixed> $schema
     * @throws InvalidSchemaException
     */
    private function getMapWriter(array $schema): MapWriter
    {
        $values = $this->schemaHelper->getMapValues($schema);

        return new MapWriter($this->getSchemaWriter($values), $this->encoder);
    }

    /**
     * @param array<mixed> $schema
     * @throws InvalidSchemaException
     */
    private function getFixedWriter(array|string $schema): FixedWriter
    {
        return new FixedWriter($this->schemaHelper->getFixedSize($schema));
    }
}
