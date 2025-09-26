<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;

class UuidLogicalType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (!is_string($datum)) {
            $context?->addError('UUID value must be a string');
            return false;
        }

        // UUID format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $datum)) {
            $context?->addError('Invalid UUID format. Expected format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
            return false;
        }

        return true;
    }

    public function normalize(mixed $datum): mixed
    {
        // UUIDs are stored as strings in Avro, so no conversion needed
        return (string) $datum;
    }

    public function denormalize(mixed $datum): mixed
    {
        // UUIDs are stored as strings in Avro, so no conversion needed
        return (string) $datum;
    }
}