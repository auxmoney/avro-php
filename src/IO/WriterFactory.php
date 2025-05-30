<?php

namespace Auxmoney\Avro\IO;

use Auxmoney\Avro\Contracts\WriterInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\LogicalTypeResolver;
use Auxmoney\Avro\Serialization\ArrayWriter;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Serialization\BooleanWriter;
use Auxmoney\Avro\Serialization\DoubleWriter;
use Auxmoney\Avro\Serialization\EnumWriter;
use Auxmoney\Avro\Serialization\FloatWriter;
use Auxmoney\Avro\Serialization\LogicalTypeWriter;
use Auxmoney\Avro\Serialization\LongWriter;
use Auxmoney\Avro\Serialization\NullWriter;
use Auxmoney\Avro\Serialization\PropertyWriter;
use Auxmoney\Avro\Serialization\RecordWriter;
use Auxmoney\Avro\Serialization\StringWriter;
use Auxmoney\Avro\Serialization\UnionWriter;
use JsonException;

class WriterFactory
{
    public function __construct(
        private readonly LogicalTypeResolver $logicalTypeResolver,
        private readonly BinaryEncoder $encoder,
    ) {
    }

    /**
     * @throws InvalidSchemaException
     */
    public function create(string $writer): ValidatorWriter
    {
        try {
            $datum = json_decode($writer, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidSchemaException('Unable to parse AVRO schema as JSON', previous: $exception);
        }

        if (!is_array($datum)) {
            throw new InvalidSchemaException('Expected AVRO schema to be an array');
        }

        return new ValidatorWriter($this->getSchemaWriter($datum));
    }

    /**
     * @throws InvalidSchemaException
     */
    private function getSchemaWriter(string|array $datum): WriterInterface
    {
        $rawWriter = $this->getRawWriter($datum);

        $logicalType = $datum['logicalType'] ?? null;
        if ($logicalType !== null) {
            if (!is_string($logicalType)) {
                throw new InvalidSchemaException('AVRO schema logicalType must be a string');
            }

            $logicalTypeFactory = $this->logicalTypeResolver->resolve($logicalType);
            if ($logicalTypeFactory !== null) {
                return new LogicalTypeWriter($rawWriter, $logicalTypeFactory->create($datum));
            }
        }

        return $rawWriter;
    }

    /**
     * @throws InvalidSchemaException
     */
    private function getRawWriter(string|array $datum): WriterInterface
    {
        if (is_string($datum)) {
            return match ($datum) {
                'null' => new NullWriter(),
                'boolean' => new BooleanWriter(),
                'int', 'long' => new LongWriter($this->encoder),
                'float' => new FloatWriter($this->encoder),
                'double' => new DoubleWriter($this->encoder),
                'bytes', 'string' => new StringWriter($this->encoder),
                default => throw new InvalidSchemaException("Unknown AVRO schema type '$datum'"),
            };
        }

        if ($this->isArrayIndexed($datum)) {
            return new UnionWriter(array_map(fn($branch) => $this->getSchemaWriter($branch), $datum), $this->encoder);
        }

        if (!isset($datum['type'])) {
            throw new InvalidSchemaException('AVRO schema is missing type');
        }

        if (is_array($datum['type']) && isset($datum['type']['logicalType'])) {
            $logicalType = $datum['type']['logicalType'];
            if (!is_string($logicalType)) {
                throw new InvalidSchemaException('AVRO schema logicalType must be a string');
            }

            $rawWriter = $this->getRawWriter($logicalType);
            $logicalTypeFactory = $this->logicalTypeResolver->resolve($logicalType);
            if ($logicalTypeFactory !== null) {
                return new LogicalTypeWriter($rawWriter, $logicalTypeFactory->create($datum));
            }
        }

        return match ($datum['type']) {
            'record' => $this->getRecordWriter($datum),
            'array' => $this->getArrayWriter($datum),
            'enum' => $this->getEnumWriter($datum),
            default => $this->getSchemaWriter($datum['type']),
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
    public function getRecordWriter(array $datum): RecordWriter
    {
        if (!isset($datum['fields'])) {
            throw new InvalidSchemaException('AVRO record schema is missing fields');
        }

        if (!is_array($datum['fields'])) {
            throw new InvalidSchemaException('AVRO record schema fields must be an array');
        }

        $propertyWriters = [];

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

            $propertyTypeWriter = $this->getSchemaWriter($field['type']);
            $hasDefault = array_key_exists('default', $field);
            $propertyWriters[] = new PropertyWriter($propertyTypeWriter, $field['name'], $hasDefault, $field['default'] ?? null);
        }

        return new RecordWriter($propertyWriters);
    }

    /**
     * @param array<mixed> $datum
     * @throws InvalidSchemaException
     */
    private function getArrayWriter($datum): ArrayWriter
    {
        $items = $datum['items'] ?? throw new InvalidSchemaException('AVRO array schema is missing items');

        return new ArrayWriter($this->getSchemaWriter($items), $this->encoder);
    }

    /**
     * @param array<mixed> $datum
     * @throws InvalidSchemaException
     */
    public function getEnumWriter($datum): EnumWriter
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

        return new EnumWriter($symbols, $this->encoder);
    }
}