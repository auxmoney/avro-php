<?php

namespace Auxmoney\Avro\IO;

use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Deserialization\ArrayReader;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\Deserialization\BooleanReader;
use Auxmoney\Avro\Deserialization\DoubleReader;
use Auxmoney\Avro\Deserialization\EnumReader;
use Auxmoney\Avro\Deserialization\FloatReader;
use Auxmoney\Avro\Deserialization\LogicalTypeReader;
use Auxmoney\Avro\Deserialization\LongReader;
use Auxmoney\Avro\Deserialization\NullReader;
use Auxmoney\Avro\Deserialization\PropertyReader;
use Auxmoney\Avro\Deserialization\RecordReader;
use Auxmoney\Avro\Deserialization\StringReader;
use Auxmoney\Avro\Deserialization\UnionReader;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\LogicalTypeResolver;
use JsonException;

class ReaderFactory
{
    public function __construct(
        private readonly LogicalTypeResolver $logicalTypeResolver,
        private readonly BinaryDecoder $decoder
    ) {
    }

    /**
     * @throws InvalidSchemaException
     */
    public function create(string $writerSchema, ?string $readerSchema = null): ReaderInterface
    {
        // TODO: implement schema resolution

        try {
            $datum = json_decode($writerSchema, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidSchemaException('Unable to parse AVRO schema as JSON', previous: $exception);
        }

        if (!is_array($datum)) {
            throw new InvalidSchemaException('Expected AVRO schema to be an array');
        }

        return $this->getSchemaReader($datum);
    }

    /**
     * @throws InvalidSchemaException
     */
    private function getSchemaReader(string|array $datum): ReaderInterface
    {
        $rawReader = $this->getRawReader($datum);

        $logicalType = $datum['logicalType'] ?? null;
        if ($logicalType !== null) {
            if (!is_string($logicalType)) {
                throw new InvalidSchemaException('AVRO schema logicalType must be a string');
            }

            $logicalTypeFactory = $this->logicalTypeResolver->resolve($logicalType);
            if ($logicalTypeFactory !== null) {
                return new LogicalTypeReader($rawReader, $logicalTypeFactory->create($datum));
            }
        }

        return $rawReader;
    }

    /**
     * @throws InvalidSchemaException
     */
    private function getRawReader(string|array $datum): ReaderInterface
    {
        if (is_string($datum)) {
            return match ($datum) {
                'null' => new NullReader(),
                'boolean' => new BooleanReader(),
                'int', 'long' => new LongReader($this->decoder),
                'float' => new FloatReader($this->decoder),
                'double' => new DoubleReader($this->decoder),
                'bytes', 'string' => new StringReader($this->decoder),
                default => throw new InvalidSchemaException("Unknown AVRO schema type '$datum'"),
            };
        }

        if ($this->isArrayIndexed($datum)) {
            return new UnionReader(array_map(fn($branch) => $this->getSchemaReader($branch), $datum), $this->decoder);
        }

        if (!isset($datum['type'])) {
            throw new InvalidSchemaException('AVRO schema is missing type');
        }

        return match ($datum['type']) {
            'record' => $this->getRecordReader($datum),
            'array' => $this->getArrayReader($datum),
            'enum' => $this->getEnumReader($datum),
            default => $this->getSchemaReader($datum['type']),
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
     * @param array<mixed> $datum
     * @throws InvalidSchemaException
     */
    public function getRecordReader(array $datum): RecordReader
    {
        if (!isset($datum['fields'])) {
            throw new InvalidSchemaException('AVRO record schema is missing fields');
        }

        if (!is_array($datum['fields'])) {
            throw new InvalidSchemaException('AVRO record schema fields must be an array');
        }

        $propertyReaders = [];

        foreach ($datum['fields'] as $field) {
            if (!is_array($field)) {
                throw new InvalidSchemaException('AVRO record schema field must be an array');
            }

            if (!isset($field['name']) || !is_string($field['name'])) {
                throw new InvalidSchemaException('AVRO record schema field is missing name');
            }

            if (!isset($field['type'])) {
                throw new InvalidSchemaException('AVRO record schema field is missing type');
            }

            $propertyTypeReader = $this->getSchemaReader($field['type']);
            $hasDefault = array_key_exists('default', $field);
            $propertyReaders[] = new PropertyReader($propertyTypeReader, $field['name'], $hasDefault, $field['default'] ?? null);
        }

        return new RecordReader($propertyReaders);
    }

    /**
     * @param array<mixed> $datum
     * @throws InvalidSchemaException
     */
    private function getArrayReader($datum): ArrayReader
    {
        $items = $datum['items'] ?? throw new InvalidSchemaException('AVRO array schema is missing items');

        return new ArrayReader($this->getSchemaReader($items), $this->decoder);
    }

    /**
     * @param array<mixed> $datum
     * @throws InvalidSchemaException
     */
    public function getEnumReader($datum): EnumReader
    {
        if (!isset($datum['symbols']) || !is_array($datum['symbols'])) {
            throw new InvalidSchemaException('AVRO enum schema is missing symbols');
        }

        if (empty($datum['symbols'])) {
            throw new InvalidSchemaException('AVRO enum schema symbols cannot be empty');
        }

        $symbols = $datum['symbols'];
        foreach ($symbols as $symbol) {
            if (!is_string($symbol)) {
                throw new InvalidSchemaException('AVRO enum schema symbols must be strings');
            }

            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $symbol)) {
                throw new InvalidSchemaException("AVRO enum symbol '$symbol' is not a valid identifier");
            }
        }

        return new EnumReader($symbols, $this->decoder);
    }
}