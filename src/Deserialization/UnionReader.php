<?php

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
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        $branchIndex = ord($stream->read(1));
        if (!isset($this->branchReaders[$branchIndex])) {
            throw new RuntimeException('Invalid branch index: ' . $branchIndex);
        }

        return $this->branchReaders[$branchIndex]->read($stream);
    }
}