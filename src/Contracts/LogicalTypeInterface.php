<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

interface LogicalTypeInterface
{
    public function isValid(mixed $datum): bool;

    public function normalize(mixed $datum): mixed;

    public function denormalize(mixed $datum): mixed;
}
