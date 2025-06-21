<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Exceptions\SchemaMismatchException;

class UnmatchedBranchReader implements ReaderInterface
{
    public function __construct(
        private readonly ReaderInterface $writerReader,
    ) {
    }

    public function read(ReadableStreamInterface $stream): mixed
    {
        throw new SchemaMismatchException('Unmatched branch encountered during deserialization');
    }

    public function skip(ReadableStreamInterface $stream): void
    {
        $this->writerReader->skip($stream);
    }
}
