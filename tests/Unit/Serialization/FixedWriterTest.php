<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Serialization\FixedWriter;
use Auxmoney\Avro\Serialization\ValidationContext;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class FixedWriterTest extends TestCase
{
    private FixedWriter $writer;
    private WritableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;
    private int $fixedSize;

    protected function setUp(): void
    {
        $this->stream = $this->createMock(WritableStreamInterface::class);
        $this->fixedSize = 16; // MD5 hash size
        $this->writer = new FixedWriter($this->fixedSize);
    }

    public function testValidateWithCorrectLength(): void
    {
        $context = new ValidationContext();
        $validData = str_repeat('a', $this->fixedSize);

        $result = $this->writer->validate($validData, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithEmptyStringWhenSizeIsZero(): void
    {
        $zeroWriter = new FixedWriter(0);
        $context = new ValidationContext();

        $result = $zeroWriter->validate('', $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithBinaryData(): void
    {
        $context = new ValidationContext();
        $binaryData = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a\x0b\x0c\x0d\x0e\x0f";

        $result = $this->writer->validate($binaryData, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithUnicodeString(): void
    {
        $context = new ValidationContext();
        // Create a unicode string that's exactly 16 bytes
        $unicodeData = '🚀🌍🎵🎭'; // Each emoji is 4 bytes, so 4 * 4 = 16 bytes

        $result = $this->writer->validate($unicodeData, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithTooShortString(): void
    {
        $context = new ValidationContext();
        $shortData = str_repeat('a', $this->fixedSize - 1);

        $result = $this->writer->validate($shortData, $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("expected string of length {$this->fixedSize}, got " . strlen($shortData), $errors[0]);
    }

    public function testValidateWithTooLongString(): void
    {
        $context = new ValidationContext();
        $longData = str_repeat('a', $this->fixedSize + 1);

        $result = $this->writer->validate($longData, $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("expected string of length {$this->fixedSize}, got " . strlen($longData), $errors[0]);
    }

    #[DataProvider('invalidDataProvider')]
    public function testValidateWithInvalidData(mixed $invalidData, string $expectedType): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate($invalidData, $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("expected string, got {$expectedType}", $errors[0]);
    }

    public function testValidateWithoutContext(): void
    {
        $validData = str_repeat('a', $this->fixedSize);

        $result = $this->writer->validate($validData);

        $this->assertTrue($result);
    }

    public function testValidateInvalidDataWithoutContext(): void
    {
        $invalidData = str_repeat('a', $this->fixedSize - 1);

        $result = $this->writer->validate($invalidData);

        $this->assertFalse($result);
    }

    public function testWriteWithValidData(): void
    {
        $validData = str_repeat('a', $this->fixedSize);

        $this->stream->expects($this->once())
            ->method('write')
            ->with($validData);

        $this->writer->write($validData, $this->stream);
    }

    public function testWriteWithBinaryData(): void
    {
        $binaryData = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a\x0b\x0c\x0d\x0e\x0f";

        $this->stream->expects($this->once())
            ->method('write')
            ->with($binaryData);

        $this->writer->write($binaryData, $this->stream);
    }

    public function testWriteWithEmptyDataWhenSizeIsZero(): void
    {
        $zeroWriter = new FixedWriter(0);
        $emptyData = '';

        $this->stream->expects($this->once())
            ->method('write')
            ->with($emptyData);

        $zeroWriter->write($emptyData, $this->stream);
    }

    public function testValidateWithNullContext(): void
    {
        $validData = str_repeat('a', $this->fixedSize);

        $result = $this->writer->validate($validData, null);

        $this->assertTrue($result);
    }

    public function testValidateInvalidDataWithNullContext(): void
    {
        $invalidData = str_repeat('a', $this->fixedSize - 1);

        $result = $this->writer->validate($invalidData, null);

        $this->assertFalse($result);
    }

    public function testValidateWithDifferentFixedSizes(): void
    {
        $sizes = [1, 4, 8, 32, 64, 128, 256];

        foreach ($sizes as $size) {
            $writer = new FixedWriter($size);
            $context = new ValidationContext();
            $validData = str_repeat('x', $size);

            $result = $writer->validate($validData, $context);

            $this->assertTrue($result, "Size {$size} should be valid");
            $this->assertEmpty($context->getContextErrors());
        }
    }

    public function testValidateOffByOneErrors(): void
    {
        $context = new ValidationContext();

        // Test one byte short
        $shortData = str_repeat('a', $this->fixedSize - 1);
        $result = $this->writer->validate($shortData, $context);
        $this->assertFalse($result);

        // Test one byte long
        $context = new ValidationContext(); // Reset context
        $longData = str_repeat('a', $this->fixedSize + 1);
        $result = $this->writer->validate($longData, $context);
        $this->assertFalse($result);
    }

    public function testValidateWithNullBytesInString(): void
    {
        $context = new ValidationContext();
        $dataWithNulls = str_repeat("\x00", $this->fixedSize);

        $result = $this->writer->validate($dataWithNulls, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithMixedBinaryAndTextData(): void
    {
        $context = new ValidationContext();
        // Mix of text and binary to reach exact length
        $mixedData = "hello\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a";
        $this->assertEquals($this->fixedSize, strlen($mixedData));

        $result = $this->writer->validate($mixedData, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
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
