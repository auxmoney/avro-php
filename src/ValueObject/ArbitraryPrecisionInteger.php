<?php

declare(strict_types=1);

namespace Auxmoney\Avro\ValueObject;

use Auxmoney\Avro\Exceptions\InvalidArgumentException;

final readonly class ArbitraryPrecisionInteger
{
    /**
     * @var int Limit the maximum exponent for each multiplication step to avoid 64-bit integer overflow
     */
    private const MAX_BYTES_EXPONENT = 16;
    private string $bytes;

    private function __construct(string $bytes)
    {
        $this->bytes = $bytes;
    }

    public static function fromBytes(string $bytes): self
    {
        return new self(self::trimBytesString($bytes));
    }

    public static function fromInteger(int|self $value): self
    {
        return $value instanceof self ? $value : self::fromBytes(pack('J', $value));
    }

    /**
     * @throws InvalidArgumentException if the input is not a valid integer string
     */
    public static function fromString(string $value): self
    {
        if (!preg_match('/^-?\d+$/', $value)) {
            throw new InvalidArgumentException('Value must be a valid integer string');
        }

        $isNegative = str_starts_with($value, '-');
        $absValue = ltrim($value, '-0') ?: '0';

        $bytes = [];
        foreach (self::toBytesArray($absValue) as $digitByte) {
            $digit = $digitByte - 48; // Convert ASCII digit to numeric value (0-9)

            // Multiply current value by 10
            $bytes = self::multiplyBytesByInt($bytes, 10);

            // Add the current digit
            if ($digit > 0) {
                $bytes = self::addBytesInt($bytes, $digit);
            }
        }

        array_unshift($bytes, 0);
        if ($isNegative) {
            $bytes = self::toTwosComplement($bytes);
        }

        return self::fromBytes(self::toBytesString($bytes));
    }

    public function toString(): string
    {
        $decimalDigits = $this->toAbsoluteDecimalDigits();
        foreach ($decimalDigits as $key => $digit) {
            $decimalDigits[$key] = 48 + $digit;
        }

        if ($this->isNegative()) {
            array_unshift($decimalDigits, 45); // ASCII code for '-'
        }

        return self::toBytesString($decimalDigits);
    }

    /**
     * @return array<int>
     */
    public function toAbsoluteDecimalDigits(): array
    {
        $byteArray = self::toBytesArray($this->bytes);

        if ($this->isNegative()) {
            $byteArray = self::toTwosComplement($byteArray);
        }

        $digits = [];
        while ($byteArray !== []) {
            [$byteArray, $remainder] = $this->divideBytesByInt($byteArray, 10);
            $digits[] = $remainder;
        }

        return $digits === [] ? [0] : array_reverse($digits);
    }

    /**
     * Shift decimal position by moving the implicit decimal point
     * Positive positions = multiply by 10^positions, negative = divide with HALF_UP rounding
     *
     * @param int $positions Number of decimal positions to shift
     */
    public function shiftDecimalPosition(int $positions): self
    {
        if ($positions === 0) {
            return $this;
        }

        if ($this->bytes === "\x00") {
            return $this;
        }

        $bytes = self::toBytesArray($this->bytes);
        $isNegative = $this->isNegative();

        if ($isNegative) {
            $bytes = self::toTwosComplement($bytes);
        }

        if ($positions > 0) {
            // Positive positions: multiply by 10^positions
            $remainingPositions = $positions;
            while ($remainingPositions > 0) {
                $iterationPositions = min($remainingPositions, self::MAX_BYTES_EXPONENT);
                $multiplier = 10 ** $iterationPositions;
                $bytes = self::multiplyBytesByInt($bytes, $multiplier);
                $remainingPositions -= $iterationPositions;
            }
        } else {
            // Negative positions: divide by 10^(-positions) with HALF_UP rounding
            $remainingPositions = -$positions;
            $finalRemainder = 0;

            while ($remainingPositions > 0) {
                $iterationPositions = min($remainingPositions, self::MAX_BYTES_EXPONENT);
                $divisor = 10 ** $iterationPositions;
                [$bytes, $remainder] = self::divideBytesByInt($bytes, $divisor);

                // Keep track of final remainder for rounding
                if ($remainingPositions === $iterationPositions) {
                    $finalRemainder = $remainder;
                    $finalDivisor = $divisor;
                }

                $remainingPositions -= $iterationPositions;
            }

            // Apply HALF_UP rounding: if remainder >= divisor/2, round up
            if (isset($finalDivisor) && $finalRemainder >= $finalDivisor / 2) {
                $bytes = self::addBytesInt($bytes, 1);
            }
        }

        array_unshift($bytes, 0);
        if ($isNegative && $bytes !== [0]) {
            $bytes = self::toTwosComplement($bytes);
        }

        return self::fromBytes(self::toBytesString($bytes));
    }

    public function toBytes(?int $padLength = null): string
    {
        $bytes = $this->bytes;
        if ($padLength !== null) {
            $paddingByte = $this->isNegative() ? "\xFF" : "\x00";
            $bytes = str_pad($bytes, $padLength, $paddingByte, STR_PAD_LEFT);
        }

        return $bytes;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function toInteger(): int
    {
        if (strlen($this->bytes) > 8) {
            throw new InvalidArgumentException('Cannot convert to integer: number of bytes exceeds 8');
        }

        // Unpack as signed 64-bit integer (big-endian)
        $result = unpack('J', $this->toBytes(8));
        assert($result !== false && is_int($result[1]), 'Failed to unpack integer bytes');
        return $result[1];
    }

    public function isNegative(): bool
    {
        return self::isBytesNegative($this->bytes);
    }

    private static function isBytesNegative(string $bytes): bool
    {
        return $bytes !== '' && (ord($bytes[0]) & 0x80) !== 0;
    }

    /**
     * @return array<int>
     */
    private static function toBytesArray(string $bytes): array
    {
        /** @var array<int> $unpackResult */
        $unpackResult = unpack('C*', $bytes);
        assert(is_array($unpackResult));

        return array_values($unpackResult);
    }

    /**
     * @param array<int> $bytes
     */
    private static function toBytesString(array $bytes): string
    {
        return pack('C*', ...$bytes);
    }

    private static function trimBytesString(string $packedBytes): string
    {
        $isNegative = self::isBytesNegative($packedBytes);
        $trimByte = $isNegative ? "\xFF" : "\x00";
        $trimmed = ltrim($packedBytes, $trimByte);
        if (self::isBytesNegative($trimmed) !== $isNegative) {
            $trimmed = $trimByte . $trimmed;
        }

        return $trimmed === '' ? "\x00" : $trimmed;
    }

    /**
     * Convert two's complement bytes to absolute value (invert bits and add 1)
     *
     * @param array<int> $bytes
     * @return array<int>
     */
    private static function toTwosComplement(array $bytes): array
    {
        $i = count($bytes) - 1;
        while ($i >= 0) {
            $newByte = ($bytes[$i] ^ 0xFF) + 1;
            $bytes[$i] = $newByte & 0xFF;
            $i--;
            if ($newByte <= 0xFF) {
                break;
            }
        }

        while ($i >= 0) {
            $bytes[$i] = $bytes[$i] ^ 0xFF;
            $i--;
        }

        return $bytes;
    }

    /**
     * Multiply byte array by an integer value using carry arithmetic
     *
     * @param array<int> $bytes
     * @return array<int>
     */
    private static function multiplyBytesByInt(array $bytes, int $multiplier): array
    {
        $carry = 0;

        // Process from least significant byte (rightmost) to most significant
        for ($i = count($bytes) - 1; $i >= 0; $i--) {
            $product = $bytes[$i] * $multiplier + $carry;
            $bytes[$i] = $product & 0xFF;  // Keep only low 8 bits
            $carry = $product >> 8;        // High bits become carry
        }

        // If there's still carry, prepend new bytes
        while ($carry > 0) {
            array_unshift($bytes, $carry & 0xFF);
            $carry >>= 8;
        }

        return $bytes;
    }

    /**
     * Divide byte array by an integer value and return both quotient and remainder
     *
     * @param array<int> $bytes
     * @return array{array<int>, int}
     */
    private static function divideBytesByInt(array $bytes, int $divisor): array
    {
        $remainder = 0;

        // Process from most significant byte (leftmost) to least significant
        for ($i = 0; $i < count($bytes); $i++) {
            $dividend = ($remainder << 8) + $bytes[$i];
            $bytes[$i] = intdiv($dividend, $divisor);
            $remainder = $dividend % $divisor;
        }

        $firstNonZero = 0;
        // Remove leading zero bytes
        while ($firstNonZero < count($bytes) && $bytes[$firstNonZero] === 0) {
            $firstNonZero++;
        }
        $bytes = array_slice($bytes, $firstNonZero);

        return [$bytes, $remainder];
    }

    /**
     * Add a small integer value to a byte array using carry arithmetic
     *
     * @param array<int> $bytes
     * @return array<int>
     */
    private static function addBytesInt(array $bytes, int $value): array
    {
        $carry = $value;

        // Process from least significant byte (rightmost) to most significant
        for ($i = count($bytes) - 1; $i >= 0 && $carry > 0; $i--) {
            $sum = $bytes[$i] + $carry;
            $bytes[$i] = $sum & 0xFF;  // Keep only low 8 bits
            $carry = $sum >> 8;        // High bits become carry
        }

        // If there's still carry, prepend new bytes
        while ($carry > 0) {
            array_unshift($bytes, $carry & 0xFF);
            $carry >>= 8;
        }

        return $bytes;
    }
}
