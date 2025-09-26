<?php

declare(strict_types=1);

namespace Auxmoney\Avro;

use Auxmoney\Avro\Contracts\AvroFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\Options;
use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Contracts\StringBufferInterface;
use Auxmoney\Avro\Contracts\WriterInterface;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\IO\ReadableStringBuffer;
use Auxmoney\Avro\IO\WritableStringBuffer;
use Auxmoney\Avro\LogicalType\Factory\DateFactory;
use Auxmoney\Avro\LogicalType\Factory\DecimalFactory;
use Auxmoney\Avro\LogicalType\Factory\DurationFactory;
use Auxmoney\Avro\LogicalType\Factory\LocalTimestampMicrosFactory;
use Auxmoney\Avro\LogicalType\Factory\LocalTimestampMillisFactory;
use Auxmoney\Avro\LogicalType\Factory\TimeMicrosFactory;
use Auxmoney\Avro\LogicalType\Factory\TimeMillisFactory;
use Auxmoney\Avro\LogicalType\Factory\TimestampMicrosFactory;
use Auxmoney\Avro\LogicalType\Factory\TimestampMillisFactory;
use Auxmoney\Avro\LogicalType\Factory\UuidFactory;
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

    public static function create(Options $options = new Options()): AvroFactoryInterface
    {
        $logicalTypeFactories = $options->logicalTypeFactories ?? self::getDefaultLogicalTypeFactories();
        $logicalTypeResolver = new LogicalTypeResolver($logicalTypeFactories);
        $schemaHelper = new SchemaHelper($logicalTypeResolver);
        $writerFactory = new WriterFactory(new BinaryEncoder(), $schemaHelper, $options);
        $readerFactory = new ReaderFactory(new BinaryDecoder(), $schemaHelper);

        return new self($writerFactory, $readerFactory);
    }

    /**
     * @return array<string, LogicalTypeFactoryInterface>
     */
    public static function getDefaultLogicalTypeFactories(): array
    {
        return [
            'date' => new DateFactory(),
            'decimal' => new DecimalFactory(),
            'duration' => new DurationFactory(),
            'local-timestamp-micros' => new LocalTimestampMicrosFactory(),
            'local-timestamp-millis' => new LocalTimestampMillisFactory(),
            'time-micros' => new TimeMicrosFactory(),
            'time-millis' => new TimeMillisFactory(),
            'timestamp-micros' => new TimestampMicrosFactory(),
            'timestamp-millis' => new TimestampMillisFactory(),
            'uuid' => new UuidFactory(),
        ];
    }
}
