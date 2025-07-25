<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

use Auxmoney\Avro\Exceptions\StreamWriteException;

interface WritableStreamInterface
{
    /**
     * @throws StreamWriteException
     */
    public function write(string $datum): int;
}
