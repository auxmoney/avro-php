<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTime;
use DateTimeInterface;

class TimestampMicrosLogicalType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (is_int($datum)) {
            return true; // Already microseconds since Unix epoch
        }

        if ($datum instanceof DateTimeInterface) {
            return true;
        }

        $context?->addError('Timestamp value must be an integer (microseconds since Unix epoch) or DateTimeInterface object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        if (is_int($datum)) {
            return $datum; // Already microseconds since epoch
        }

        if ($datum instanceof DateTimeInterface) {
            return (int) ($datum->getTimestamp() * 1000000 + (int) $datum->format('u'));
        }

        throw new \InvalidArgumentException('Timestamp value must be an integer or DateTimeInterface object');
    }

    public function denormalize(mixed $datum): mixed
    {
        if (!is_int($datum)) {
            throw new \InvalidArgumentException('Expected integer (microseconds since Unix epoch) for timestamp denormalization');
        }

        $seconds = intval($datum / 1000000);
        $microseconds = $datum % 1000000;
        
        $dateTime = new DateTime('@' . $seconds);
        $dateTime->setTimezone(new \DateTimeZone('UTC'));
        
        // Format with microseconds
        return $dateTime->format('Y-m-d\TH:i:s') . '.' . str_pad((string) $microseconds, 6, '0', STR_PAD_LEFT) . 'Z';
    }
}