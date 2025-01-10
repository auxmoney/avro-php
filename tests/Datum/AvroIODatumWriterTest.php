<?php

namespace Auxmoney\Avro\Tests\Datum;

use Apache\Avro\AvroException;
use Apache\Avro\Datum\AvroIOBinaryEncoder;
use Apache\Avro\Schema\AvroSchema;
use Auxmoney\Avro\Adapters\Apache\Datum\AvroIODatumWriter;
use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
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

        $logicalType = $this->createMock(LogicalTypeFactoryInterface::class);
        $logicalType->expects($this->once())
            ->method('normalize')
            ->with($schema->extraAttributes, $datum);

        $logicalType->expects($this->once())
            ->method('isValid')
            ->with($schema->extraAttributes, $datum)
            ->willReturn(true);

        $writer = new AvroIODatumWriter(['logical' => $logicalType]);

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

        $logicalType = $this->createMock(LogicalTypeFactoryInterface::class);
        $logicalType->expects($this->once())
            ->method('isValid')
            ->with($schema->extraAttributes, $datum)
            ->willReturn(false);

        $writer = new AvroIODatumWriter(['logical' => $logicalType]);

        $this->expectExceptionMessage('The datum \'test\' is not an example of schema {"type":"bytes","logicalType":"logical"}');
        $this->expectException(AvroException::class);

        $writer->writeData($schema, 'test', $encoder);
    }
}
