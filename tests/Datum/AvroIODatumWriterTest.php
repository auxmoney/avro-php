<?php

namespace Auxmoney\Avro\Tests\Datum;

use Apache\Avro\Datum\AvroIOBinaryEncoder;
use Apache\Avro\Schema\AvroSchema;
use Auxmoney\Avro\Datum\AvroIODatumWriter;
use Auxmoney\Avro\Datum\LogicalTypeInterface;
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

        $logicalType = $this->createMock(LogicalTypeInterface::class);
        $logicalType->expects($this->once())
            ->method('writeData')
            ->with($schema, $datum, $encoder);

        $logicalType->expects($this->once())
            ->method('isValid')
            ->with($schema, $datum)
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

        $logicalType = $this->createMock(LogicalTypeInterface::class);
        $logicalType->expects($this->once())
            ->method('isValid')
            ->with($schema, $datum)
            ->willReturn(false);

        $writer = new AvroIODatumWriter(['logical' => $logicalType]);

        $this->expectExceptionMessage('The datum \'test\' is not an example of the logical type');

        $writer->writeData($schema, 'test', $encoder);
    }
}
