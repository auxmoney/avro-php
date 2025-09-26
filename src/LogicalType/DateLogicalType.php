<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTime;
use DateTimeInterface;

class DateLogicalType implements LogicalTypeInterface
{
    private const UNIX_EPOCH = '1970-01-01';

    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (is_int($datum)) {
            return true; // Already days since epoch
        }

        if ($datum instanceof DateTimeInterface) {
            return true;
        }

        $context?->addError('Date value must be an integer (days since epoch) or DateTimeInterface object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        if (is_int($datum)) {
            return $datum; // Already days since epoch
        }

        if ($datum instanceof DateTimeInterface) {
            $epoch = new DateTime(self::UNIX_EPOCH);
            $diff = $epoch->diff($datum);
            return $diff->invert ? -$diff->days : $diff->days;
        }

        throw new \InvalidArgumentException('Date value must be an integer or DateTimeInterface object');
    }

    public function denormalize(mixed $datum): mixed
    {
        if (!is_int($datum)) {
            throw new \InvalidArgumentException('Expected integer (days since epoch) for date denormalization');
        }

        $epoch = new DateTime(self::UNIX_EPOCH);
        $epoch->modify("{$datum} days");
        
        return $epoch->format('Y-m-d');
    }
}