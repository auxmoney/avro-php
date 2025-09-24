<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class TimestampMillisType implements LogicalTypeInterface
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
        return $this->getMillisecondsSinceEpoch($datum);
    }

    public function denormalize(mixed $datum): mixed
    {
        assert(is_int($datum), 'TimestampMillis logical type datum must be an integer');

        return $this->zeroDate->modify("{$datum} milliseconds")->setTimezone($this->defaultTimeZone);
    }

    private function getMillisecondsSinceEpoch(DateTimeInterface $datum): int
    {
        $seconds = (int) $datum->format('U');
        $milliseconds = (int) $datum->format('v');

        return $seconds * 1000 + $milliseconds;
    }
}
