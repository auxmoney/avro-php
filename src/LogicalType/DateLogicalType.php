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

        if (is_string($datum)) {
            // Try to parse as date string
            if (DateTime::createFromFormat('Y-m-d', $datum) !== false) {
                return true;
            }
            
            $context?->addError('Invalid date format. Expected YYYY-MM-DD format');
            return false;
        }

        if ($datum instanceof DateTimeInterface) {
            return true;
        }

        $context?->addError('Date value must be an integer (days since epoch), date string (YYYY-MM-DD), or DateTime object');
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

        if (is_string($datum)) {
            $date = DateTime::createFromFormat('Y-m-d', $datum);
            if ($date === false) {
                throw new \InvalidArgumentException('Invalid date format. Expected YYYY-MM-DD');
            }
            
            $epoch = new DateTime(self::UNIX_EPOCH);
            $diff = $epoch->diff($date);
            return $diff->invert ? -$diff->days : $diff->days;
        }

        throw new \InvalidArgumentException('Cannot normalize date value');
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