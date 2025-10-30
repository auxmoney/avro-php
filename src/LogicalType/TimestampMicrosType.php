<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class TimestampMicrosType implements LogicalTypeInterface
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
        return $this->getMicrosecondsSinceEpoch($datum);
    }

    public function denormalize(mixed $datum): mixed
    {
        assert(is_int($datum), 'TimestampMicros logical type datum must be an integer');

        $seconds = intval($datum / 1000000);
        $remainingMicroseconds = $datum % 1000000;

        return $this->zeroDate
            ->modify("+{$seconds} seconds")
            ->modify("+{$remainingMicroseconds} microseconds")
            ->setTimezone($this->defaultTimeZone);
    }

    private function getMicrosecondsSinceEpoch(DateTimeInterface $datum): int
    {
        $seconds = (int) $datum->format('U');
        $microseconds = (int) $datum->format('u');

        return $seconds * 1000000 + $microseconds;
    }
}
