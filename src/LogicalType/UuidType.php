<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\ValueObject\Uuid;

class UuidType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (!($datum instanceof Uuid)) {
            $context?->addError('UUID value must be a Uuid value object');
            return false;
        }

        return true;
    }

    public function normalize(mixed $datum): mixed
    {
        assert($datum instanceof Uuid);

        return $datum->toBytes();
    }

    public function denormalize(mixed $datum): mixed
    {
        assert(is_string($datum) && strlen($datum) === 16);

        // Convert 16-byte binary to Uuid value object
        return Uuid::fromBytes($datum);
    }
}
