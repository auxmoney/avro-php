<?php

namespace Auxmoney\Avro\Tests\Integration;

use Auxmoney\Avro\AvroFactory;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Generator;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    /**
     * @throws InvalidSchemaException
     */
    #[DataProvider('dataProvider')]
    public function testReader(string $schema, mixed $data, string $hex)
    {
        $avroFactory = AvroFactory::create();
        $reader = $avroFactory->createReader($schema);
        $buffer = $avroFactory->createReadableStreamFromString(hex2bin($hex));
        $actual = $reader->read($buffer);
        $this->assertEquals($data, $actual, 'Decoded data does not match expected data.');
    }

    /**
     * @throws JsonException
     */
    public static function dataProvider(): Generator
    {
        yield from TestCasesLoader::load();
    }
}
