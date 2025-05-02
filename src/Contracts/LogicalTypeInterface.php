<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

interface LogicalTypeInterface
{
    public function validate(mixed $datum, ValidationContextInterface $context): bool;

    public function normalize(mixed $datum): mixed;

    public function denormalize(mixed $datum): mixed;
}
