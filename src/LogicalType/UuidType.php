<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;

class UuidType implements LogicalTypeInterface
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
        assert(is_string($datum));

        // Convert UUID string to 16-byte binary representation
        // Remove hyphens and convert hex to binary
        $hex = str_replace('-', '', $datum);
        return hex2bin($hex);
    }

    public function denormalize(mixed $datum): mixed
    {
        assert(is_string($datum) && strlen($datum) === 16);

        // Convert 16-byte binary back to UUID string format
        $hex = bin2hex($datum);

        // Format as UUID: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
