<?php

declare(strict_types=1);

namespace Auxmoney\Avro\IO;

use Apache\Avro\Schema\AvroSchema;
use Auxmoney\Avro\Adapters\Apache\Datum\AvroIOBinaryEncoder;
use Auxmoney\Avro\Adapters\Apache\Datum\AvroIODatumWriter;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;

class Writer implements WriterInterface
{
    public function __construct(
        private AvroSchema $schema,
        private AvroIODatumWriter $datumWriter,
    ) {
    }

    public function write(mixed $data, WritableStreamInterface $stream): void
    {
        $encoder = new AvroIOBinaryEncoder($stream);
        $this->datumWriter->writeData($this->schema, $data, $encoder);
    }
}
