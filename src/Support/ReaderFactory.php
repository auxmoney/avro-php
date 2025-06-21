<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Support;

use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Deserialization\ArrayReader;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\Deserialization\BooleanReader;
use Auxmoney\Avro\Deserialization\CastReader;
use Auxmoney\Avro\Deserialization\DoubleReader;
use Auxmoney\Avro\Deserialization\EnumReader;
use Auxmoney\Avro\Deserialization\FixedReader;
use Auxmoney\Avro\Deserialization\FloatReader;
use Auxmoney\Avro\Deserialization\LogicalTypeReader;
use Auxmoney\Avro\Deserialization\LongReader;
use Auxmoney\Avro\Deserialization\MapReader;
use Auxmoney\Avro\Deserialization\NullReader;
use Auxmoney\Avro\Deserialization\PropertyDefaultReader;
use Auxmoney\Avro\Deserialization\PropertyReader;
use Auxmoney\Avro\Deserialization\PropertySkipReader;
use Auxmoney\Avro\Deserialization\RecordPropertyReader;
use Auxmoney\Avro\Deserialization\RecordReader;
use Auxmoney\Avro\Deserialization\StringReader;
use Auxmoney\Avro\Deserialization\UnionReader;
use Auxmoney\Avro\Deserialization\UnmatchedBranchReader;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\Exceptions\SchemaMismatchException;

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
class ReaderFactory
{
    public function __construct(
        private readonly BinaryDecoder $decoder,
        private readonly SchemaHelper $schemaHelper,
    ) {
    }

    /**
     * @throws InvalidSchemaException
     * @throws SchemaMismatchException
     */
    public function create(string $writerSchema, ?string $readerSchema = null): ReaderInterface
    {
        $parsedWriterSchema = $this->schemaHelper->parseSchema($writerSchema);
        $parsedReaderSchema = $readerSchema === null ? null : $this->schemaHelper->parseSchema($readerSchema);

        return $this->getSchemaReader($parsedWriterSchema, $parsedReaderSchema);
    }

    /**
     * @param array<mixed>|string $writerSchema
     * @param null|array<mixed>|string $readerSchema
     * @throws InvalidSchemaException
     * @throws SchemaMismatchException
     */
    private function getSchemaReader(array|string $writerSchema, null|array|string $readerSchema): ReaderInterface
    {
        $nomalizedWriterSchema = $this->schemaHelper->normalizeSchema($writerSchema);
        $nomalizedReaderSchema = $readerSchema === null ? null : $this->schemaHelper->normalizeSchema($readerSchema);
        $rawReader = $this->getRawReader($nomalizedWriterSchema, $nomalizedReaderSchema);

        $logicalType = $this->schemaHelper->getLogicalType($nomalizedWriterSchema);
        if ($logicalType !== null) {
            return new LogicalTypeReader($rawReader, $logicalType);
        }

        return $rawReader;
    }

    /**
     * @param NormalizedSchema $writerSchema
     * @param null|NormalizedSchema $readerSchema
     * @throws InvalidSchemaException
     * @throws SchemaMismatchException
     */
    private function getRawReader(array $writerSchema, ?array $readerSchema): ReaderInterface
    {
        if ($readerSchema !== null && $readerSchema['type'] === 'union' && $writerSchema['type'] !== 'union') {
            foreach ($readerSchema['branches'] as $branch) {
                try {
                    return $this->getSchemaReader($writerSchema, $branch);
                } catch (SchemaMismatchException) {
                    continue;
                }
            }

            throw new SchemaMismatchException('No matching branch found in reader union schema for writer schema');
        }

        return match ($writerSchema['type']) {
            'union' => $this->getUnionReader($writerSchema, $readerSchema),
            'record' => $this->getRecordReader($writerSchema, $readerSchema),
            'array' => $this->getArrayReader($writerSchema, $readerSchema),
            'enum' => $this->getEnumReader($writerSchema, $readerSchema),
            'map' => $this->getMapReader($writerSchema, $readerSchema),
            'fixed' => $this->getFixedReader($writerSchema, $readerSchema),
            default => $this->getPrimitiveReader($writerSchema, $readerSchema),
        };
    }

    /**
     * @param UnionSchema $writerSchema
     * @param null|NormalizedSchema $readerSchema
     * @throws InvalidSchemaException
     * @throws SchemaMismatchException
     */
    private function getUnionReader(array $writerSchema, ?array $readerSchema): UnionReader
    {
        if ($readerSchema === null) {
            return new UnionReader(
                array_map(fn (array|string $branch) => $this->getSchemaReader($branch, null), $writerSchema['branches']),
                $this->decoder,
            );
        }

        $unionReaderSchema = $readerSchema['type'] === 'union' ? $readerSchema : $this->schemaHelper->normalizeSchema([$readerSchema]);
        assert($unionReaderSchema['type'] === 'union', 'At this point, reader schema must be a union type');

        $branchReaders = [];
        $foundMatch = false;
        foreach ($writerSchema['branches'] as $branchWriterSchema) {
            foreach ($unionReaderSchema['branches'] as $branchReaderSchema) {
                try {
                    $branchReaders[] = $this->getSchemaReader($branchWriterSchema, $branchReaderSchema);
                    $foundMatch = true;
                    continue 2;
                } catch (SchemaMismatchException) {
                    continue;
                }
            }

            $branchReaders[] = new UnmatchedBranchReader($this->getSchemaReader($branchWriterSchema, null));
        }

        if (!$foundMatch) {
            throw new SchemaMismatchException('No match found between reader schema and union writer schema');
        }

        return new UnionReader($branchReaders, $this->decoder);
    }

    /**
     * @param RecordSchema $writerSchema
     * @param null|NormalizedSchema $readerSchema
     * @throws InvalidSchemaException
     * @throws SchemaMismatchException
     */
    private function getRecordReader(array $writerSchema, ?array $readerSchema): RecordReader
    {
        /** @var array<RecordPropertyReader> $propertyReaders */
        $propertyReaders = [];
        $readerSchemaFields = [];
        if ($readerSchema !== null) {
            if ($readerSchema['type'] !== 'record') {
                throw new SchemaMismatchException('Reader schema must be a record type for record writer schema');
            }

            foreach ($readerSchema['fields'] as $field) {
                $readerSchemaFields[$field['name']] = $field;
            }
        }

        foreach ($writerSchema['fields'] as $field) {
            $fieldReaderType = $readerSchemaFields[$field['name']]['type'] ?? null;

            if ($fieldReaderType === null && $readerSchema !== null) {
                $propertyReaders[] = new PropertySkipReader($this->getSchemaReader($field['type'], null));
            } else {
                $hasDefault = array_key_exists('default', $field);
                $propertyReaders[] = new PropertyReader(
                    $this->getSchemaReader($field['type'], $fieldReaderType),
                    $field['name'],
                    $hasDefault,
                    $field['default'] ?? null,
                );
            }

            unset($readerSchemaFields[$field['name']]);
        }

        foreach ($readerSchemaFields as $fieldName => $field) {
            if (!array_key_exists('default', $field)) {
                throw new SchemaMismatchException("Reader schema field '{$fieldName}' doesn't exist in writer schema and has no default");
            }

            $propertyReaders[] = new PropertyDefaultReader($field['default'], $field['name']);
        }

        return new RecordReader($propertyReaders);
    }

    /**
     * @param ArraySchema $writerSchema
     * @param null|NormalizedSchema $readerSchema
     * @throws InvalidSchemaException
     * @throws SchemaMismatchException
     */
    private function getArrayReader(array $writerSchema, ?array $readerSchema): ArrayReader
    {
        $itemsReaderSchema = null;
        if ($readerSchema !== null) {
            if ($readerSchema['type'] !== 'array') {
                throw new SchemaMismatchException('Reader schema must be an array type for array writer schema');
            }
            $itemsReaderSchema = $readerSchema['items'];
        }

        return new ArrayReader($this->getSchemaReader($writerSchema['items'], $itemsReaderSchema), $this->decoder);
    }

    /**
     * @param EnumSchema $writerSchema
     * @param null|NormalizedSchema $readerSchema
     * @throws SchemaMismatchException
     */
    private function getEnumReader(array $writerSchema, ?array $readerSchema): EnumReader
    {
        if ($readerSchema === null) {
            return new EnumReader($writerSchema['symbols'], $this->decoder);
        }

        if ($readerSchema['type'] !== 'enum') {
            throw new SchemaMismatchException('Reader schema must be an enum type for enum writer schema');
        }

        $symbols = [];
        foreach ($writerSchema['symbols'] as $symbol) {
            if (in_array($symbol, $readerSchema['symbols'], true)) {
                $symbols[] = $symbol;
            }

            if (!array_key_exists('default', $readerSchema)) {
                throw new SchemaMismatchException("Symbol '{$symbol}' in writer schema not found in reader schema");
            }

            $symbols[] = $readerSchema['default'];
        }

        return new EnumReader($symbols, $this->decoder);
    }

    /**
     * @param MapSchema $writerSchema
     * @param null|NormalizedSchema $readerSchema
     * @throws InvalidSchemaException
     * @throws SchemaMismatchException
     */
    private function getMapReader(array $writerSchema, ?array $readerSchema): ReaderInterface
    {
        $readerValuesSchema = null;
        if ($readerSchema !== null) {
            if ($readerSchema['type'] !== 'map') {
                throw new SchemaMismatchException('Reader schema must be a map type for map writer schema');
            }
            $readerValuesSchema = $readerSchema['values'];
        }

        return new MapReader($this->getSchemaReader($writerSchema['values'], $readerValuesSchema), $this->decoder);
    }

    /**
     * @param FixedSchema $writerSchema
     * @param null|NormalizedSchema $readerSchema
     * @throws InvalidSchemaException
     * @throws SchemaMismatchException
     */
    private function getFixedReader(array $writerSchema, ?array $readerSchema): FixedReader
    {
        if ($readerSchema !== null) {
            if ($readerSchema['type'] !== 'fixed') {
                throw new SchemaMismatchException('Reader schema must be a fixed type for fixed writer schema');
            }
            if ($readerSchema['size'] !== $writerSchema['size']) {
                throw new SchemaMismatchException('Fixed size mismatch between writer and reader schema');
            }
        }
        return new FixedReader($writerSchema['size']);
    }

    /**
     * @param PrimitiveSchema $writerSchema
     * @param null|NormalizedSchema $readerSchema
     * @throws InvalidSchemaException
     * @throws SchemaMismatchException
     */
    private function getPrimitiveReader(array $writerSchema, ?array $readerSchema): ReaderInterface
    {
        $writerType = $writerSchema['type'];
        $writerReader = match ($writerType) {
            'null' => new NullReader(),
            'boolean' => new BooleanReader(),
            'int', 'long' => new LongReader($this->decoder),
            'float' => new FloatReader($this->decoder),
            'double' => new DoubleReader($this->decoder),
            'bytes', 'string' => new StringReader($this->decoder),
        };

        $readerType = $readerSchema['type'] ?? null;
        if ($readerSchema === null || $writerType === $readerType) {
            return $writerReader;
        }

        $castReader = match ($readerType) {
            'long' => match ($writerType) {
                'int' => $writerReader,
                default => null,
            },
            'float' => match ($writerType) {
                'int', 'long' => new CastReader($writerReader, floatval(...)),
                default => null,
            },
            'double' => match ($writerType) {
                'int', 'long', 'float' => new CastReader($writerReader, doubleval(...)),
                default => null,
            },
            'string' => match ($writerType) {
                'bytes' => $writerReader,
                default => null,
            },
            'bytes' => match ($writerType) {
                'string' => $writerReader,
                default => null,
            },
            default => null,
        };

        if ($castReader === null) {
            throw new SchemaMismatchException("Writer schema type '{$writerType}' does not match reader schema type '{$readerType}'");
        }

        return $castReader;
    }
}
