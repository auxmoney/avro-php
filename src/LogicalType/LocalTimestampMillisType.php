<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTime;
use DateTimeInterface;

class LocalTimestampMillisType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if ($datum instanceof DateTimeInterface) {
            return true;
        }

        $context?->addError('Local timestamp value must be a DateTimeInterface object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        assert($datum instanceof DateTimeInterface);

        // Local timestamp ignores timezone, treats as local time
        $localTime = new DateTime($datum->format('Y-m-d H:i:s.u'));
        return (int) ($localTime->getTimestamp() * 1000 + intval($localTime->format('u') / 1000));
    }

    public function denormalize(mixed $datum): mixed
    {
        assert(is_int($datum), 'Expected integer (milliseconds) for local timestamp denormalization');

        $seconds = intval($datum / 1000);
        $milliseconds = $datum % 1000;

        $dateTime = new DateTime('@' . $seconds);

        // Format as local timestamp (no timezone indicator)
        if ($milliseconds > 0) {
            return $dateTime->format('Y-m-d H:i:s') . '.' . str_pad((string) $milliseconds, 3, '0', STR_PAD_LEFT);
        }
        return $dateTime->format('Y-m-d H:i:s');
    }
}
