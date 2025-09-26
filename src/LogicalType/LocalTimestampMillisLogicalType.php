<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTime;
use DateTimeInterface;

class LocalTimestampMillisLogicalType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (is_int($datum)) {
            return true; // Already milliseconds since Unix epoch
        }

        if ($datum instanceof DateTimeInterface) {
            return true;
        }

        $context?->addError('Local timestamp value must be an integer (milliseconds) or DateTimeInterface object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        if (is_int($datum)) {
            return $datum; // Already milliseconds
        }

        if ($datum instanceof DateTimeInterface) {
            // Local timestamp ignores timezone, treats as local time
            $localTime = new DateTime($datum->format('Y-m-d H:i:s.u'));
            return (int) ($localTime->getTimestamp() * 1000 + intval($localTime->format('u') / 1000));
        }

        throw new \InvalidArgumentException('Local timestamp value must be an integer or DateTimeInterface object');
    }

    public function denormalize(mixed $datum): mixed
    {
        if (!is_int($datum)) {
            throw new \InvalidArgumentException('Expected integer (milliseconds) for local timestamp denormalization');
        }

        $seconds = intval($datum / 1000);
        $milliseconds = $datum % 1000;
        
        $dateTime = new DateTime('@' . $seconds);
        
        // Format as local timestamp (no timezone indicator)
        if ($milliseconds > 0) {
            return $dateTime->format('Y-m-d H:i:s') . '.' . str_pad((string) $milliseconds, 3, '0', STR_PAD_LEFT);
        } else {
            return $dateTime->format('Y-m-d H:i:s');
        }
    }
}