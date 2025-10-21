<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Support;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Serialization\ValidationContext;
use Auxmoney\Avro\Support\DefaultValueConverter;
use Auxmoney\Avro\Support\LogicalTypeResolver;
use Auxmoney\Avro\Support\SchemaHelper;
use PHPUnit\Framework\TestCase;

class DefaultValueConverterTest extends TestCase
{
    private DefaultValueConverter $converter;
    private ValidationContext $context;

    protected function setUp(): void
    {
        // Create real LogicalTypeResolver with empty factories for testing
        $logicalTypeResolver = new LogicalTypeResolver([]);
        $this->converter = new DefaultValueConverter(new SchemaHelper($logicalTypeResolver));
        $this->context = new ValidationContext();
    }

    public function testConvertNullDefaultValueWithValidNull(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(null, 'null', $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertNull($convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertNullDefaultValueWithInvalidValue(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('not null', 'null', $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('Null default value must be null', $this->context->getContextErrors());
    }

    public function testConvertBooleanDefaultValueWithValidBoolean(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(true, 'boolean', $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertTrue($convertedValue);
        $this->assertEmpty($this->context->getContextErrors());

        // Test with false
        $this->context = new ValidationContext(); // Reset context
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(false, 'boolean', $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertFalse($convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertBooleanDefaultValueWithInvalidValue(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('not boolean', 'boolean', $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('Boolean default value must be a boolean', $this->context->getContextErrors());
    }

    public function testConvertIntegerDefaultValueWithValidInteger(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(42, 'int', $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame(42, $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertIntegerDefaultValueWithInvalidValue(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('not int', 'int', $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('Integer default value must be an integer', $this->context->getContextErrors());
    }

    public function testConvertLongDefaultValueWithValidInteger(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(9223372036854775807, 'long', $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame(9223372036854775807, $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertFloatDefaultValueWithValidFloat(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(3.14, 'float', $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame(3.14, $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertFloatDefaultValueWithValidInteger(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(42, 'float', $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame(42.0, $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertFloatDefaultValueWithInvalidValue(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('not float', 'float', $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('Float default value must be a float or int', $this->context->getContextErrors());
    }

    public function testConvertDoubleDefaultValueWithValidFloat(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(3.14159, 'double', $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame(3.14159, $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertStringDefaultValueWithValidString(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('hello', 'string', $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame('hello', $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertStringDefaultValueWithInvalidValue(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(123, 'string', $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('String default value must be a string', $this->context->getContextErrors());
    }

    public function testConvertBytesDefaultValueWithValidString(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('hello', 'bytes', $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame('hello', $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertBytesDefaultValueWithValidUnicodeString(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue("\u{FF}", 'bytes', $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame("\xFF", $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertBytesDefaultValueWithInvalidByte(): void
    {
        $convertedValue = null;
        // Using a character with Unicode code point > 255
        $result = $this->converter->convertDefaultValue("\u{0100}", 'bytes', $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('Invalid byte value at index 0', $this->context->getContextErrors());
    }

    public function testConvertBytesDefaultValueWithInvalidValue(): void
    {
        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(123, 'bytes', $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('Bytes default value must be a string', $this->context->getContextErrors());
    }

    public function testConvertEnumDefaultValueWithValidSymbol(): void
    {
        $schema = [
            'type' => 'enum',
            'symbols' => ['RED', 'GREEN', 'BLUE'],
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('RED', $schema, $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame('RED', $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertEnumDefaultValueWithInvalidSymbol(): void
    {
        $schema = [
            'type' => 'enum',
            'symbols' => ['RED', 'GREEN', 'BLUE'],
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('YELLOW', $schema, $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains("Enum default value 'YELLOW' is not in symbols list", $this->context->getContextErrors());
    }

    public function testConvertEnumDefaultValueWithInvalidValue(): void
    {
        $schema = [
            'type' => 'enum',
            'symbols' => ['RED', 'GREEN', 'BLUE'],
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(123, $schema, $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('Enum default value must be a string', $this->context->getContextErrors());
    }

    public function testConvertFixedDefaultValueWithValidSize(): void
    {
        $schema = [
            'type' => 'fixed',
            'size' => 4,
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('test', $schema, $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame('test', $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertFixedDefaultValueWithInvalidSize(): void
    {
        $schema = [
            'type' => 'fixed',
            'size' => 4,
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('toolong', $schema, $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('Fixed default value length does not match schema size', $this->context->getContextErrors());
    }

    public function testConvertArrayDefaultValueWithValidArray(): void
    {
        $schema = [
            'type' => 'array',
            'items' => 'string',
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(['hello', 'world'], $schema, $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame(['hello', 'world'], $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertArrayDefaultValueWithInvalidArray(): void
    {
        $schema = [
            'type' => 'array',
            'items' => 'string',
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('not array', $schema, $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('Array default value must be an array', $this->context->getContextErrors());
    }

    public function testConvertMapDefaultValueWithValidMap(): void
    {
        $schema = [
            'type' => 'map',
            'values' => 'int',
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(['a' => 1, 'b' => 2], $schema, $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame(['a' => 1, 'b' => 2], $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertMapDefaultValueWithInvalidValue(): void
    {
        $schema = [
            'type' => 'map',
            'values' => 'int',
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('not array', $schema, $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('Map default value must be an array', $this->context->getContextErrors());
    }

    public function testConvertRecordDefaultValueWithValidRecord(): void
    {
        $schema = [
            'type' => 'record',
            'fields' => [
                ['name' => 'field1', 'type' => 'string'],
                ['name' => 'field2', 'type' => 'int', 'default' => 42],
            ],
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(['field1' => 'hello'], $schema, $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame(['field1' => 'hello', 'field2' => 42], $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertRecordDefaultValueWithMissingFieldAndNoDefault(): void
    {
        $schema = [
            'type' => 'record',
            'fields' => [
                ['name' => 'field1', 'type' => 'string'],
                ['name' => 'field2', 'type' => 'int'],
            ],
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(['field1' => 'hello'], $schema, $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('field2: Missing default value', $this->context->getContextErrors());
    }

    public function testConvertRecordDefaultValueWithInvalidValue(): void
    {
        $schema = [
            'type' => 'record',
            'fields' => [],
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('not array', $schema, $convertedValue, $this->context);

        $this->assertFalse($result);
        $this->assertContains('Record default value must be an array', $this->context->getContextErrors());
    }

    public function testConvertUnionDefaultValueWithFirstMatchingBranch(): void
    {
        $schema = ['int', 'string']; // Union schema as array

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue(42, $schema, $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame(42, $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertUnionDefaultValueWithSecondMatchingBranch(): void
    {
        $schema = ['int', 'string']; // Union schema as array

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('hello', $schema, $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame('hello', $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertUnionDefaultValueWithNoMatchingBranch(): void
    {
        $schema = ['int', 'boolean']; // Union schema as array

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue('hello', $schema, $convertedValue, $this->context);

        $this->assertFalse($result);
        // Union errors should be present since no branch matched
        $this->assertNotEmpty($this->context->getContextErrors());
    }

    public function testConvertDefaultValueWithLogicalType(): void
    {
        // For this test, we need to use a mock logical type since we don't have real ones registered
        $logicalType = $this->createMock(LogicalTypeInterface::class);
        $logicalType->method('denormalize')
            ->with(12345)
            ->willReturn(54321);

        // Create a schema helper with a logical type factory
        $logicalTypeFactory = $this->createMock(\Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface::class);
        $logicalTypeFactory->method('getName')->willReturn('test');
        $logicalTypeFactory->method('create')->willReturn($logicalType);

        $logicalTypeResolver = new LogicalTypeResolver([$logicalTypeFactory]);
        $schemaHelper = new SchemaHelper($logicalTypeResolver);
        $converter = new DefaultValueConverter($schemaHelper);

        $schema = ['type' => 'long', 'logicalType' => 'test'];
        $convertedValue = null;
        $result = $converter->convertDefaultValue(12345, $schema, $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertSame(54321, $convertedValue);
        $this->assertEmpty($this->context->getContextErrors());
    }

    public function testConvertNestedRecordDefaultValue(): void
    {
        $schema = [
            'type' => 'record',
            'name' => 'OuterRecord',
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                [
                    'name' => 'address',
                    'type' => [
                        'type' => 'record',
                        'name' => 'AddressRecord',
                        'fields' => [
                            ['name' => 'street', 'type' => 'string'],
                            ['name' => 'city', 'type' => 'string'],
                            ['name' => 'zipCode', 'type' => 'string', 'default' => '00000'],
                        ],
                    ],
                ],
                ['name' => 'active', 'type' => 'boolean', 'default' => true],
            ],
        ];

        $defaultValue = [
            'id' => 'user123',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                // zipCode is missing but has a default value
            ],
            // active is missing but has a default value
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue($defaultValue, $schema, $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertEmpty($this->context->getContextErrors());

        $expectedResult = [
            'id' => 'user123',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zipCode' => '00000',
            ],
            'active' => true,
        ];

        $this->assertSame($expectedResult, $convertedValue);
    }

    public function testConvertNestedRecordDefaultValueWithMissingRequiredField(): void
    {
        $schema = [
            'type' => 'record',
            'name' => 'OuterRecord',
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
                [
                    'name' => 'address',
                    'type' => [
                        'type' => 'record',
                        'name' => 'AddressRecord',
                        'fields' => [
                            ['name' => 'street', 'type' => 'string'],
                            ['name' => 'city', 'type' => 'string'],
                            ['name' => 'zipCode', 'type' => 'string'], // No default value
                        ],
                    ],
                ],
            ],
        ];

        $defaultValue = [
            'id' => 'user123',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                // zipCode is missing and has no default value
            ],
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue($defaultValue, $schema, $convertedValue, $this->context);

        $this->assertFalse($result);
        $errors = $this->context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertContains('address.zipCode: Missing default value', $errors);
    }

    public function testConvertDeeplyNestedRecordDefaultValue(): void
    {
        $schema = [
            'type' => 'record',
            'name' => 'Company',
            'fields' => [
                ['name' => 'name', 'type' => 'string'],
                [
                    'name' => 'employee',
                    'type' => [
                        'type' => 'record',
                        'name' => 'Employee',
                        'fields' => [
                            ['name' => 'firstName', 'type' => 'string'],
                            ['name' => 'lastName', 'type' => 'string'],
                            [
                                'name' => 'address',
                                'type' => [
                                    'type' => 'record',
                                    'name' => 'Address',
                                    'fields' => [
                                        ['name' => 'street', 'type' => 'string'],
                                        ['name' => 'city', 'type' => 'string'],
                                        ['name' => 'country', 'type' => 'string', 'default' => 'USA'],
                                    ],
                                ],
                            ],
                            ['name' => 'department', 'type' => 'string', 'default' => 'Engineering'],
                        ],
                    ],
                ],
            ],
        ];

        $defaultValue = [
            'name' => 'ACME Corp',
            'employee' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'address' => [
                    'street' => '456 Oak Ave',
                    'city' => 'Boston',
                    // country defaults to 'USA'
                ],
                // department defaults to 'Engineering'
            ],
        ];

        $convertedValue = null;
        $result = $this->converter->convertDefaultValue($defaultValue, $schema, $convertedValue, $this->context);

        $this->assertTrue($result);
        $this->assertEmpty($this->context->getContextErrors());

        $expectedResult = [
            'name' => 'ACME Corp',
            'employee' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'address' => [
                    'street' => '456 Oak Ave',
                    'city' => 'Boston',
                    'country' => 'USA',
                ],
                'department' => 'Engineering',
            ],
        ];

        $this->assertSame($expectedResult, $convertedValue);
    }
}
