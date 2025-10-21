<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use Auxmoney\Avro\IO\WritableStringBuffer;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Serialization\BooleanWriter;
use Auxmoney\Avro\Serialization\NullWriter;
use Auxmoney\Avro\Serialization\PropertyWriter;
use Auxmoney\Avro\Serialization\RecordWriter;
use Auxmoney\Avro\Serialization\UnionWriter;
use Auxmoney\Avro\Serialization\ValidationContext;
use PHPUnit\Framework\TestCase;

class RecordWriterTest extends TestCase
{
    private WritableStringBuffer $stream;

    // Simple property writers for testing RecordWriter logic
    private PropertyWriter $field1PropertyWriter;
    private PropertyWriter $field2PropertyWriter;
    private PropertyWriter $field2NullablePropertyWriter;

    protected function setUp(): void
    {
        // Create simple BooleanWriter for testing RecordWriter logic
        $boolWriter = new BooleanWriter();

        // Create nullable boolean writer (union of null and boolean)
        $nullWriter = new NullWriter();
        $encoder = new BinaryEncoder();
        $nullableBoolWriter = new UnionWriter([$nullWriter, $boolWriter], $encoder);

        // Create simplified property writers using BooleanWriter
        $this->field1PropertyWriter = new PropertyWriter($boolWriter, 'field1');
        $this->field2PropertyWriter = new PropertyWriter($boolWriter, 'field2');
        $this->field2NullablePropertyWriter = new PropertyWriter($nullableBoolWriter, 'field2');

        $this->stream = new WritableStringBuffer();
    }

    public function testWriteWithArray(): void
    {
        $data = ['field1' => true, 'field2' => false];

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains the correct boolean values
        $written = (string) $this->stream;
        $this->assertSame(chr(1) . chr(0), $written); // field1=true, field2=false
    }

    public function testWriteWithObject(): void
    {
        $data = new class() {
            public bool $field1 = true;
            public bool $field2 = false;
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains the correct boolean values
        $written = (string) $this->stream;
        $this->assertSame(chr(1) . chr(0), $written); // field1=true, field2=false
    }

    public function testValidateWithValidArray(): void
    {
        $data = ['field1' => true, 'field2' => false];

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithValidObject(): void
    {
        $data = new class() {
            public bool $field1 = true;
            public bool $field2 = false;
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidType(): void
    {
        $data = 'invalid';

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        // Should return false as string is not a valid array or object for records
        $this->assertFalse($result);
    }

    public function testValidateWithInvalidTypeAndContext(): void
    {
        $data = 'invalid';
        $context = new ValidationContext();

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data, $context);

        // Should return false as string is not a valid array or object for records
        $this->assertFalse($result);

        // Check that ValidationContext captured the error
        $errors = $context->getContextErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('expected array or object, got string', $errors[0]);
    }

    public function testValidateWithMissingRequiredField(): void
    {
        $data = ['field1' => true]; // missing field2

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        // BooleanWriter will reject null, so validation should fail
        $this->assertFalse($result);
    }

    public function testValidateWithInvalidField(): void
    {
        $data = ['field1' => true, 'field2' => 'invalid']; // field2 has invalid type for boolean

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        // BooleanWriter will reject string, so validation should fail
        $this->assertFalse($result);
    }

    public function testValidateWithContextAndInvalidField(): void
    {
        $data = ['field1' => 'invalid', 'field2' => true]; // field1 has invalid type for boolean
        $context = new ValidationContext();

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data, $context);

        // BooleanWriter will reject string for ffield1, so validation should fail
        $this->assertFalse($result);

        // Check that ValidationContext captured the field validation error
        $errors = $context->getContextErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('field1: expected boolean, got string', $errors[0]);
    }

    // Object with Getter Methods Tests

    public function testWriteWithObjectWithGetter(): void
    {
        $data = new class() {
            // field1 has a direct property, field2 uses getter
            public bool $field1 = true;

            public function getField2(): bool
            {
                return false;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains the correct boolean values
        $written = (string) $this->stream;
        $this->assertSame(chr(1) . chr(0), $written); // field1=true, field2=false (from getter)
    }

    public function testValidateWithObjectWithGetter(): void
    {
        $data = new class() {
            // field1 has a direct property, field2 uses getter
            public bool $field1 = true;

            public function getField2(): bool
            {
                return false;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithObjectWithGetterAndContext(): void
    {
        $data = new class() {
            // field1 has a direct property, field2 uses getter
            public bool $field1 = true;

            public function getField2(): bool
            {
                return false;
            }
        };

        $context = new ValidationContext();

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data, $context);

        $this->assertTrue($result);

        // Check that no validation errors occurred
        $errors = $context->getContextErrors();
        $this->assertEmpty($errors);
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

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains the correct boolean values
        $written = (string) $this->stream;
        $this->assertSame(chr(1) . chr(0), $written); // field1=true, field2=false (from isser)
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

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

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

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains the correct boolean values
        $written = (string) $this->stream;
        $this->assertSame(chr(1) . chr(0), $written); // field1=true, field2=false
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

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Mixed Access Methods Tests

    public function testWriteWithObjectWithMixedAccessMethods(): void
    {
        $data = new class() {
            public bool $field1 = true; // direct property

            public function getField2(): bool // getter method
            {
                return false;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains the correct boolean values
        $written = (string) $this->stream;
        $this->assertSame(chr(1) . chr(0), $written); // field1=true, field2=false
    }

    public function testValidateWithObjectWithMixedAccessMethods(): void
    {
        $data = new class() {
            public bool $field1 = true; // direct property

            public function getField2(): bool // getter method
            {
                return false;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Priority Tests (Direct property takes precedence over methods)

    public function testWriteWithObjectWithDirectPropertyAndGetter(): void
    {
        $data = new class() {
            public bool $field1 = true;

            public function getField1(): bool
            {
                return false; // This should NOT be used - direct property takes precedence
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains true value (from property, not from getter)
        $written = (string) $this->stream;
        $this->assertSame(chr(1), $written); // should use direct property = true
    }

    public function testValidateWithObjectWithDirectPropertyAndGetter(): void
    {
        $data = new class() {
            public bool $field1 = true;

            public function getField1(): bool
            {
                return false; // This should NOT be used - direct property takes precedence
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Missing Field Tests with Access Methods

    public function testValidateWithObjectMissingFieldWithGetter(): void
    {
        $data = new class() {
            public bool $field1 = true;

            // field2 is missing as property, but has a getter
            public function getField2(): bool
            {
                return false;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithObjectMissingFieldWithIsser(): void
    {
        $data = new class() {
            public bool $field1 = true;

            // field2 is missing as property, but has an isser
            public function isField2(): bool
            {
                return false;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithObjectMissingFieldWithHasser(): void
    {
        $data = new class() {
            public bool $field1 = true;

            // field2 is missing as property, but has a hasser
            public function hasField2(): bool
            {
                return false;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Error Cases with Access Methods

    public function testValidateWithObjectMissingFieldNoAccessMethod(): void
    {
        $data = new class() {
            public bool $field1 = true;
            // field2 is missing and has no access method
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        // BooleanWriter will reject null for field2, so validation should fail
        $this->assertFalse($result);
    }

    public function testValidateWithObjectMissingFieldNoAccessMethodWithContext(): void
    {
        $data = new class() {
            public bool $field1 = true;
            // field2 is missing and has no access method
        };

        $context = new ValidationContext();

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data, $context);

        // BooleanWriter will reject null for field2, so validation should fail
        $this->assertFalse($result);

        // Check that ValidationContext captured the missing field error
        $errors = $context->getContextErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('field2: expected boolean, got NULL', $errors[0]);
    }

    // Complex Object Tests

    public function testWriteWithComplexObjectWithAccessMethods(): void
    {
        $data = new class() {
            public bool $field1 = true; // Direct property

            public function getField2(): bool
            {
                return false;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains the correct boolean values
        $written = (string) $this->stream;
        $this->assertSame(chr(1) . chr(0), $written); // field1=true, field2=false
    }

    public function testValidateWithComplexObjectWithAccessMethods(): void
    {
        $data = new class() {
            public bool $field1 = true; // Direct property

            public function getField2(): bool
            {
                return false;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Bug Fix Verification Tests

    public function testWriteWithObjectWithPrivatePropertyAndGetter(): void
    {
        $data = new class() {
            private bool $field1 = true;

            public function getField1(): bool
            {
                return $this->field1;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains true value (from getter since property is private)
        $written = (string) $this->stream;
        $this->assertSame(chr(1), $written); // should use getter = true
    }

    public function testValidateWithObjectWithPrivatePropertyAndGetter(): void
    {
        $data = new class() {
            private bool $field1 = true;

            public function getField1(): bool
            {
                return $this->field1;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter]);

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

        $recordWriter = new RecordWriter([$this->field1PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        $written = (string) $this->stream;
        $this->assertSame(chr(1), $written); // BooleanWriter encodes true as chr(1)
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

        $recordWriter = new RecordWriter([$this->field1PropertyWriter]);

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

        $recordWriter = new RecordWriter([$this->field1PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        $written = (string) $this->stream;
        $this->assertSame(chr(0), $written); // BooleanWriter encodes false as chr(0)
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

        $recordWriter = new RecordWriter([$this->field1PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testWriteWithObjectWithMixedPublicAndPrivateProperties(): void
    {
        $data = new class() {
            public bool $field1 = true; // public property
            private bool $field2 = false; // private property

            public function getField2(): bool
            {
                return $this->field2;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains the correct boolean values
        $written = (string) $this->stream;
        $this->assertSame(chr(1) . chr(0), $written); // field1=true, field2=false
    }

    public function testValidateWithObjectWithMixedPublicAndPrivateProperties(): void
    {
        $data = new class() {
            public bool $field1 = true; // public property
            private bool $field2 = false; // private property

            public function getField2(): bool
            {
                return $this->field2;
            }
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2PropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    // Nullable Property Tests

    public function testWriteWithObjectWithNullablePropertySetToNull(): void
    {
        $data = new class() {
            public bool $field1 = true;
            public ?bool $field2 = null; // nullable property set to null
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2NullablePropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains the correct values
        $written = (string) $this->stream;
        // UnionWriter writes the branch index (0 for null) as a long, then the null value
        // BinaryEncoder.writeLong(0) writes 0x00, NullWriter writes nothing for null
        $this->assertSame(chr(1) . chr(0), $written); // field1=true (chr(1)), field2=null (branch 0: chr(0))
    }

    public function testValidateWithObjectWithNullablePropertySetToNull(): void
    {
        $data = new class() {
            public bool $field1 = true;
            public ?bool $field2 = null; // nullable property set to null
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2NullablePropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithObjectWithNullablePropertySetToNullWithContext(): void
    {
        $data = new class() {
            public bool $field1 = true;
            public ?bool $field2 = null; // nullable property set to null
        };

        $context = new ValidationContext();

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2NullablePropertyWriter]);

        $result = $recordWriter->validate($data, $context);

        $this->assertTrue($result);

        // Check that no validation errors occurred (nullable field set to null is valid)
        $errors = $context->getContextErrors();
        $this->assertEmpty($errors);
    }

    public function testWriteWithObjectWithNullablePropertySetToValue(): void
    {
        $data = new class() {
            public bool $field1 = true;
            public ?bool $field2 = false; // nullable property set to bool value
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2NullablePropertyWriter]);

        $recordWriter->write($data, $this->stream);

        // Assert that the buffer contains the correct values
        $written = (string) $this->stream;
        // UnionWriter writes the branch index (1 for boolean) as a varint long, then the boolean value
        // BinaryEncoder.writeLong(1) writes 0x02 (varint encoding: 1 << 1 = 2), BooleanWriter writes 0x00 for false
        $this->assertSame(chr(1) . chr(2) . chr(0), $written); // field1=true (chr(1)), field2=false (branch 1: chr(2), value: chr(0))
    }

    public function testValidateWithObjectWithNullablePropertySetToValue(): void
    {
        $data = new class() {
            public bool $field1 = true;
            public ?bool $field2 = false; // nullable property set to bool value
        };

        $recordWriter = new RecordWriter([$this->field1PropertyWriter, $this->field2NullablePropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result);
    }

    /**
     * This test demonstrates that nullable properties set to null are handled correctly.
     * Previously, isset() would return false for null values, causing issues.
     * With the fix, null values are properly passed to the PropertyWriter for validation.
     */
    public function testValidateWithObjectWithNullablePropertySetToNullShouldNotTreatAsMissingField(): void
    {
        $data = new class() {
            public ?bool $field2 = null; // This exists but is null
        };

        $recordWriter = new RecordWriter([$this->field2NullablePropertyWriter]);

        $result = $recordWriter->validate($data);

        $this->assertTrue($result, 'Nullable property set to null should not be treated as missing field');
    }
}
