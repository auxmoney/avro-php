<?php

declare(strict_types=1);

namespace Auxmoney\Avro;

use Auxmoney\Avro\Contracts\AvroFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Contracts\StringBufferInterface;
use Auxmoney\Avro\Contracts\WriterInterface;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\IO\ReadableStringBuffer;
use Auxmoney\Avro\IO\WritableStringBuffer;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Support\LogicalTypeResolver;
use Auxmoney\Avro\Support\ReaderFactory;
use Auxmoney\Avro\Support\SchemaHelper;
use Auxmoney\Avro\Support\WriterFactory;

readonly class AvroFactory implements AvroFactoryInterface
{
    private function __construct(
        private WriterFactory $writerFactory,
        private ReaderFactory $readerFactory,
    ) {
    }

    public function createWriter(string $schema): WriterInterface
    {
        return $this->writerFactory->create($schema);
    }

    public function createReader(string $writerSchema, ?string $readerSchema = null): ReaderInterface
    {
        return $this->readerFactory->create($writerSchema, $readerSchema);
    }

    public function createStringBuffer(): StringBufferInterface
    {
        return new WritableStringBuffer();
    }

    public function createReadableStreamFromString(string $string): ReadableStreamInterface
    {
        return new ReadableStringBuffer($string);
    }

    /**
     * @param iterable<LogicalTypeFactoryInterface> $logicalTypeFactories
     */
    public static function create(iterable $logicalTypeFactories = []): AvroFactoryInterface
    {
        $logicalTypeResolver = new LogicalTypeResolver($logicalTypeFactories);
        $schemaHelper = new SchemaHelper($logicalTypeResolver);

        return new self(new WriterFactory(new BinaryEncoder(), $schemaHelper), new ReaderFactory(new BinaryDecoder(), $schemaHelper));
    }
}
