<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

use Auxmoney\Avro\Exceptions\StreamReadException;

interface ReadableStreamInterface
{
    /**
     * This method must return the number of bytes requested or throw an exception if the stream is exhausted.
     *
     * @throws StreamReadException
     */
    public function read(int $count): string;

    /**
     * This method must skip the number of bytes requested or throw an exception if the stream is exhausted.
     *
     * @throws StreamReadException
     */
    public function skip(int $count): void;
}
