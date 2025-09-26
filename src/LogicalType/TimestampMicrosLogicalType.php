<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTime;
use DateTimeInterface;

class TimestampMicrosLogicalType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (is_int($datum)) {
            return true; // Already microseconds since Unix epoch
        }

        if (is_string($datum)) {
            // Try to parse as ISO 8601 timestamp
            if (DateTime::createFromFormat(DateTime::ATOM, $datum) !== false ||
                DateTime::createFromFormat('Y-m-d H:i:s', $datum) !== false ||
                DateTime::createFromFormat('Y-m-d\TH:i:s', $datum) !== false) {
                return true;
            }
            
            $context?->addError('Invalid timestamp format. Expected ISO 8601 format or Y-m-d H:i:s');
            return false;
        }

        if ($datum instanceof DateTimeInterface) {
            return true;
        }

        $context?->addError('Timestamp value must be an integer (microseconds since Unix epoch), timestamp string, or DateTime object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        if (is_int($datum)) {
            return $datum; // Already microseconds since epoch
        }

        if ($datum instanceof DateTimeInterface) {
            return (int) ($datum->getTimestamp() * 1000000 + (int) $datum->format('u'));
        }

        if (is_string($datum)) {
            $date = DateTime::createFromFormat(DateTime::ATOM, $datum) ?:
                    DateTime::createFromFormat('Y-m-d H:i:s', $datum) ?:
                    DateTime::createFromFormat('Y-m-d\TH:i:s', $datum);
                    
            if ($date === false) {
                throw new \InvalidArgumentException('Invalid timestamp format');
            }
            
            return (int) ($date->getTimestamp() * 1000000 + (int) $date->format('u'));
        }

        throw new \InvalidArgumentException('Cannot normalize timestamp value');
    }

    public function denormalize(mixed $datum): mixed
    {
        if (!is_int($datum)) {
            throw new \InvalidArgumentException('Expected integer (microseconds since Unix epoch) for timestamp denormalization');
        }

        $seconds = intval($datum / 1000000);
        $microseconds = $datum % 1000000;
        
        $dateTime = new DateTime('@' . $seconds);
        $dateTime->setTimezone(new \DateTimeZone('UTC'));
        
        // Format with microseconds
        return $dateTime->format('Y-m-d\TH:i:s') . '.' . str_pad((string) $microseconds, 6, '0', STR_PAD_LEFT) . 'Z';
    }
}