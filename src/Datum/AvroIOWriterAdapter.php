<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Datum;

use Apache\Avro\AvroIO;
use Auxmoney\Avro\Contracts\WritableStreamInterface;

class AvroIOWriterAdapter extends AvroIO
{
    public function __construct(private readonly WritableStreamInterface $stream)
    {
    }

    public function write($arg)
    {
        return $this->stream->write($arg);
    }
}
