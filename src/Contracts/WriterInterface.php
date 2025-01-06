<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

/**
 * @template T
 */
interface WriterInterface
{
    /**
     * @param T $data
     */
    public function write(mixed $data, WritableStreamInterface $stream): void;
}
