<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

use Auxmoney\Avro\Exceptions\SchemaMismatchException;

interface ReaderInterface
{
    /**
     * @throws SchemaMismatchException
     */
    public function read(ReadableStreamInterface $stream): mixed;

    public function skip(ReadableStreamInterface $stream): void;
}
