<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTime;
use DateTimeInterface;
use DateTimeZone;

class TimestampMillisType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if ($datum instanceof DateTimeInterface) {
            return true;
        }

        $context?->addError('Timestamp value must be a DateTimeInterface object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        assert($datum instanceof DateTimeInterface);

        return (int) ($datum->getTimestamp() * 1000 + intval($datum->format('u') / 1000));
    }

    public function denormalize(mixed $datum): mixed
    {
        assert(is_int($datum), 'Expected integer (milliseconds since Unix epoch) for timestamp denormalization');

        $seconds = intval($datum / 1000);
        $milliseconds = $datum % 1000;

        $dateTime = new DateTime('@' . $seconds);
        $dateTime->setTimezone(new DateTimeZone('UTC'));

        // Add milliseconds
        if ($milliseconds > 0) {
            $dateTime->modify('+' . $milliseconds . ' milliseconds');
        }

        return $dateTime->format('Y-m-d\TH:i:s.v\Z');
    }
}
