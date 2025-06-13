<?php

declare(strict_types=1);

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
    public function testReader(string $schema, mixed $data, string $hex): void
    {
        $avroFactory = AvroFactory::create();
        $reader = $avroFactory->createReader($schema);
        $binary = hex2bin($hex);
        assert($binary !== false, 'Failed to convert hex to binary.');
        $buffer = $avroFactory->createReadableStreamFromString($binary);
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
