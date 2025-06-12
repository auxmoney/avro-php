<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

use Auxmoney\Avro\Exceptions\DataMismatchException;

interface WriterInterface
{
    /**
     * @throws DataMismatchException
     */
    public function write(mixed $datum, WritableStreamInterface $stream): void;

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool;
}
