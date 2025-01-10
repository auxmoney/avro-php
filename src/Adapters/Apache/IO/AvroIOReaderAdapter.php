<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Adapters\Apache\IO;

use Apache\Avro\AvroIO;
use Apache\Avro\AvroNotImplementedException;
use Auxmoney\Avro\Contracts\ReadableStreamInterface;

class AvroIOReaderAdapter extends AvroIO
{
    public function __construct(private readonly ReadableStreamInterface $stream)
    {
    }

    public function read($len): string
    {
        return $this->stream->read($len);
    }

    public function seek($offset, $whence = AvroIO::SEEK_SET): bool
    {
        if ($whence !== AvroIO::SEEK_CUR) {
            throw new AvroNotImplementedException('Not implemented');
        }

        $this->stream->skip($offset);
        return true;
    }
}
