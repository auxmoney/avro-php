<?php

declare(strict_types=1);

namespace Auxmoney\Avro\ValueObject;

use Auxmoney\Avro\Exceptions\InvalidArgumentException;

/**
 * Represents an AVRO duration logical type value.
 * 
 * A duration is composed of three unsigned 32-bit integers:
 * - months: the number of months (0-4294967295)
 * - days: the number of days (0-4294967295) 
 * - milliseconds: the number of milliseconds (0-4294967295)
 * 
 * This class provides a convenient object-oriented interface for working with
 * duration values in AVRO serialization/deserialization contexts.
 * 
 * @example
 * // Create a duration representing 2 months, 15 days, and 5 seconds
 * $duration = new Duration(2, 15, 5000);
 * 
 * // Access components via public properties
 * $months = $duration->months;
 * $days = $duration->days;
 * $milliseconds = $duration->milliseconds;
 */
readonly class Duration
{
    public function __construct(
        public int $months = 0,
        public int $days = 0,
        public int $milliseconds = 0,
    ) {
        if ($months < 0) {
            throw new InvalidArgumentException('Months must be non-negative');
        }
        if ($days < 0) {
            throw new InvalidArgumentException('Days must be non-negative');
        }
        if ($milliseconds < 0) {
            throw new InvalidArgumentException('Milliseconds must be non-negative');
        }
    }
}