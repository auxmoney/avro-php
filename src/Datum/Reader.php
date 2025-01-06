<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Datum;

use Apache\Avro\Datum\AvroIOBinaryDecoder;
use Apache\Avro\Schema\AvroSchema;
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
        $io = new AvroIOReaderAdapter($stream);
        $decoder = new AvroIOBinaryDecoder($io);
        return $this->datumReader->readData($this->writerSchema, $this->readerSchema, $decoder);
    }
}
