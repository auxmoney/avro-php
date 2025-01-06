<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

/**
 * @template T
 */
interface ReaderInterface
{
    /**
     * @return T
     */
    public function read(ReadableStreamInterface $stream): mixed;
}
