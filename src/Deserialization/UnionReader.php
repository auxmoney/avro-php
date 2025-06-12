<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use RuntimeException;

class UnionReader implements ReaderInterface
{
    /**
     * @param array<ReaderInterface> $branchReaders
     */
    public function __construct(
        private readonly array $branchReaders,
        private readonly BinaryDecoder $decoder,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        $branchIndex = $this->decoder->readLong($stream);
        if (!isset($this->branchReaders[$branchIndex])) {
            throw new RuntimeException('Invalid branch index: ' . $branchIndex);
        }

        return $this->branchReaders[$branchIndex]->read($stream);
    }
}
