<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Adapters\Apache\Datum;

use Apache\Avro\Datum\AvroIOBinaryDecoder;
use Apache\Avro\Schema\AvroSchema;
use Auxmoney\Avro\Adapters\Apache\Datum\AvroIODatumReader;
use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use PHPUnit\Framework\TestCase;
use Throwable;

class AvroIODatumReaderTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testReadDataWithValidLogicalType(): void
    {
        $schema = new AvroSchema(AvroSchema::BYTES_TYPE);
        $schema->extraAttributes['logicalType'] = 'logical';

        $decoder = $this->createMock(AvroIOBinaryDecoder::class);
        $decoder->method('readBytes')->willReturn('normalized');

        $logicalTypeFactory = $this->createMock(LogicalTypeFactoryInterface::class);
        $logicalType = $this->createMock(LogicalTypeInterface::class);
        $logicalTypeFactory->method('create')->with(['logicalType' => 'logical'])->willReturn($logicalType);

        $logicalType->expects($this->once())
            ->method('denormalize')
            ->with('normalized')
            ->willReturn('denormalized');

        $reader = new AvroIODatumReader(['logical' => $logicalTypeFactory]);

        $reader->readData($schema, $schema, $decoder);
    }
}
