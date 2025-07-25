<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Support;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\Support\LogicalTypeResolver;
use Auxmoney\Avro\Support\SchemaHelper;
use PHPUnit\Framework\TestCase;

class SchemaHelperTest extends TestCase
{
    private SchemaHelper $schemaHelper;
    private LogicalTypeResolver&\PHPUnit\Framework\MockObject\MockObject $logicalTypeResolver;

    protected function setUp(): void
    {
        $this->logicalTypeResolver = $this->createMock(LogicalTypeResolver::class);
        $this->schemaHelper = new SchemaHelper($this->logicalTypeResolver);
    }

    public function testParseSchemaWithValidJsonString(): void
    {
        $schema = '{"type": "string"}';
        $result = $this->schemaHelper->parseSchema($schema);

        $this->assertEquals(['type' => 'string'], $result);
    }

    public function testParseSchemaWithValidJsonArray(): void
    {
        $schema = '["string", "int"]';
        $result = $this->schemaHelper->parseSchema($schema);

        $this->assertEquals(['string', 'int'], $result);
    }

    public function testParseSchemaWithInvalidJson(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('Unable to parse AVRO schema as JSON');

        $this->schemaHelper->parseSchema('{"type": "string"');
    }

    public function testParseSchemaWithNonStringOrArray(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO schema must be a string or an array');

        $this->schemaHelper->parseSchema('123');
    }

    public function testNormalizeSchemaWithString(): void
    {
        $result = $this->schemaHelper->normalizeSchema('string');

        $this->assertEquals(['type' => 'string'], $result);
    }

    public function testNormalizeSchemaWithArrayIndexed(): void
    {
        $result = $this->schemaHelper->normalizeSchema(['string', 'int']);

        $this->assertEquals([
            'type' => 'union',
            'branches' => ['string', 'int'],
        ], $result);
    }

    public function testNormalizeSchemaWithMissingType(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO schema must be a string or an array with a "type" key');

        $this->schemaHelper->normalizeSchema(['name' => 'test']);
    }

    public function testNormalizeSchemaWithNonStringType(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO schema must be a string or an array with a "type" key');

        $this->schemaHelper->normalizeSchema(['type' => 123]);
    }

    public function testNormalizeSchemaWithUnsupportedType(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage("AVRO schema type 'unsupported' is not supported");

        $this->schemaHelper->normalizeSchema(['type' => 'unsupported']);
    }

    public function testNormalizeSchemaWithPrimitiveTypes(): void
    {
        $primitiveTypes = ['int', 'long', 'float', 'double', 'string', 'bytes', 'boolean', 'null'];

        foreach ($primitiveTypes as $type) {
            $result = $this->schemaHelper->normalizeSchema(['type' => $type]);
            $this->assertEquals(['type' => $type], $result);
        }
    }

    public function testValidateRecordSchemaWithValidSchema(): void
    {
        $schema = [
            'type' => 'record',
            'name' => 'test',
            'fields' => [
                ['name' => 'field1', 'type' => 'string'],
                ['name' => 'field2', 'type' => 'int'],
            ],
        ];

        $result = $this->schemaHelper->normalizeSchema($schema);

        $this->assertEquals($schema, $result);
    }

    public function testValidateRecordSchemaWithMissingFields(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO record schema is missing fields');

        $this->schemaHelper->normalizeSchema(['type' => 'record', 'name' => 'test']);
    }

    public function testValidateRecordSchemaWithNonArrayFields(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO record schema fields must be an array');

        $this->schemaHelper->normalizeSchema([
            'type' => 'record',
            'name' => 'test',
            'fields' => 'invalid',
        ]);
    }

    public function testValidateRecordSchemaWithInvalidField(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO record schema field must be an array');

        $this->schemaHelper->normalizeSchema([
            'type' => 'record',
            'name' => 'test',
            'fields' => ['invalid'],
        ]);
    }

    public function testValidateRecordSchemaWithMissingFieldName(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO record schema field is missing name');

        $this->schemaHelper->normalizeSchema([
            'type' => 'record',
            'name' => 'test',
            'fields' => [['type' => 'string']],
        ]);
    }

    public function testValidateRecordSchemaWithNonStringFieldName(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO record schema field is missing name');

        $this->schemaHelper->normalizeSchema([
            'type' => 'record',
            'name' => 'test',
            'fields' => [['name' => 123, 'type' => 'string']],
        ]);
    }

    public function testValidateRecordSchemaWithMissingFieldType(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO record schema field is missing type');

        $this->schemaHelper->normalizeSchema([
            'type' => 'record',
            'name' => 'test',
            'fields' => [['name' => 'field1']],
        ]);
    }

    public function testValidateRecordSchemaWithInvalidFieldType(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO record schema field type must be a string or an array');

        $this->schemaHelper->normalizeSchema([
            'type' => 'record',
            'name' => 'test',
            'fields' => [['name' => 'field1', 'type' => 123]],
        ]);
    }

    public function testValidateEnumSchemaWithValidSchema(): void
    {
        $schema = [
            'type' => 'enum',
            'name' => 'test',
            'symbols' => ['A', 'B', 'C'],
        ];

        $result = $this->schemaHelper->normalizeSchema($schema);

        $this->assertEquals($schema, $result);
    }

    public function testValidateEnumSchemaWithMissingSymbols(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO enum schema is missing symbols');

        $this->schemaHelper->normalizeSchema(['type' => 'enum', 'name' => 'test']);
    }

    public function testValidateEnumSchemaWithNonArraySymbols(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO enum schema is missing symbols');

        $this->schemaHelper->normalizeSchema([
            'type' => 'enum',
            'name' => 'test',
            'symbols' => 'invalid',
        ]);
    }

    public function testValidateEnumSchemaWithEmptySymbols(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO enum schema symbols cannot be empty');

        $this->schemaHelper->normalizeSchema([
            'type' => 'enum',
            'name' => 'test',
            'symbols' => [],
        ]);
    }

    public function testValidateEnumSchemaWithInvalidSymbol(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO enum schema symbols must be strings');

        $this->schemaHelper->normalizeSchema([
            'type' => 'enum',
            'name' => 'test',
            'symbols' => [123],
        ]);
    }

    public function testValidateEnumSchemaWithInvalidSymbolFormat(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage("AVRO enum symbol '123' is not a valid identifier");

        $this->schemaHelper->normalizeSchema([
            'type' => 'enum',
            'name' => 'test',
            'symbols' => ['123'],
        ]);
    }

    public function testValidateEnumSchemaWithInvalidDefault(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO enum schema symbols must be strings');

        $this->schemaHelper->normalizeSchema([
            'type' => 'enum',
            'name' => 'test',
            'symbols' => ['A', 'B'],
            'default' => 123,
        ]);
    }

    public function testValidateArraySchemaWithValidSchema(): void
    {
        $schema = [
            'type' => 'array',
            'items' => 'string',
        ];

        $result = $this->schemaHelper->normalizeSchema($schema);

        $this->assertEquals($schema, $result);
    }

    public function testValidateArraySchemaWithMissingItems(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO array schema is missing items');

        $this->schemaHelper->normalizeSchema(['type' => 'array']);
    }

    public function testValidateArraySchemaWithInvalidItems(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO array schema items must be an array or a string');

        $this->schemaHelper->normalizeSchema([
            'type' => 'array',
            'items' => 123,
        ]);
    }

    public function testValidateMapSchemaWithValidSchema(): void
    {
        $schema = [
            'type' => 'map',
            'values' => 'string',
        ];

        $result = $this->schemaHelper->normalizeSchema($schema);

        $this->assertEquals($schema, $result);
    }

    public function testValidateMapSchemaWithMissingValues(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO map schema is missing values');

        $this->schemaHelper->normalizeSchema(['type' => 'map']);
    }

    public function testValidateMapSchemaWithInvalidValues(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO map schema values must be an array or a string');

        $this->schemaHelper->normalizeSchema([
            'type' => 'map',
            'values' => 123,
        ]);
    }

    public function testValidateFixedSchemaWithValidSchema(): void
    {
        $schema = [
            'type' => 'fixed',
            'name' => 'test',
            'size' => 16,
        ];

        $result = $this->schemaHelper->normalizeSchema($schema);

        $this->assertEquals($schema, $result);
    }

    public function testValidateFixedSchemaWithMissingSize(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO fixed schema is missing or has invalid size');

        $this->schemaHelper->normalizeSchema(['type' => 'fixed', 'name' => 'test']);
    }

    public function testValidateFixedSchemaWithInvalidSize(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO fixed schema is missing or has invalid size');

        $this->schemaHelper->normalizeSchema([
            'type' => 'fixed',
            'name' => 'test',
            'size' => -1,
        ]);
    }

    public function testGetUnionBranchesWithValidBranches(): void
    {
        $result = $this->schemaHelper->normalizeSchema(['string', 'int']);

        $this->assertEquals([
            'type' => 'union',
            'branches' => ['string', 'int'],
        ], $result);
    }

    public function testGetUnionBranchesWithInvalidBranch(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO union branches must be strings or arrays');

        $this->schemaHelper->normalizeSchema([123]);
    }

    public function testGetLogicalTypeWithNoLogicalType(): void
    {
        $schema = ['type' => 'string'];

        $result = $this->schemaHelper->getLogicalType($schema);

        $this->assertNull($result);
    }

    public function testGetLogicalTypeWithNonStringLogicalType(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('AVRO logical type must be a string');

        $this->schemaHelper->getLogicalType(['type' => 'string', 'logicalType' => 123]);
    }

    public function testGetLogicalTypeWithUnresolvedLogicalType(): void
    {
        $this->logicalTypeResolver->method('resolve')->with('date')->willReturn(null);

        $result = $this->schemaHelper->getLogicalType(['type' => 'string', 'logicalType' => 'date']);

        $this->assertNull($result);
    }

    public function testGetLogicalTypeWithResolvedLogicalType(): void
    {
        $logicalType = $this->createMock(LogicalTypeInterface::class);
        $factory = $this->createMock(LogicalTypeFactoryInterface::class);
        $factory->method('create')->willReturn($logicalType);

        $this->logicalTypeResolver->method('resolve')->with('date')->willReturn($factory);

        $schema = ['type' => 'string', 'logicalType' => 'date'];
        $result = $this->schemaHelper->getLogicalType($schema);

        $this->assertSame($logicalType, $result);
    }
}
