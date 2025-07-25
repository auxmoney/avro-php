<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Support;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\Deserialization\BooleanReader;
use Auxmoney\Avro\Deserialization\CastReader;
use Auxmoney\Avro\Deserialization\DoubleReader;
use Auxmoney\Avro\Deserialization\FloatReader;
use Auxmoney\Avro\Deserialization\LogicalTypeReader;
use Auxmoney\Avro\Deserialization\LongReader;
use Auxmoney\Avro\Deserialization\NullReader;
use Auxmoney\Avro\Deserialization\StringReader;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\Exceptions\SchemaMismatchException;
use Auxmoney\Avro\Support\LogicalTypeResolver;
use Auxmoney\Avro\Support\ReaderFactory;
use Auxmoney\Avro\Support\SchemaHelper;
use PHPUnit\Framework\TestCase;

class ReaderFactoryTest extends TestCase
{
    private ReaderFactory $readerFactory;
    private BinaryDecoder $decoder;
    private SchemaHelper $schemaHelper;

    protected function setUp(): void
    {
        $this->decoder = new BinaryDecoder();
        $logicalTypeResolver = new LogicalTypeResolver([]);
        $this->schemaHelper = new SchemaHelper($logicalTypeResolver);
        $this->readerFactory = new ReaderFactory($this->decoder, $this->schemaHelper);
    }

    public function testCreateWithSimpleStringSchema(): void
    {
        $schema = '{"type": "string"}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(StringReader::class, $result);
    }

    public function testCreateWithNullSchema(): void
    {
        $schema = '{"type": "null"}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(NullReader::class, $result);
    }

    public function testCreateWithBooleanSchema(): void
    {
        $schema = '{"type": "boolean"}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(BooleanReader::class, $result);
    }

    public function testCreateWithIntSchema(): void
    {
        $schema = '{"type": "int"}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(LongReader::class, $result);
    }

    public function testCreateWithLongSchema(): void
    {
        $schema = '{"type": "long"}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(LongReader::class, $result);
    }

    public function testCreateWithFloatSchema(): void
    {
        $schema = '{"type": "float"}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(FloatReader::class, $result);
    }

    public function testCreateWithDoubleSchema(): void
    {
        $schema = '{"type": "double"}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(DoubleReader::class, $result);
    }

    public function testCreateWithBytesSchema(): void
    {
        $schema = '{"type": "bytes"}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(StringReader::class, $result);
    }

    public function testCreateWithUnionSchema(): void
    {
        $schema = '["string", "int"]';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\UnionReader::class, $result);
    }

    public function testCreateWithRecordSchema(): void
    {
        $schema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "string"}]}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\RecordReader::class, $result);
    }

    public function testCreateWithArraySchema(): void
    {
        $schema = '{"type": "array", "items": "string"}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\ArrayReader::class, $result);
    }

    public function testCreateWithMapSchema(): void
    {
        $schema = '{"type": "map", "values": "string"}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\MapReader::class, $result);
    }

    public function testCreateWithFixedSchema(): void
    {
        $schema = '{"type": "fixed", "name": "test", "size": 16}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\FixedReader::class, $result);
    }

    public function testCreateWithEnumSchema(): void
    {
        $schema = '{"type": "enum", "name": "test", "symbols": ["A", "B"]}';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\EnumReader::class, $result);
    }

    // Schema Evolution Tests

    public function testCreateWithReaderSchemaIntToLong(): void
    {
        $writerSchema = '{"type": "int"}';
        $readerSchema = '{"type": "long"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(LongReader::class, $result);
    }

    public function testCreateWithReaderSchemaIntToFloat(): void
    {
        $writerSchema = '{"type": "int"}';
        $readerSchema = '{"type": "float"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(CastReader::class, $result);
    }

    public function testCreateWithReaderSchemaIntToDouble(): void
    {
        $writerSchema = '{"type": "int"}';
        $readerSchema = '{"type": "double"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(CastReader::class, $result);
    }

    public function testCreateWithReaderSchemaIntToFloatInRecord(): void
    {
        $writerSchema = '{"type": "record", "name": "test", "fields": [{"name": "value", "type": "int"}]}';
        $readerSchema = '{"type": "record", "name": "test", "fields": [{"name": "value", "type": "float"}]}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\RecordReader::class, $result);
    }

    public function testCreateWithReaderSchemaIntToDoubleInRecord(): void
    {
        $writerSchema = '{"type": "record", "name": "test", "fields": [{"name": "value", "type": "int"}]}';
        $readerSchema = '{"type": "record", "name": "test", "fields": [{"name": "value", "type": "double"}]}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\RecordReader::class, $result);
    }

    public function testCreateWithReaderSchemaLongToFloat(): void
    {
        $writerSchema = '{"type": "long"}';
        $readerSchema = '{"type": "float"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(CastReader::class, $result);
    }

    public function testCreateWithReaderSchemaLongToDouble(): void
    {
        $writerSchema = '{"type": "long"}';
        $readerSchema = '{"type": "double"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(CastReader::class, $result);
    }

    public function testCreateWithReaderSchemaFloatToDouble(): void
    {
        $writerSchema = '{"type": "float"}';
        $readerSchema = '{"type": "double"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(CastReader::class, $result);
    }

    public function testCreateWithReaderSchemaBytesToString(): void
    {
        $writerSchema = '{"type": "bytes"}';
        $readerSchema = '{"type": "string"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(StringReader::class, $result);
    }

    public function testCreateWithReaderSchemaStringToBytes(): void
    {
        $writerSchema = '{"type": "string"}';
        $readerSchema = '{"type": "bytes"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(StringReader::class, $result);
    }

    public function testCreateWithReaderSchemaSameType(): void
    {
        $writerSchema = '{"type": "string"}';
        $readerSchema = '{"type": "string"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(StringReader::class, $result);
    }

    // Union Schema Evolution Tests

    public function testCreateWithUnionReaderSchema(): void
    {
        $writerSchema = '{"type": "string"}';
        $readerSchema = '["string", "int"]';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        // When writer schema is not union but reader schema is union,
        // it finds the matching branch and returns that reader, not a UnionReader
        $this->assertInstanceOf(StringReader::class, $result);
    }

    public function testCreateWithUnionToUnionSchema(): void
    {
        $writerSchema = '["string", "int"]';
        $readerSchema = '["string", "long", "float"]';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\UnionReader::class, $result);
    }

    public function testCreateWithUnionToSingleSchema(): void
    {
        $writerSchema = '["string", "int"]';
        $readerSchema = '{"type": "string"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\UnionReader::class, $result);
    }

    // Record Schema Evolution Tests

    public function testCreateWithRecordReaderSchema(): void
    {
        $writerSchema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "string"}]}';
        $readerSchema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "string"}, {"name": "field2", "type": "int", "default": 42}]}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\RecordReader::class, $result);
    }

    public function testCreateWithRecordReaderSchemaFieldRemoval(): void
    {
        $writerSchema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "string"}, {"name": "field2", "type": "int"}]}';
        $readerSchema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "string"}]}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\RecordReader::class, $result);
    }

    public function testCreateWithRecordReaderSchemaFieldTypeChange(): void
    {
        $writerSchema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "int"}]}';
        $readerSchema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "long"}]}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\RecordReader::class, $result);
    }

    public function testCreateWithRecordReaderSchemaWithDefaults(): void
    {
        $writerSchema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "string", "default": "default"}]}';
        $readerSchema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "string"}]}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\RecordReader::class, $result);
    }

    // Array Schema Evolution Tests

    public function testCreateWithArrayReaderSchema(): void
    {
        $writerSchema = '{"type": "array", "items": "int"}';
        $readerSchema = '{"type": "array", "items": "long"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\ArrayReader::class, $result);
    }

    // Map Schema Evolution Tests

    public function testCreateWithMapReaderSchema(): void
    {
        $writerSchema = '{"type": "map", "values": "int"}';
        $readerSchema = '{"type": "map", "values": "long"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\MapReader::class, $result);
    }

    // Enum Schema Evolution Tests

    public function testCreateWithEnumReaderSchema(): void
    {
        $writerSchema = '{"type": "enum", "name": "test", "symbols": ["A", "B"]}';
        $readerSchema = '{"type": "enum", "name": "test", "symbols": ["A", "B", "C"], "default": "C"}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\EnumReader::class, $result);
    }

    // Fixed Schema Evolution Tests

    public function testCreateWithFixedReaderSchema(): void
    {
        $writerSchema = '{"type": "fixed", "name": "test", "size": 16}';
        $readerSchema = '{"type": "fixed", "name": "test", "size": 16}';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\FixedReader::class, $result);
    }

    // Logical Type Tests

    public function testCreateWithLogicalType(): void
    {
        $logicalType = $this->createMock(\Auxmoney\Avro\Contracts\LogicalTypeInterface::class);
        $logicalTypeFactory = $this->createMock(LogicalTypeFactoryInterface::class);
        $logicalTypeFactory->method('getName')->willReturn('date');
        $logicalTypeFactory->method('create')->willReturn($logicalType);

        $logicalTypeResolver = new LogicalTypeResolver([$logicalTypeFactory]);
        $schemaHelper = new SchemaHelper($logicalTypeResolver);
        $readerFactory = new ReaderFactory($this->decoder, $schemaHelper);

        $schema = '{"type": "int", "logicalType": "date"}';

        $result = $readerFactory->create($schema);

        $this->assertInstanceOf(LogicalTypeReader::class, $result);
    }

    // Error Cases

    public function testCreateWithInvalidSchemaThrowsException(): void
    {
        $this->expectException(InvalidSchemaException::class);

        $this->readerFactory->create('invalid json');
    }

    public function testCreateWithIncompatibleTypesThrowsException(): void
    {
        $this->expectException(SchemaMismatchException::class);

        $writerSchema = '{"type": "string"}';
        $readerSchema = '{"type": "int"}';

        $this->readerFactory->create($writerSchema, $readerSchema);
    }

    public function testCreateWithRecordReaderSchemaMismatchThrowsException(): void
    {
        $this->expectException(SchemaMismatchException::class);

        $writerSchema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "string"}]}';
        $readerSchema = '{"type": "string"}';

        $this->readerFactory->create($writerSchema, $readerSchema);
    }

    public function testCreateWithArrayReaderSchemaMismatchThrowsException(): void
    {
        $this->expectException(SchemaMismatchException::class);

        $writerSchema = '{"type": "array", "items": "string"}';
        $readerSchema = '{"type": "string"}';

        $this->readerFactory->create($writerSchema, $readerSchema);
    }

    public function testCreateWithMapReaderSchemaMismatchThrowsException(): void
    {
        $this->expectException(SchemaMismatchException::class);

        $writerSchema = '{"type": "map", "values": "string"}';
        $readerSchema = '{"type": "string"}';

        $this->readerFactory->create($writerSchema, $readerSchema);
    }

    public function testCreateWithEnumReaderSchemaMismatchThrowsException(): void
    {
        $this->expectException(SchemaMismatchException::class);

        $writerSchema = '{"type": "enum", "name": "test", "symbols": ["A", "B"]}';
        $readerSchema = '{"type": "string"}';

        $this->readerFactory->create($writerSchema, $readerSchema);
    }

    public function testCreateWithFixedReaderSchemaMismatchThrowsException(): void
    {
        $this->expectException(SchemaMismatchException::class);

        $writerSchema = '{"type": "fixed", "name": "test", "size": 16}';
        $readerSchema = '{"type": "string"}';

        $this->readerFactory->create($writerSchema, $readerSchema);
    }

    public function testCreateWithFixedReaderSchemaSizeMismatchThrowsException(): void
    {
        $this->expectException(SchemaMismatchException::class);

        $writerSchema = '{"type": "fixed", "name": "test", "size": 16}';
        $readerSchema = '{"type": "fixed", "name": "test", "size": 32}';

        $this->readerFactory->create($writerSchema, $readerSchema);
    }

    public function testCreateWithRecordReaderSchemaMissingFieldNoDefaultThrowsException(): void
    {
        $this->expectException(SchemaMismatchException::class);

        $writerSchema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "string"}]}';
        $readerSchema = '{"type": "record", "name": "test", "fields": [{"name": "field1", "type": "string"}, {"name": "field2", "type": "int"}]}';

        $this->readerFactory->create($writerSchema, $readerSchema);
    }

    public function testCreateWithEnumReaderSchemaMissingSymbolNoDefaultThrowsException(): void
    {
        $this->expectException(SchemaMismatchException::class);

        $writerSchema = '{"type": "enum", "name": "test", "symbols": ["A", "B"]}';
        $readerSchema = '{"type": "enum", "name": "test", "symbols": ["A"]}';

        $this->readerFactory->create($writerSchema, $readerSchema);
    }

    public function testCreateWithUnionReaderSchemaNoMatchingBranchThrowsException(): void
    {
        $this->expectException(SchemaMismatchException::class);

        $writerSchema = '{"type": "string"}';
        $readerSchema = '["int", "long"]';

        $this->readerFactory->create($writerSchema, $readerSchema);
    }

    public function testCreateWithUnionToUnionNoMatchThrowsException(): void
    {
        $this->expectException(SchemaMismatchException::class);

        $writerSchema = '["string", "boolean"]';
        $readerSchema = '["long", "float"]';

        $this->readerFactory->create($writerSchema, $readerSchema);
    }

    // Edge Cases

    public function testCreateWithComplexNestedSchema(): void
    {
        $schema = '{
            "type": "record",
            "name": "complex",
            "fields": [
                {"name": "id", "type": "int"},
                {"name": "name", "type": "string"},
                {"name": "tags", "type": {"type": "array", "items": "string"}},
                {"name": "metadata", "type": {"type": "map", "values": "string"}},
                {"name": "status", "type": {"type": "enum", "name": "status", "symbols": ["ACTIVE", "INACTIVE"]}},
                {"name": "data", "type": ["null", "string"]}
            ]
        }';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\RecordReader::class, $result);
    }

    public function testCreateWithComplexNestedSchemaEvolution(): void
    {
        $writerSchema = '{
            "type": "record",
            "name": "complex",
            "fields": [
                {"name": "id", "type": "int"},
                {"name": "name", "type": "string"},
                {"name": "tags", "type": {"type": "array", "items": "string"}}
            ]
        }';

        $readerSchema = '{
            "type": "record",
            "name": "complex",
            "fields": [
                {"name": "id", "type": "long"},
                {"name": "name", "type": "string"},
                {"name": "tags", "type": {"type": "array", "items": "string"}},
                {"name": "metadata", "type": {"type": "map", "values": "string"}, "default": {}},
                {"name": "status", "type": {"type": "enum", "name": "status", "symbols": ["ACTIVE", "INACTIVE"]}, "default": "ACTIVE"}
            ]
        }';

        $result = $this->readerFactory->create($writerSchema, $readerSchema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\RecordReader::class, $result);
    }

    public function testCreateWithArrayOfRecords(): void
    {
        $schema = '{
            "type": "array",
            "items": {
                "type": "record",
                "name": "item",
                "fields": [
                    {"name": "id", "type": "int"},
                    {"name": "name", "type": "string"}
                ]
            }
        }';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\ArrayReader::class, $result);
    }

    public function testCreateWithMapOfRecords(): void
    {
        $schema = '{
            "type": "map",
            "values": {
                "type": "record",
                "name": "item",
                "fields": [
                    {"name": "id", "type": "int"},
                    {"name": "name", "type": "string"}
                ]
            }
        }';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\MapReader::class, $result);
    }

    public function testCreateWithUnionOfComplexTypes(): void
    {
        $schema = '[
            {"type": "record", "name": "user", "fields": [{"name": "id", "type": "int"}, {"name": "name", "type": "string"}]},
            {"type": "record", "name": "admin", "fields": [{"name": "id", "type": "int"}, {"name": "name", "type": "string"}, {"name": "role", "type": "string"}]}
        ]';

        $result = $this->readerFactory->create($schema);

        $this->assertInstanceOf(\Auxmoney\Avro\Deserialization\UnionReader::class, $result);
    }
}
