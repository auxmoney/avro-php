<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;
use Auxmoney\Avro\Exceptions\DataMismatchException;
use Auxmoney\Avro\IO\WritableStringBuffer;
use Countable;
use Generator;
use Traversable;

class MapWriter implements WriterInterface
{
    public function __construct(
        private readonly WriterInterface $valueWriter,
        private readonly BinaryEncoder $encoder,
        private readonly int $blockCount,
        private readonly bool $writeBlockSize,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        assert(
            is_array($datum) || ($datum instanceof Traversable && $datum instanceof Countable),
            'MapWriter expects an array or countable traversable, got ' . gettype($datum),
        );
        /** @var array<string, mixed>|(Traversable<string, mixed>&Countable) $datum */

        $this->writeBlockSize ? $this->writeWithBlockSizes($datum, $stream) : $this->writeWithoutBlockSizes($datum, $stream);
        $this->encoder->writeLong($stream, 0);
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        if (!is_array($datum) && (!$datum instanceof Traversable || !$datum instanceof Countable)) {
            $context?->addError('expected array or countable traversable, got ' . gettype($datum));
            return false;
        }

        $valid = true;
        foreach ($datum as $key => $item) {
            if (!is_string($key)) {
                $context?->addError('expected string key, got ' . gettype($key));
                $valid = false;
                continue;
            }

            $context?->pushPath("[{$key}]");
            $valid = $valid && $this->valueWriter->validate($item, $context);
            $context?->popPath();
        }

        return $valid;
    }

    /**
     * @param array<string, mixed>|(Traversable<string, mixed>&Countable) $datum
     * @throws DataMismatchException
     */
    private function writeWithBlockSizes(array|(Traversable&Countable) $datum, WritableStreamInterface $stream): void
    {
        foreach ($this->getBlocksGenerator($datum) as $block) {
            $this->encoder->writeLong($stream, -count($block));
            if (count($block) === 0) {
                continue;
            }

            $buffer = new WritableStringBuffer();
            foreach ($block as $key => $item) {
                $this->encoder->writeString($buffer, $key);
                $this->valueWriter->write($item, $buffer);
            }

            $this->encoder->writeString($stream, $buffer->__toString());
        }
    }

    /**
     * @param array<string, mixed>|(Traversable<string, mixed>&Countable) $datum
     * @throws DataMismatchException
     */
    private function writeWithoutBlockSizes(array|(Traversable&Countable) $datum, WritableStreamInterface $stream): void
    {
        foreach ($this->getBlocksGenerator($datum) as $block) {
            $this->encoder->writeLong($stream, count($block));
            foreach ($block as $key => $item) {
                $this->encoder->writeString($stream, $key);
                $this->valueWriter->write($item, $stream);
            }
        }
    }

    /**
     * @param array<string, mixed>|(Traversable<string, mixed>&Countable) $datum
     * @return Generator<array<string, mixed>|(Traversable<string, mixed>&Countable)>
     */
    private function getBlocksGenerator(array|(Traversable&Countable) $datum): Generator
    {
        if ($this->blockCount <= 0) {
            if (count($datum) > 0) {
                yield $datum;
            }
            return;
        }

        $block = [];
        foreach ($datum as $key => $item) {
            $block[$key] = $item;

            if (count($block) >= $this->blockCount) {
                yield $block;
                $block = [];
            }
        }

        if (!empty($block)) {
            yield $block;
        }

        yield [];
    }
}
