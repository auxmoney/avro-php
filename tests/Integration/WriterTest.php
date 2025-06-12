<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Integration;

use Auxmoney\Avro\AvroFactory;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Generator;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WriterTest extends TestCase
{
    /**
     * @throws InvalidSchemaException
     */
    #[DataProvider('dataProvider')]
    public function testWriter(string $schema, mixed $data, string $hex)
    {
        $avroFactory = AvroFactory::create();
        $writer = $avroFactory->createWriter($schema);
        $buffer = $avroFactory->createStringBuffer();
        $writer->write($data, $buffer);
        $actualHex = bin2hex($buffer->__toString());
        $this->assertSame($hex, $actualHex, 'Encoded data does not match expected output.');
    }

    /**
     * @throws JsonException
     */
    public static function dataProvider(): Generator
    {
        yield from TestCasesLoader::load();
    }
}
