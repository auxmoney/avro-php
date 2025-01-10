<?php

declare(strict_types=1);

namespace Auxmoney\Avro\IO;

use Apache\Avro\Schema\AvroSchema;
use Auxmoney\Avro\Adapters\Apache\Datum\AvroIOBinaryDecoder;
use Auxmoney\Avro\Adapters\Apache\Datum\AvroIODatumReader;
use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

readonly class Reader implements ReaderInterface
{
    public function __construct(
        private AvroSchema $writerSchema,
        private AvroSchema $readerSchema,
        private AvroIODatumReader $datumReader,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        $decoder = new AvroIOBinaryDecoder($stream);
        return $this->datumReader->readData($this->writerSchema, $this->readerSchema, $decoder);
    }
}
