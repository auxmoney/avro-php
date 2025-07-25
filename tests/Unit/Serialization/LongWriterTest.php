<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Serialization\LongWriter;
use Auxmoney\Avro\Serialization\ValidationContext;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class LongWriterTest extends TestCase
{
    private LongWriter $writer;
    private BinaryEncoder&\PHPUnit\Framework\MockObject\MockObject $encoder;
    private WritableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->encoder = $this->createMock(BinaryEncoder::class);
        $this->stream = $this->createMock(WritableStreamInterface::class);
        $this->writer = new LongWriter($this->encoder);
    }

    public function testValidateWithValidInteger(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate(42, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithZero(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate(0, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithNegativeInteger(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate(-123, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithMaxInteger(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate(PHP_INT_MAX, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithMinInteger(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate(PHP_INT_MIN, $context);

        $this->assertTrue($result);
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
        $this->assertStringContainsString("expected int, got {$expectedType}", $errors[0]);
    }

    public function testValidateWithoutContext(): void
    {
        $result = $this->writer->validate(42);

        $this->assertTrue($result);
    }

    public function testValidateInvalidDataWithoutContext(): void
    {
        $result = $this->writer->validate('123');

        $this->assertFalse($result);
    }

    public function testWriteWithPositiveInteger(): void
    {
        $value = 123;

        $this->encoder->expects($this->once())
            ->method('writeLong')
            ->with($this->stream, $value);

        $this->writer->write($value, $this->stream);
    }

    public function testWriteWithNegativeInteger(): void
    {
        $value = -456;

        $this->encoder->expects($this->once())
            ->method('writeLong')
            ->with($this->stream, $value);

        $this->writer->write($value, $this->stream);
    }

    public function testWriteWithZero(): void
    {
        $value = 0;

        $this->encoder->expects($this->once())
            ->method('writeLong')
            ->with($this->stream, $value);

        $this->writer->write($value, $this->stream);
    }

    public function testWriteWithMaxInteger(): void
    {
        $value = PHP_INT_MAX;

        $this->encoder->expects($this->once())
            ->method('writeLong')
            ->with($this->stream, $value);

        $this->writer->write($value, $this->stream);
    }

    public function testWriteWithMinInteger(): void
    {
        $value = PHP_INT_MIN;

        $this->encoder->expects($this->once())
            ->method('writeLong')
            ->with($this->stream, $value);

        $this->writer->write($value, $this->stream);
    }

    public function testValidateWithNullContext(): void
    {
        $result = $this->writer->validate(42, null);

        $this->assertTrue($result);
    }

    public function testValidateInvalidDataWithNullContext(): void
    {
        $result = $this->writer->validate('123', null);

        $this->assertFalse($result);
    }

    public static function invalidDataProvider(): Generator
    {
        yield 'string_number' => ['123', 'string'];
        yield 'string_text' => ['not a number', 'string'];
        yield 'float' => [12.34, 'double'];
        yield 'boolean_true' => [true, 'boolean'];
        yield 'boolean_false' => [false, 'boolean'];
        yield 'null' => [null, 'NULL'];
        yield 'array' => [[], 'array'];
        yield 'object' => [new stdClass(), 'object'];
        yield 'resource' => [fopen('php://memory', 'r'), 'resource'];
    }
}
