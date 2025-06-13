<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class MapReader implements ReaderInterface
{
    public function __construct(
        private readonly ReaderInterface $valueReader,
        private readonly BinaryDecoder $decoder,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        $map = [];

        while (($blockCount = $this->decoder->readLong($stream)) !== 0) {
            if ($blockCount < 0) {
                // If a block’s count is negative, its absolute value is used, and the count is followed immediately by a long block size
                // indicating the number of bytes in the block. This block size permits fast skipping through data, e.g., when projecting a
                // record to a subset of its fields.
                $blockCount = -$blockCount;
                /*$blockSize =*/ $this->decoder->readLong($stream);
            }

            for ($i = 0; $i < $blockCount; ++$i) {
                $keyLength = $this->decoder->readLong($stream);
                $key = $stream->read($keyLength);
                $map[$key] = $this->valueReader->read($stream);
            }
        }

        return $map;
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        while (($blockCount = $this->decoder->readLong($stream)) !== 0) {
            if ($blockCount < 0) {
                $blockSize = $this->decoder->readLong($stream);
                $stream->skip($blockSize);
                continue;
            }

            for ($i = 0; $i < $blockCount; ++$i) {
                $keyLength = $this->decoder->readLong($stream);
                $stream->skip($keyLength);
                $this->valueReader->skip($stream);
            }
        }
    }
}
