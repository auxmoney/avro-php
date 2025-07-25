<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Serialization\FloatWriter;
use Auxmoney\Avro\Serialization\ValidationContext;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class FloatWriterTest extends TestCase
{
    private FloatWriter $writer;
    private BinaryEncoder&\PHPUnit\Framework\MockObject\MockObject $encoder;
    private WritableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->encoder = $this->createMock(BinaryEncoder::class);
        $this->stream = $this->createMock(WritableStreamInterface::class);
        $this->writer = new FloatWriter($this->encoder);
    }

    public function testValidateWithValidFloat(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate(3.14, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithValidInteger(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate(42, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithZeroFloat(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate(0.0, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithZeroInteger(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate(0, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithNegativeFloat(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate(-123.45, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithSpecialFloatValues(): void
    {
        $context = new ValidationContext();

        $this->assertTrue($this->writer->validate(INF, $context));
        $this->assertTrue($this->writer->validate(-INF, $context));
        $this->assertTrue($this->writer->validate(NAN, $context));
        $this->assertEmpty($context->getContextErrors());
    }

    #[DataProvider('invalidDataProvider')]
    public function testValidateWithInvalidData(mixed $invalidData, string $expectedType): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate($invalidData, $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("expected int or float, got {$expectedType}", $errors[0]);
    }

    public function testValidateWithoutContext(): void
    {
        $result = $this->writer->validate(3.14);

        $this->assertTrue($result);
    }

    public function testValidateInvalidDataWithoutContext(): void
    {
        $result = $this->writer->validate('3.14');

        $this->assertFalse($result);
    }

    public function testWriteWithFloat(): void
    {
        $value = 3.14;

        $this->encoder->expects($this->once())
            ->method('encodeFloat')
            ->with($value)
            ->willReturn("\x40\x48\xf5\xc3");

        $this->stream->expects($this->once())
            ->method('write')
            ->with("\x40\x48\xf5\xc3");

        $this->writer->write($value, $this->stream);
    }

    public function testWriteWithInteger(): void
    {
        $value = 42;

        $this->encoder->expects($this->once())
            ->method('encodeFloat')
            ->with($value)
            ->willReturn("\x42\x28\x00\x00");

        $this->stream->expects($this->once())
            ->method('write')
            ->with("\x42\x28\x00\x00");

        $this->writer->write($value, $this->stream);
    }

    public function testWriteWithZero(): void
    {
        $value = 0.0;

        $this->encoder->expects($this->once())
            ->method('encodeFloat')
            ->with($value)
            ->willReturn("\x00\x00\x00\x00");

        $this->stream->expects($this->once())
            ->method('write')
            ->with("\x00\x00\x00\x00");

        $this->writer->write($value, $this->stream);
    }

    public function testWriteWithNegativeFloat(): void
    {
        $value = -123.45;

        $this->encoder->expects($this->once())
            ->method('encodeFloat')
            ->with($value)
            ->willReturn("\xc2\xf6\xe6\x66");

        $this->stream->expects($this->once())
            ->method('write')
            ->with("\xc2\xf6\xe6\x66");

        $this->writer->write($value, $this->stream);
    }

    public function testWriteWithSpecialValues(): void
    {
        // Test INF
        $this->encoder->expects($this->exactly(3))
            ->method('encodeFloat')
            ->willReturnOnConsecutiveCalls(
                "\x7f\x80\x00\x00", // INF
                "\xff\x80\x00\x00", // -INF
                "\x7f\xc0\x00\x00",  // NAN
            );

        $this->stream->expects($this->exactly(3))
            ->method('write');

        $this->writer->write(INF, $this->stream);
        $this->writer->write(-INF, $this->stream);
        $this->writer->write(NAN, $this->stream);
    }

    public function testValidateWithNullContext(): void
    {
        $result = $this->writer->validate(3.14, null);

        $this->assertTrue($result);
    }

    public function testValidateInvalidDataWithNullContext(): void
    {
        $result = $this->writer->validate('3.14', null);

        $this->assertFalse($result);
    }

    public static function invalidDataProvider(): Generator
    {
        yield 'string_number' => ['3.14', 'string'];
        yield 'string_text' => ['not a number', 'string'];
        yield 'boolean_true' => [true, 'boolean'];
        yield 'boolean_false' => [false, 'boolean'];
        yield 'null' => [null, 'NULL'];
        yield 'array' => [[], 'array'];
        yield 'object' => [new stdClass(), 'object'];
        yield 'resource' => [fopen('php://memory', 'r'), 'resource'];
    }
}
