<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTime;
use DateTimeInterface;

class TimestampMillisLogicalType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (is_int($datum)) {
            return true; // Already milliseconds since Unix epoch
        }

        if ($datum instanceof DateTimeInterface) {
            return true;
        }

        $context?->addError('Timestamp value must be an integer (milliseconds since Unix epoch) or DateTimeInterface object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        if (is_int($datum)) {
            return $datum; // Already milliseconds since epoch
        }

        if ($datum instanceof DateTimeInterface) {
            return (int) ($datum->getTimestamp() * 1000 + intval($datum->format('u') / 1000));
        }

        throw new \InvalidArgumentException('Timestamp value must be an integer or DateTimeInterface object');
    }

    public function denormalize(mixed $datum): mixed
    {
        if (!is_int($datum)) {
            throw new \InvalidArgumentException('Expected integer (milliseconds since Unix epoch) for timestamp denormalization');
        }

        $seconds = intval($datum / 1000);
        $milliseconds = $datum % 1000;
        
        $dateTime = new DateTime('@' . $seconds);
        $dateTime->setTimezone(new \DateTimeZone('UTC'));
        
        // Add milliseconds
        if ($milliseconds > 0) {
            $dateTime->modify('+' . $milliseconds . ' milliseconds');
        }
        
        return $dateTime->format('Y-m-d\TH:i:s.v\Z');
    }
}