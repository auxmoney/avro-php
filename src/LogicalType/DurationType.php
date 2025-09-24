<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\ValueObject\Duration;

class DurationType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if ($datum instanceof Duration) {
            return true; // Duration value object
        }

        $context?->addError('Duration value must be a Duration object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        assert($datum instanceof Duration);

        // Pack as 3 little-endian unsigned 32-bit integers (12 bytes total)
        return pack('VVV', $datum->months, $datum->days, $datum->milliseconds);
    }

    public function denormalize(mixed $datum): Duration
    {
        assert(is_string($datum) && strlen($datum) === 12, 'Expected 12-byte string for duration denormalization');

        // Unpack 3 little-endian unsigned 32-bit integers
        $values = unpack('V3', $datum);
        assert($values !== false && is_int($values[1]) && is_int($values[2]) && is_int($values[3]), 'Failed to unpack duration data');

        return new Duration(months: $values[1], days: $values[2], milliseconds: $values[3]);
    }
}
