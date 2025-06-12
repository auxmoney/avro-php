<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Integration;

use Generator;
use JsonException;
use RuntimeException;

class TestCasesLoader
{
    /**
     * @throws JsonException
     */
    public static function load(): Generator
    {
        $testCasesDir = __DIR__ . '/TestCases';
        $files = scandir($testCasesDir);
        if ($files === false) {
            throw new RuntimeException("Failed to read test cases directory: {$testCasesDir}");
        }

        foreach ($files as $file) {
            $filePath = $testCasesDir . '/' . $file;
            if (!is_file($filePath)) {
                continue;
            }

            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                throw new RuntimeException("Failed to open file: {$filePath}");
            }

            $lineNumber = 0;
            $schema = null;
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                if (trim($line) === '') {
                    continue;
                }

                $lineData = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                if (isset($lineData['schema'])) {
                    $schema = $lineData['schema'];
                    continue;
                }

                if ($schema === null) {
                    throw new RuntimeException("Schema not found in file: {$file} at line {$lineNumber}");
                }

                yield "File {$file} line {$lineNumber}" => [
                    'schema' => json_encode($schema),
                    'data' => $lineData['data'],
                    'hex' => $lineData['hex'],
                ];
            }
        }
    }
}
