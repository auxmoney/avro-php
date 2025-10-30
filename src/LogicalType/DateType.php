<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTimeImmutable;
use DateTimeInterface;

class DateType implements LogicalTypeInterface
{
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
        return $this->getDaysSinceEpoch($datum);
    }

    public function denormalize(mixed $datum): DateTimeInterface
    {
        $epochDate = DateTimeImmutable::createFromFormat('!Y-m-d', '1970-01-01');
        assert($epochDate !== false, 'Could not create epoch');
        assert(is_int($datum), 'Date logical type datum must be an integer');

        return $epochDate->modify("{$datum} days");
    }

    private function getDaysSinceEpoch(DateTimeInterface $datum): int
    {
        $epoch = DateTimeImmutable::createFromFormat('!Y-m-d', '1970-01-01', $datum->getTimezone());
        assert($epoch !== false, 'Could not create epoch');

        $dateInterval = $epoch->diff($datum);
        $days = $dateInterval->days;
        assert($days !== false, 'Could not calculate days since epoch');

        return $dateInterval->invert ? -$days : $days;
    }
}
