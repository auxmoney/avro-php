<?php

declare(strict_types=1);

namespace Auxmoney\Avro\ValueObject;

use Auxmoney\Avro\Exceptions\InvalidArgumentException;
use DateTimeInterface;

readonly class TimeOfDay
{
    public function __construct(
        public int $totalMicroseconds,
    ) {
        if ($totalMicroseconds < 0 || $totalMicroseconds >= 86400000000) {
            throw new InvalidArgumentException('Total microseconds must be between 0 and 86399999999 (midnight to 23:59:59.999999)');
        }
    }

    public function __toString(): string
    {
        return sprintf('%02d:%02d:%02d.%06d', $this->getHours(), $this->getMinutes(), $this->getSeconds(), $this->getMicroseconds());
    }

    public static function fromComponents(
        int $hours,
        int $minutes = 0,
        int $seconds = 0,
        int $milliseconds = 0,
        int $microseconds = 0,
    ): self {
        if ($hours < 0 || $hours > 23) {
            throw new InvalidArgumentException('Hours must be between 0 and 23');
        }
        if ($minutes < 0 || $minutes > 59) {
            throw new InvalidArgumentException('Minutes must be between 0 and 59');
        }
        if ($seconds < 0 || $seconds > 59) {
            throw new InvalidArgumentException('Seconds must be between 0 and 59');
        }
        if ($milliseconds < 0 || $milliseconds > 999) {
            throw new InvalidArgumentException('Milliseconds must be between 0 and 999');
        }
        if ($microseconds < 0 || $microseconds > 999) {
            throw new InvalidArgumentException('Microseconds must be between 0 and 999');
        }

        $totalMicroseconds = ($hours * 3600 + $minutes * 60 + $seconds) * 1000000 + $milliseconds * 1000 + $microseconds;

        return new self($totalMicroseconds);
    }

    public static function fromDateTime(DateTimeInterface $dateTime): self
    {
        $hours = (int) $dateTime->format('H');
        $minutes = (int) $dateTime->format('i');
        $seconds = (int) $dateTime->format('s');
        $microseconds = (int) $dateTime->format('u');

        return self::fromComponents($hours, $minutes, $seconds, intval($microseconds / 1000), $microseconds % 1000);
    }

    public function getHours(): int
    {
        return intval($this->totalMicroseconds / 3600000000);
    }

    public function getMinutes(): int
    {
        return intval(($this->totalMicroseconds % 3600000000) / 60000000);
    }

    public function getSeconds(): int
    {
        return intval(($this->totalMicroseconds % 60000000) / 1000000);
    }

    public function getMilliseconds(): int
    {
        return intval(($this->totalMicroseconds % 1000000) / 1000);
    }

    public function getMicroseconds(): int
    {
        return $this->totalMicroseconds % 1000000;
    }

    public function getTotalMilliseconds(): int
    {
        return intval($this->totalMicroseconds / 1000);
    }
}
