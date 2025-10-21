<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Support;

use Auxmoney\Avro\Contracts\Options;
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

/**
 * @phpstan-import-type NormalizedSchema from SchemaHelper
 * @phpstan-import-type PrimitiveSchema from SchemaHelper
 * @phpstan-import-type UnionSchema from SchemaHelper
 * @phpstan-import-type RecordSchema from SchemaHelper
 * @phpstan-import-type ArraySchema from SchemaHelper
 * @phpstan-import-type EnumSchema from SchemaHelper
 * @phpstan-import-type MapSchema from SchemaHelper
 * @phpstan-import-type FixedSchema from SchemaHelper
 */
class WriterFactory
{
    public function __construct(
        private readonly BinaryEncoder $encoder,
        private readonly SchemaHelper $schemaHelper,
        private readonly Options $options,
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
     * @param array<mixed>|string $schema
     * @throws InvalidSchemaException
     */
    private function getSchemaWriter(array|string $schema): WriterInterface
    {
        $nomalizedSchema = $this->schemaHelper->normalizeSchema($schema);
        $rawWriter = $this->getRawWriter($nomalizedSchema);

        $logicalType = $this->schemaHelper->getLogicalType($nomalizedSchema);
        if ($logicalType !== null) {
            return new LogicalTypeWriter($rawWriter, $logicalType);
        }

        return $rawWriter;
    }

    /**
     * @param NormalizedSchema $schema
     * @throws InvalidSchemaException
     */
    private function getRawWriter(array $schema): WriterInterface
    {
        return match ($schema['type']) {
            'null' => new NullWriter(),
            'boolean' => new BooleanWriter(),
            'int', 'long' => new LongWriter($this->encoder),
            'float' => new FloatWriter($this->encoder),
            'double' => new DoubleWriter($this->encoder),
            'bytes', 'string' => new StringWriter($this->encoder),
            'record' => $this->getRecordWriter($schema),
            'array' => new ArrayWriter(
                $this->getSchemaWriter($schema['items']),
                $this->encoder,
                $this->options->arrayBlockCount,
                $this->options->arrayWriteBlockSize,
            ),
            'enum' => new EnumWriter($schema['symbols'], $this->encoder),
            'map' => new MapWriter(
                $this->getSchemaWriter($schema['values']),
                $this->encoder,
                $this->options->mapBlockCount,
                $this->options->mapWriteBlockSize,
            ),
            'fixed' => new FixedWriter($schema['size']),
            'union' => new UnionWriter(array_map($this->getSchemaWriter(...), $schema['branches']), $this->encoder),
        };
    }

    /**
     * @param RecordSchema $schema
     * @throws InvalidSchemaException
     */
    private function getRecordWriter(array $schema): RecordWriter
    {
        $propertyWriters = [];

        foreach ($schema['fields'] as $field) {
            $propertyTypeWriter = $this->getSchemaWriter($field['type']);
            $propertyWriters[] = new PropertyWriter($propertyTypeWriter, $field['name']);
        }

        return new RecordWriter($propertyWriters);
    }
}
