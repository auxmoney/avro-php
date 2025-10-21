<?php

declare(strict_types=1);

namespace Auxmoney\Avro;

use Auxmoney\Avro\Contracts\AvroFactoryInterface;
use Auxmoney\Avro\Contracts\Options;
use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Contracts\StringBufferInterface;
use Auxmoney\Avro\Contracts\WriterInterface;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\IO\ReadableStringBuffer;
use Auxmoney\Avro\IO\WritableStringBuffer;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Support\DefaultValueConverter;
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

    public static function create(Options $options = new Options()): AvroFactoryInterface
    {
        $logicalTypeResolver = new LogicalTypeResolver($options->logicalTypeFactories);
        $schemaHelper = new SchemaHelper($logicalTypeResolver);
        $defaultValueConverter = new DefaultValueConverter($schemaHelper);
        $writerFactory = new WriterFactory(new BinaryEncoder(), $schemaHelper, $options);
        $readerFactory = new ReaderFactory(new BinaryDecoder(), $schemaHelper, $defaultValueConverter);

        return new self($writerFactory, $readerFactory);
    }
}
