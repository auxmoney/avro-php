<?php

// Simple test script for logical types
require_once __DIR__ . '/examples/autoload.php';

use Auxmoney\Avro\LogicalType\DecimalType;
use Auxmoney\Avro\LogicalType\UuidType;
use Auxmoney\Avro\LogicalType\DateType;
use Auxmoney\Avro\LogicalType\TimeMillisType;
use Auxmoney\Avro\LogicalType\DurationType;

echo "Testing logical types implementations...\n\n";

// Test Decimal
try {
    $decimal = new DecimalType(10, 2);
    $normalized = $decimal->normalize('123.45');
    $denormalized = $decimal->denormalize($normalized);
    echo "✓ Decimal: 123.45 -> normalized -> {$denormalized}\n";
} catch (Exception $e) {
    echo "✗ Decimal test failed: " . $e->getMessage() . "\n";
}

// Test UUID
try {
    $uuid = new UuidType();
    $testUuid = '550e8400-e29b-41d4-a716-446655440000';
    $normalized = $uuid->normalize($testUuid);
    $denormalized = $uuid->denormalize($normalized);
    echo "✓ UUID: {$testUuid} -> normalized -> {$denormalized}\n";
} catch (Exception $e) {
    echo "✗ UUID test failed: " . $e->getMessage() . "\n";
}

// Test Date
try {
    $date = new DateType();
    $testDate = new DateTime('2023-12-25');
    $normalized = $date->normalize($testDate);
    $denormalized = $date->denormalize($normalized);
    echo "✓ Date: " . $testDate->format('Y-m-d') . " -> {$normalized} days -> {$denormalized}\n";
} catch (Exception $e) {
    echo "✗ Date test failed: " . $e->getMessage() . "\n";
}

// Test Time Millis
try {
    $time = new TimeMillisType();
    $testTime = DateTime::createFromFormat('H:i:s.v', '14:30:25.123');
    $normalized = $time->normalize($testTime);
    $denormalized = $time->denormalize($normalized);
    echo "✓ Time Millis: " . $testTime->format('H:i:s.v') . " -> {$normalized} ms -> {$denormalized}\n";
} catch (Exception $e) {
    echo "✗ Time Millis test failed: " . $e->getMessage() . "\n";
}

// Test Duration
try {
    $duration = new DurationType();
    $testDuration = [12, 30, 86400000];
    $normalized = $duration->normalize($testDuration);
    $denormalized = $duration->denormalize($normalized);
    echo "✓ Duration: " . json_encode($testDuration) . " -> normalized -> " . json_encode($denormalized) . "\n";
} catch (Exception $e) {
    echo "✗ Duration test failed: " . $e->getMessage() . "\n";
}

echo "\nAll basic logical type tests completed!\n";