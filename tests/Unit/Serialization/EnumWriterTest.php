<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Serialization\EnumWriter;
use Auxmoney\Avro\Serialization\ValidationContext;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class EnumWriterTest extends TestCase
{
    private EnumWriter $writer;
    private BinaryEncoder&\PHPUnit\Framework\MockObject\MockObject $encoder;
    private WritableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    /** @var array<string> */
    private array $validSymbols;

    protected function setUp(): void
    {
        $this->encoder = $this->createMock(BinaryEncoder::class);
        $this->stream = $this->createMock(WritableStreamInterface::class);
        $this->validSymbols = ['RED', 'GREEN', 'BLUE'];
        $this->writer = new EnumWriter($this->validSymbols, $this->encoder);
    }

    public function testValidateWithValidString(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate('RED', $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithAllValidSymbols(): void
    {
        $context = new ValidationContext();

        foreach ($this->validSymbols as $symbol) {
            $result = $this->writer->validate($symbol, $context);
            $this->assertTrue($result, "Symbol '{$symbol}' should be valid");
        }

        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithValidBackedEnum(): void
    {
        $context = new ValidationContext();
        $backedEnum = TestColor::GREEN;

        $result = $this->writer->validate($backedEnum, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithInvalidString(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate('YELLOW', $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('invalid enum value: YELLOW', $errors[0]);
    }

    public function testValidateWithInvalidBackedEnum(): void
    {
        // Create a writer with enum symbols that don't include all TestColor values
        $restrictedWriter = new EnumWriter(['RED', 'BLUE'], $this->encoder);
        $context = new ValidationContext();
        $backedEnum = TestColor::GREEN; // GREEN is not in the restricted symbol list

        $result = $restrictedWriter->validate($backedEnum, $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('invalid enum value: GREEN', $errors[0]);
    }

    #[DataProvider('invalidDataProvider')]
    public function testValidateWithInvalidData(mixed $invalidData, string $expectedType): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate($invalidData, $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("expected string or BackedEnum, got {$expectedType}", $errors[0]);
    }

    public function testValidateWithoutContext(): void
    {
        $result = $this->writer->validate('RED');

        $this->assertTrue($result);
    }

    public function testValidateInvalidDataWithoutContext(): void
    {
        $result = $this->writer->validate('YELLOW');

        $this->assertFalse($result);
    }

    public function testWriteWithValidString(): void
    {
        $this->encoder->expects($this->once())
            ->method('writeLong')
            ->with($this->stream, 1); // GREEN is at index 1

        $this->writer->write('GREEN', $this->stream);
    }

    public function testWriteWithFirstSymbol(): void
    {
        $this->encoder->expects($this->once())
            ->method('writeLong')
            ->with($this->stream, 0); // RED is at index 0

        $this->writer->write('RED', $this->stream);
    }

    public function testWriteWithLastSymbol(): void
    {
        $this->encoder->expects($this->once())
            ->method('writeLong')
            ->with($this->stream, 2); // BLUE is at index 2

        $this->writer->write('BLUE', $this->stream);
    }

    public function testWriteWithValidBackedEnum(): void
    {
        $backedEnum = TestColor::GREEN;

        $this->encoder->expects($this->once())
            ->method('writeLong')
            ->with($this->stream, 1); // GREEN is at index 1

        $this->writer->write($backedEnum, $this->stream);
    }

    public function testValidateWithNullContext(): void
    {
        $result = $this->writer->validate('RED', null);

        $this->assertTrue($result);
    }

    public function testValidateInvalidDataWithNullContext(): void
    {
        $result = $this->writer->validate('YELLOW', null);

        $this->assertFalse($result);
    }

    public function testValidateEmptySymbolsList(): void
    {
        $emptyWriter = new EnumWriter([], $this->encoder);
        $context = new ValidationContext();

        $result = $emptyWriter->validate('ANY', $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('invalid enum value: ANY', $errors[0]);
    }

    public function testValidateWithCaseSensitivity(): void
    {
        $context = new ValidationContext();

        // Test that enum validation is case-sensitive
        $result = $this->writer->validate('red', $context); // lowercase

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('invalid enum value: red', $errors[0]);
    }

    public function testToRawValueWithString(): void
    {
        // This tests the private toRawValue method indirectly
        $context = new ValidationContext();

        $result = $this->writer->validate('RED', $context);

        $this->assertTrue($result);
    }

    public function testToRawValueWithBackedEnum(): void
    {
        // This tests the private toRawValue method indirectly
        $context = new ValidationContext();
        $backedEnum = TestColor::BLUE;

        $result = $this->writer->validate($backedEnum, $context);

        $this->assertTrue($result);
    }

    public static function invalidDataProvider(): Generator
    {
        yield 'integer' => [123, 'integer'];
        yield 'float' => [12.34, 'double'];
        yield 'boolean_true' => [true, 'boolean'];
        yield 'boolean_false' => [false, 'boolean'];
        yield 'null' => [null, 'NULL'];
        yield 'array' => [[], 'array'];
        yield 'object' => [new stdClass(), 'object'];
        yield 'resource' => [fopen('php://memory', 'r'), 'resource'];
    }
}

enum TestColor: string
{
    case RED = 'RED';
    case GREEN = 'GREEN';
    case BLUE = 'BLUE';
}
