<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Serialization\PropertyWriter;
use Auxmoney\Avro\Serialization\RecordWriter;
use PHPUnit\Framework\TestCase;
use stdClass;

class RecordWriterTest extends TestCase
{
    private RecordWriter $recordWriter;
    private WritableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', true, 'default_value');
        $this->stream = $this->createMock(WritableStreamInterface::class);
        $this->recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);
    }

    public function testWriteWithArray(): void
    {
        $data = ['field1' => 'value1', 'field2' => 'value2'];

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->exactly(2))
            ->method('write');

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $recordWriter->write($data, $this->stream);
    }

    public function testWriteWithObject(): void
    {
        $data = new stdClass();
        $data->field1 = 'value1';
        $data->field2 = 'value2';

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->exactly(2))
            ->method('write');

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithValidArray(): void
    {
        $data = ['field1' => 'value1', 'field2' => 'value2'];

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([['value1', null, true], ['value2', null, true]]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithValidObject(): void
    {
        $data = new stdClass();
        $data->field1 = 'value1';
        $data->field2 = 'value2';

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([['value1', null, true], ['value2', null, true]]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidType(): void
    {
        $data = 'not an array or object';

        $result = $this->recordWriter->validate($data);

        $this->assertFalse($result);
    }

    public function testValidateWithInvalidTypeAndContext(): void
    {
        $data = 'not an array or object';
        $context = $this->createMock(ValidationContextInterface::class);

        $context->expects($this->once())
            ->method('addError')
            ->with('expected array or object, got string');

        $result = $this->recordWriter->validate($data, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithMissingRequiredField(): void
    {
        $data = ['field1' => 'value1']; // missing field2

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->with('value1', null)
            ->willReturn(true);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null); // no default
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertFalse($result);
    }

    public function testValidateWithMissingFieldWithDefault(): void
    {
        $data = ['field1' => 'value1']; // missing field2

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([
                ['value1', null, true],
                ['default_value', null, true], // default value
            ]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', true, 'default_value');
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidField(): void
    {
        $data = ['field1' => 'value1', 'field2' => 'value2'];

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([
                ['value1', null, true],
                ['value2', null, false], // invalid
            ]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertFalse($result);
    }

    public function testValidateWithContextAndInvalidField(): void
    {
        $data = ['field1' => 'value1', 'field2' => 'value2'];
        $context = $this->createMock(ValidationContextInterface::class);

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([
                ['value1', $context, false], // invalid
                ['value2', $context, true],
            ]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);

        $context->expects($this->exactly(2))
            ->method('pushPath');

        $context->expects($this->exactly(2))
            ->method('popPath');

        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data, $context);

        $this->assertFalse($result);
    }

    // Object with Getter Methods Tests

    public function testWriteWithObjectWithGetter(): void
    {
        $data = new class() {
            // field1 has a direct property, field2 uses getter
            public string $field1 = 'value1';

            public function getField2(): string
            {
                return 'value2';
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->exactly(2))
            ->method('write');

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithObjectWithGetter(): void
    {
        $data = new class() {
            // field1 has a direct property, field2 uses getter
            public string $field1 = 'value1';

            public function getField2(): string
            {
                return 'value2';
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([['value1', null, true], ['value2', null, true]]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithObjectWithGetterAndContext(): void
    {
        $data = new class() {
            // field1 has a direct property, field2 uses getter
            public string $field1 = 'value1';

            public function getField2(): string
            {
                return 'value2';
            }
        };

        $context = $this->createMock(ValidationContextInterface::class);
        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([['value1', $context, true], ['value2', $context, true]]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);

        $context->expects($this->exactly(2))
            ->method('pushPath');

        $context->expects($this->exactly(2))
            ->method('popPath');

        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data, $context);

        $this->assertTrue($result);
    }

    // Object with Isser Methods Tests

    public function testWriteWithObjectWithIsser(): void
    {
        $data = new class() {
            // field1 has a direct property, field2 uses isser
            public bool $field1 = true;

            public function isField2(): bool
            {
                return false;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->exactly(2))
            ->method('write');

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithObjectWithIsser(): void
    {
        $data = new class() {
            // field1 has a direct property, field2 uses isser
            public bool $field1 = true;

            public function isField2(): bool
            {
                return false;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([[true, null, true], [false, null, true]]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Object with Hasser Methods Tests

    public function testWriteWithObjectWithHasser(): void
    {
        $data = new class() {
            // field1 has a direct property, field2 uses hasser
            public bool $field1 = true;

            public function hasField2(): bool
            {
                return false;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->exactly(2))
            ->method('write');

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithObjectWithHasser(): void
    {
        $data = new class() {
            // field1 has a direct property, field2 uses hasser
            public bool $field1 = true;

            public function hasField2(): bool
            {
                return false;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([[true, null, true], [false, null, true]]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Mixed Access Methods Tests

    public function testWriteWithObjectWithMixedAccessMethods(): void
    {
        $data = new class() {
            public string $field1 = 'value1'; // direct property

            public function getField2(): string
            {
                return 'value2';
            }

            public function isField3(): bool
            {
                return true;
            }

            public function hasField4(): bool
            {
                return false;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->exactly(4))
            ->method('write');

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $propertyWriter3 = new PropertyWriter($stringWriter, 'field3', false, null);
        $propertyWriter4 = new PropertyWriter($stringWriter, 'field4', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2, $propertyWriter3, $propertyWriter4]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithObjectWithMixedAccessMethods(): void
    {
        $data = new class() {
            public string $field1 = 'value1'; // direct property

            public function getField2(): string
            {
                return 'value2';
            }

            public function isField3(): bool
            {
                return true;
            }

            public function hasField4(): bool
            {
                return false;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([['value1', null, true], ['value2', null, true], [true, null, true], [false, null, true]]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $propertyWriter3 = new PropertyWriter($stringWriter, 'field3', false, null);
        $propertyWriter4 = new PropertyWriter($stringWriter, 'field4', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2, $propertyWriter3, $propertyWriter4]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Priority Tests (Direct property takes precedence over methods)

    public function testWriteWithObjectWithDirectPropertyAndGetter(): void
    {
        $data = new class() {
            public string $field1 = 'direct_value';

            public function getField1(): string
            {
                return 'getter_value';
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->once())
            ->method('write')
            ->with('direct_value'); // Should use direct property, not getter

        $propertyWriter = new PropertyWriter($stringWriter, 'field1', false, null);
        $recordWriter = new RecordWriter([$propertyWriter]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithObjectWithDirectPropertyAndGetter(): void
    {
        $data = new class() {
            public string $field1 = 'direct_value';

            public function getField1(): string
            {
                return 'getter_value';
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->once())
            ->method('validate')
            ->with('direct_value', null) // Should use direct property, not getter
            ->willReturn(true);

        $propertyWriter = new PropertyWriter($stringWriter, 'field1', false, null);
        $recordWriter = new RecordWriter([$propertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Missing Field Tests with Access Methods

    public function testValidateWithObjectMissingFieldWithGetter(): void
    {
        $data = new class() {
            public string $field1 = 'value1';

            // field2 is missing, but has a getter
            public function getField2(): string
            {
                return 'value2';
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([
                ['value1', null, true],
                ['value2', null, true], // Should use getter
            ]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithObjectMissingFieldWithIsser(): void
    {
        $data = new class() {
            public string $field1 = 'value1';

            // field2 is missing, but has an isser
            public function isField2(): bool
            {
                return true;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([
                ['value1', null, true],
                [true, null, true], // Should use isser
            ]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithObjectMissingFieldWithHasser(): void
    {
        $data = new class() {
            public string $field1 = 'value1';

            // field2 is missing, but has a hasser
            public function hasField2(): bool
            {
                return false;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([
                ['value1', null, true],
                [false, null, true], // Should use hasser
            ]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Error Cases with Access Methods

    public function testValidateWithObjectMissingFieldNoAccessMethod(): void
    {
        $data = new class() {
            public string $field1 = 'value1';
            // field2 is missing and has no access method
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->with('value1', null)
            ->willReturn(true);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null); // no default
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertFalse($result);
    }

    public function testValidateWithObjectMissingFieldNoAccessMethodWithContext(): void
    {
        $data = new class() {
            public string $field1 = 'value1';
            // field2 is missing and has no access method
        };

        $context = $this->createMock(ValidationContextInterface::class);
        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->with('value1', $context)
            ->willReturn(true);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null); // no default

        $context->expects($this->once())
            ->method('addError')
            ->with('missing required field field2');

        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data, $context);

        $this->assertFalse($result);
    }

    // Complex Object Tests

    public function testWriteWithComplexObjectWithAccessMethods(): void
    {
        $data = new class() {
            public string $name = 'John Doe';
            public int $age = 30; // Direct property

            public function isActive(): bool
            {
                return true;
            }

            public function hasTags(): bool
            {
                return true;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $intWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $boolWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $arrayWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);

        $stringWriter->expects($this->once())->method('write');
        $intWriter->expects($this->once())->method('write');
        $boolWriter->expects($this->once())->method('write');
        $arrayWriter->expects($this->once())->method('write');

        $propertyWriter1 = new PropertyWriter($stringWriter, 'name', false, null);
        $propertyWriter2 = new PropertyWriter($intWriter, 'age', false, null);
        $propertyWriter3 = new PropertyWriter($boolWriter, 'active', false, null);
        $propertyWriter4 = new PropertyWriter($arrayWriter, 'tags', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2, $propertyWriter3, $propertyWriter4]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithComplexObjectWithAccessMethods(): void
    {
        $data = new class() {
            public string $name = 'John Doe';
            public int $age = 30; // Direct property

            public function isActive(): bool
            {
                return true;
            }

            public function hasTags(): bool
            {
                return true;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $intWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $boolWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $arrayWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);

        $stringWriter->method('validate')->with('John Doe', null)->willReturn(true);
        $intWriter->method('validate')->with(30, null)->willReturn(true);
        $boolWriter->method('validate')->with(true, null)->willReturn(true);
        $arrayWriter->method('validate')->with(true, null)->willReturn(true); // hasTags returns bool

        $propertyWriter1 = new PropertyWriter($stringWriter, 'name', false, null);
        $propertyWriter2 = new PropertyWriter($intWriter, 'age', false, null);
        $propertyWriter3 = new PropertyWriter($boolWriter, 'active', false, null);
        $propertyWriter4 = new PropertyWriter($arrayWriter, 'tags', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2, $propertyWriter3, $propertyWriter4]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Bug Fix Verification Tests

    public function testWriteWithObjectWithPrivatePropertyAndGetter(): void
    {
        $data = new class() {
            private string $field1 = 'private_value';

            public function getField1(): string
            {
                return $this->field1;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->once())
            ->method('write')
            ->with('private_value'); // Should use getter since property is private

        $propertyWriter = new PropertyWriter($stringWriter, 'field1', false, null);
        $recordWriter = new RecordWriter([$propertyWriter]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithObjectWithPrivatePropertyAndGetter(): void
    {
        $data = new class() {
            private string $field1 = 'private_value';

            public function getField1(): string
            {
                return $this->field1;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->once())
            ->method('validate')
            ->with('private_value', null) // Should use getter since property is private
            ->willReturn(true);

        $propertyWriter = new PropertyWriter($stringWriter, 'field1', false, null);
        $recordWriter = new RecordWriter([$propertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testWriteWithObjectWithPrivatePropertyAndIsser(): void
    {
        $data = new class() {
            private bool $field1 = true;

            public function isField1(): bool
            {
                return $this->field1;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->once())
            ->method('write')
            ->with(true); // Should use isser since property is private

        $propertyWriter = new PropertyWriter($stringWriter, 'field1', false, null);
        $recordWriter = new RecordWriter([$propertyWriter]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithObjectWithPrivatePropertyAndIsser(): void
    {
        $data = new class() {
            private bool $field1 = true;

            public function isField1(): bool
            {
                return $this->field1;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->once())
            ->method('validate')
            ->with(true, null) // Should use isser since property is private
            ->willReturn(true);

        $propertyWriter = new PropertyWriter($stringWriter, 'field1', false, null);
        $recordWriter = new RecordWriter([$propertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testWriteWithObjectWithPrivatePropertyAndHasser(): void
    {
        $data = new class() {
            private bool $field1 = false;

            public function hasField1(): bool
            {
                return $this->field1;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->once())
            ->method('write')
            ->with(false); // Should use hasser since property is private

        $propertyWriter = new PropertyWriter($stringWriter, 'field1', false, null);
        $recordWriter = new RecordWriter([$propertyWriter]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithObjectWithPrivatePropertyAndHasser(): void
    {
        $data = new class() {
            private bool $field1 = false;

            public function hasField1(): bool
            {
                return $this->field1;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->once())
            ->method('validate')
            ->with(false, null) // Should use hasser since property is private
            ->willReturn(true);

        $propertyWriter = new PropertyWriter($stringWriter, 'field1', false, null);
        $recordWriter = new RecordWriter([$propertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testWriteWithObjectWithMixedPublicAndPrivateProperties(): void
    {
        $data = new class() {
            public string $field1 = 'public_value';
            private string $field2 = 'private_value';

            public function getField2(): string
            {
                return $this->field2;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->exactly(2))
            ->method('write');

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithObjectWithMixedPublicAndPrivateProperties(): void
    {
        $data = new class() {
            public string $field1 = 'public_value';
            private string $field2 = 'private_value';

            public function getField2(): string
            {
                return $this->field2;
            }
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([
                ['public_value', null, true], // Direct property access
                ['private_value', null, true], // Getter method access
            ]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', false, null);
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testWriteWithObjectMissingFieldWithDefault(): void
    {
        $data = new class() {
            public string $field1 = 'value1';
            // field2 is missing, but has a default value
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->expects($this->exactly(2))
            ->method('write');

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', true, 'default_value');
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $recordWriter->write($data, $this->stream);
    }

    public function testValidateWithObjectMissingFieldWithDefault(): void
    {
        $data = new class() {
            public string $field1 = 'value1';
            // field2 is missing, but has a default value
        };

        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([
                ['value1', null, true], // Direct property access
                ['default_value', null, true], // Default value
            ]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', true, 'default_value');
        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testWriteWithObjectMissingFieldWithDefaultAndContext(): void
    {
        $data = new class() {
            public string $field1 = 'value1';
            // field2 is missing, but has a default value
        };

        $context = $this->createMock(ValidationContextInterface::class);
        $stringWriter = $this->createMock(\Auxmoney\Avro\Contracts\WriterInterface::class);
        $stringWriter->method('validate')
            ->willReturnMap([
                ['value1', $context, true], // Direct property access
                ['default_value', $context, true], // Default value
            ]);

        $propertyWriter1 = new PropertyWriter($stringWriter, 'field1', false, null);
        $propertyWriter2 = new PropertyWriter($stringWriter, 'field2', true, 'default_value');

        $context->expects($this->exactly(2))
            ->method('pushPath');

        $context->expects($this->exactly(2))
            ->method('popPath');

        $recordWriter = new RecordWriter([$propertyWriter1, $propertyWriter2]);

        $result = $recordWriter->validate($data, $context);

        $this->assertTrue($result);
    }
}
