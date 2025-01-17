<?php

declare(strict_types=1);

namespace Auxmoney\Avro;

use Apache\Avro\Schema\AvroSchema;
use Auxmoney\Avro\Adapters\Apache\Datum\AvroIODatumReader;
use Auxmoney\Avro\Adapters\Apache\Datum\AvroIODatumWriter;
use Auxmoney\Avro\Contracts\AvroFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Contracts\StringBufferInterface;
use Auxmoney\Avro\Contracts\WriterInterface;
use Auxmoney\Avro\IO\ReadableStringBuffer;
use Auxmoney\Avro\IO\Reader;
use Auxmoney\Avro\IO\WritableStringBuffer;
use Auxmoney\Avro\IO\Writer;

readonly class AvroFactory implements AvroFactoryInterface
{
    /** @var array<string, LogicalTypeFactoryInterface> */
    private array $logicalTypeFactories;

    /**
     * @param iterable<LogicalTypeFactoryInterface> $logicalTypeFactories
     */
    private function __construct(iterable $logicalTypeFactories)
    {
        $keyedLogicalTypeFactories = [];
        foreach ($logicalTypeFactories as $logicalTypeFactory) {
            $keyedLogicalTypeFactories[$logicalTypeFactory->getName()] = $logicalTypeFactory;
        }

        $this->logicalTypeFactories = $keyedLogicalTypeFactories;
    }

    public function createWriter(string $schema): WriterInterface
    {
        $parsedSchema = AvroSchema::parse($schema);
        $datumWriter = new AvroIODatumWriter($this->logicalTypeFactories);
        return new Writer($parsedSchema, $datumWriter);
    }

    public function createReader(string $writerSchema, string $readerSchema = null): ReaderInterface
    {
        $parsedWriterSchema = AvroSchema::parse($writerSchema);
        $parsedReaderSchema = $readerSchema === null ? $parsedWriterSchema : AvroSchema::parse($readerSchema);
        $datumReader = new AvroIODatumReader($this->logicalTypeFactories);
        return new Reader($parsedWriterSchema, $parsedReaderSchema, $datumReader);
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
        return new self($logicalTypeFactories);
    }
}
