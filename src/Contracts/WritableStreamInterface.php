<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

interface WritableStreamInterface
{
    public function write(string $data): int;
}
