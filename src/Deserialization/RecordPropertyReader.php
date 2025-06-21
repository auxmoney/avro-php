<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;

interface RecordPropertyReader
{
    /**
     * @param array<string, mixed> $record
     */
    public function read(ReadableStreamInterface $stream, array &$record): void;

    public function skip(ReadableStreamInterface $stream): void;
}
