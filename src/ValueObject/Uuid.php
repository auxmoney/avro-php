<?php

declare(strict_types=1);

namespace Auxmoney\Avro\ValueObject;

use Auxmoney\Avro\Exceptions\InvalidArgumentException;

/**
 * Represents an AVRO UUID logical type value.
 *
 * A UUID (Universally Unique Identifier) is stored as 16 bytes of binary data
 * but can be represented as a string in the standard format:
 * xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
 *
 * This class provides a convenient object-oriented interface for working with
 * UUID values in AVRO serialization/deserialization contexts.
 *
 * @example
 * // Create from string format
 * $uuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');
 *
 * // Create from binary bytes
 * $bytes = hex2bin('550e8400e29b41d4a716446655440000');
 * $uuid = Uuid::fromBytes($bytes);
 *
 * // Convert to string
 * $stringValue = $uuid->toString();
 *
 * // Get binary bytes
 * $binaryValue = $uuid->toBytes();
 */
readonly class Uuid
{
    private function __construct(
        public string $bytes,
    ) {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgumentException('UUID bytes must be exactly 16 bytes long');
        }
    }

    /**
     * Creates a UUID from a 16-byte binary string.
     *
     * @param string $bytes The 16-byte binary representation of the UUID
     * @throws InvalidArgumentException if the bytes are not exactly 16 bytes long
     */
    public static function fromBytes(string $bytes): self
    {
        return new self($bytes);
    }

    /**
     * Creates a UUID from a string in the standard UUID format.
     *
     * @param string $uuidString The UUID string in format xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
     * @throws InvalidArgumentException if the string is not a valid UUID format
     */
    public static function fromString(string $uuidString): self
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuidString)) {
            throw new InvalidArgumentException('Invalid UUID format. Expected format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
        }

        // Remove hyphens and convert hex to binary
        $hex = str_replace('-', '', $uuidString);
        $bytes = hex2bin($hex);

        if ($bytes === false) {
            throw new InvalidArgumentException('Failed to convert UUID string to bytes');
        }

        return new self($bytes);
    }

    /**
     * Returns the UUID as a 16-byte binary string.
     *
     * @return string The 16-byte binary representation
     */
    public function toBytes(): string
    {
        return $this->bytes;
    }

    /**
     * Returns the UUID as a string in the standard format.
     *
     * @return string The UUID string in format xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
     */
    public function toString(): string
    {
        $hex = bin2hex($this->bytes);

        // Format as UUID: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
