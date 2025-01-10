<?php

namespace Auxmoney\Avro\Tests\Adapters\Apache\Datum;

use Apache\Avro\AvroException;
use Apache\Avro\Datum\AvroIOBinaryEncoder;
use Apache\Avro\Schema\AvroSchema;
use Auxmoney\Avro\Adapters\Apache\Datum\AvroIODatumWriter;
use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use PHPUnit\Framework\TestCase;
use Throwable;

class AvroIODatumWriterTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testWriteDataWithValidLogicalType(): void
    {
        $datum = 'test';

        $schema = new AvroSchema(AvroSchema::BYTES_TYPE);
        $schema->extraAttributes['logicalType'] = 'logical';

        $encoder = $this->createMock(AvroIOBinaryEncoder::class);

        $logicalTypeFactory = $this->createMock(LogicalTypeFactoryInterface::class);
        $logicalType = $this->createMock(LogicalTypeInterface::class);
        $logicalTypeFactory->method('create')->with(['logicalType' => 'logical'])->willReturn($logicalType);

        $logicalType->expects($this->once())
            ->method('normalize')
            ->with($datum);

        $logicalType->expects($this->once())
            ->method('isValid')
            ->with($datum)
            ->willReturn(true);

        $writer = new AvroIODatumWriter(['logical' => $logicalTypeFactory]);

        $writer->writeData($schema, 'test', $encoder);
    }

    /**
     * @throws Throwable
     */
    public function testWriteDataWithInvalidLogicalType(): void
    {
        $datum = 'test';

        $schema = new AvroSchema(AvroSchema::BYTES_TYPE);
        $schema->extraAttributes['logicalType'] = 'logical';

        $encoder = $this->createMock(AvroIOBinaryEncoder::class);

        $logicalTypeFactory = $this->createMock(LogicalTypeFactoryInterface::class);
        $logicalType = $this->createMock(LogicalTypeInterface::class);
        $logicalTypeFactory->method('create')->with(['logicalType' => 'logical'])->willReturn($logicalType);

        $logicalType->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $writer = new AvroIODatumWriter(['logical' => $logicalTypeFactory]);

        $this->expectExceptionMessage('The datum \'test\' is not an example of schema {"type":"bytes"}');
        $this->expectException(AvroException::class);

        $writer->writeData($schema, 'test', $encoder);
    }
}
