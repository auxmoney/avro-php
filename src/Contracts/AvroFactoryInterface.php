<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

interface AvroFactoryInterface
{
    public function createWriter(string $schema): WriterInterface;

    public function createReader(string $writerSchema, string $readerSchema = null): ReaderInterface;

    public function createStringBuffer(): StringBufferInterface;

    public function createReadableStreamFromString(string $string): ReadableStreamInterface;
}
