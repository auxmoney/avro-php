<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

use Auxmoney\Avro\Exceptions\InvalidSchemaException;

interface AvroFactoryInterface
{
    /**
     * @throws InvalidSchemaException
     */
    public function createWriter(string $schema): WriterInterface;

    /**
     * @throws InvalidSchemaException
     */
    public function createReader(string $writerSchema, ?string $readerSchema = null): ReaderInterface;

    public function createStringBuffer(): StringBufferInterface;

    public function createReadableStreamFromString(string $string): ReadableStreamInterface;
}
