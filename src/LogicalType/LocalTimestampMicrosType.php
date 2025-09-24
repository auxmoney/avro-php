<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class LocalTimestampMicrosType implements LogicalTypeInterface
{
    private DateTimeImmutable $zeroDate;
    private DateTimeZone $defaultTimeZone;

    public function __construct()
    {
        $this->zeroDate = new DateTimeImmutable('@0');
        $this->defaultTimeZone = (new DateTimeImmutable())->getTimezone();
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (!($datum instanceof DateTimeInterface)) {
            $context?->addError('expected DateTimeInterface, got ' . gettype($datum));
            return false;
        }

        return true;
    }

    public function normalize(mixed $datum): mixed
    {
        assert($datum instanceof DateTimeInterface);
        
        // For local timestamp: treat the local time components as if they were UTC
        // e.g., 14:00 CEST should be treated as 14:00 UTC, not 12:00 UTC
        $utcDateTime = new DateTimeImmutable($datum->format('Y-m-d H:i:s.u'), new DateTimeZone('UTC'));
        return $this->getMicrosecondsSinceEpoch($utcDateTime);
    }

    public function denormalize(mixed $datum): mixed
    {
        assert(is_int($datum), 'LocalTimestampMicros logical type datum must be an integer');

        $seconds = intval($datum / 1000000);
        $remainingMicroseconds = $datum % 1000000;

        // Create UTC datetime from epoch microseconds
        $utcDateTime = $this->zeroDate
            ->modify("+{$seconds} seconds")
            ->modify("+{$remainingMicroseconds} microseconds");
        
        // Create a new DateTime with the same time components but in local timezone
        // This preserves the time (e.g., 12:00 UTC becomes 12:00 local, not shifted)
        return new DateTimeImmutable($utcDateTime->format('Y-m-d H:i:s.u'), $this->defaultTimeZone);
    }

    private function getMicrosecondsSinceEpoch(DateTimeInterface $datum): int
    {
        $seconds = (int) $datum->format('U');
        $microseconds = (int) $datum->format('u');

        return $seconds * 1000000 + $microseconds;
    }
}
