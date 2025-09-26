<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\ValueObject\TimeOfDay;
use InvalidArgumentException;

class TimeMicrosType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if ($datum instanceof TimeOfDay) {
            return true;
        }

        $context?->addError('Time value must be a TimeOfDay object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        assert($datum instanceof TimeOfDay);

        return $datum->totalMicroseconds;
    }

    public function denormalize(mixed $datum): TimeOfDay
    {
        if (!is_int($datum)) {
            throw new InvalidArgumentException('Expected integer (microseconds since midnight) for time denormalization');
        }

        return new TimeOfDay($datum);
    }
}
