<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;

class ArrayReader implements ReaderInterface
{
    public function __construct(
        private readonly ReaderInterface $itemReader,
        private readonly BinaryDecoder $decoder,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        $items = [];

        while (($blockCount = $this->decoder->readLong($stream)) !== 0) {
            if ($blockCount < 0) {
                $blockCount = -$blockCount;

                // Read block size if negative count indicates a block size
                /*$blockSize =*/ $this->decoder->readLong($stream);
            }

            for ($i = 0; $i < $blockCount; ++$i) {
                $items[] = $this->itemReader->read($stream);
            }
        }

        return $items;
    }
}
