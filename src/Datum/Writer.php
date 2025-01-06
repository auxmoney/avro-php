<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Datum;

use Apache\Avro\Datum\AvroIOBinaryEncoder;
use Apache\Avro\Schema\AvroSchema;
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
        $io = new AvroIOWriterAdapter($stream);
        $encoder = new AvroIOBinaryEncoder($io);
        $this->datumWriter->writeData($this->schema, $data, $encoder);
    }
}
