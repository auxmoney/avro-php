<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\ValueObject;

use Auxmoney\Avro\ValueObject\TimeOfDay;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TimeOfDayTest extends TestCase
{
    public function testConstructorWithValidMicroseconds(): void
    {
        $timeOfDay = new TimeOfDay(43200000000); // 12:00:00.000000

        $this->assertSame(43200000000, $timeOfDay->totalMicroseconds);
        $this->assertSame(12, $timeOfDay->getHours());
        $this->assertSame(0, $timeOfDay->getMinutes());
        $this->assertSame(0, $timeOfDay->getSeconds());
        $this->assertSame(0, $timeOfDay->getMilliseconds());
        $this->assertSame(0, $timeOfDay->getMicroseconds());
    }

    public function testConstructorWithMidnight(): void
    {
        $timeOfDay = new TimeOfDay(0);

        $this->assertSame(0, $timeOfDay->totalMicroseconds);
        $this->assertSame(0, $timeOfDay->getHours());
        $this->assertSame(0, $timeOfDay->getMinutes());
        $this->assertSame(0, $timeOfDay->getSeconds());
        $this->assertSame(0, $timeOfDay->getMilliseconds());
        $this->assertSame(0, $timeOfDay->getMicroseconds());
    }

    public function testConstructorWithMaxValidTime(): void
    {
        $timeOfDay = new TimeOfDay(86399999999); // 23:59:59.999999

        $this->assertSame(86399999999, $timeOfDay->totalMicroseconds);
        $this->assertSame(23, $timeOfDay->getHours());
        $this->assertSame(59, $timeOfDay->getMinutes());
        $this->assertSame(59, $timeOfDay->getSeconds());
        $this->assertSame(999, $timeOfDay->getMilliseconds());
        $this->assertSame(999999, $timeOfDay->getMicroseconds());
    }

    public function testConstructorWithNegativeMicrosecondsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total microseconds must be between 0 and 86399999999 (midnight to 23:59:59.999999)');

        new TimeOfDay(-1);
    }

    public function testConstructorWithTooLargeMicrosecondsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total microseconds must be between 0 and 86399999999 (midnight to 23:59:59.999999)');

        new TimeOfDay(86400000000); // Exactly midnight of next day
    }

    public function testFromComponentsWithValidValues(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(14, 30, 45, 123, 456);

        $this->assertSame(14, $timeOfDay->getHours());
        $this->assertSame(30, $timeOfDay->getMinutes());
        $this->assertSame(45, $timeOfDay->getSeconds());
        $this->assertSame(123, $timeOfDay->getMilliseconds());
        $this->assertSame(123456, $timeOfDay->getMicroseconds());
    }

    public function testFromComponentsWithOnlyHours(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(9);

        $this->assertSame(9, $timeOfDay->getHours());
        $this->assertSame(0, $timeOfDay->getMinutes());
        $this->assertSame(0, $timeOfDay->getSeconds());
        $this->assertSame(0, $timeOfDay->getMilliseconds());
        $this->assertSame(0, $timeOfDay->getMicroseconds());
    }

    public function testFromComponentsWithMidnight(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(0, 0, 0, 0, 0);

        $this->assertSame(0, $timeOfDay->getHours());
        $this->assertSame(0, $timeOfDay->getMinutes());
        $this->assertSame(0, $timeOfDay->getSeconds());
        $this->assertSame(0, $timeOfDay->getMilliseconds());
        $this->assertSame(0, $timeOfDay->getMicroseconds());
        $this->assertSame(0, $timeOfDay->totalMicroseconds);
    }

    public function testFromComponentsWithMaxValidTime(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(23, 59, 59, 999, 999);

        $this->assertSame(23, $timeOfDay->getHours());
        $this->assertSame(59, $timeOfDay->getMinutes());
        $this->assertSame(59, $timeOfDay->getSeconds());
        $this->assertSame(999, $timeOfDay->getMilliseconds());
        $this->assertSame(999999, $timeOfDay->getMicroseconds());
        $this->assertSame(86399999999, $timeOfDay->totalMicroseconds);
    }

    #[DataProvider('invalidHoursProvider')]
    public function testFromComponentsWithInvalidHoursThrowsException(int $hours): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hours must be between 0 and 23');

        TimeOfDay::fromComponents($hours);
    }

    public static function invalidHoursProvider(): array
    {
        return [
            [-1],
            [24],
            [25],
            [-10],
        ];
    }

    #[DataProvider('invalidMinutesProvider')]
    public function testFromComponentsWithInvalidMinutesThrowsException(int $minutes): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minutes must be between 0 and 59');

        TimeOfDay::fromComponents(12, $minutes);
    }

    public static function invalidMinutesProvider(): array
    {
        return [
            [-1],
            [60],
            [61],
            [-10],
        ];
    }

    #[DataProvider('invalidSecondsProvider')]
    public function testFromComponentsWithInvalidSecondsThrowsException(int $seconds): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Seconds must be between 0 and 59');

        TimeOfDay::fromComponents(12, 30, $seconds);
    }

    public static function invalidSecondsProvider(): array
    {
        return [
            [-1],
            [60],
            [61],
            [-10],
        ];
    }

    #[DataProvider('invalidMillisecondsProvider')]
    public function testFromComponentsWithInvalidMillisecondsThrowsException(int $milliseconds): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Milliseconds must be between 0 and 999');

        TimeOfDay::fromComponents(12, 30, 45, $milliseconds);
    }

    public static function invalidMillisecondsProvider(): array
    {
        return [
            [-1],
            [1000],
            [1001],
            [-10],
        ];
    }

    #[DataProvider('invalidMicrosecondsProvider')]
    public function testFromComponentsWithInvalidMicrosecondsThrowsException(int $microseconds): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Microseconds must be between 0 and 999');

        TimeOfDay::fromComponents(12, 30, 45, 123, $microseconds);
    }

    public static function invalidMicrosecondsProvider(): array
    {
        return [
            [-1],
            [1000],
            [1001],
            [-10],
        ];
    }

    public function testFromDateTimeWithDateTime(): void
    {
        $dateTime = DateTime::createFromFormat('H:i:s.u', '14:30:45.123456');
        $this->assertInstanceOf(DateTime::class, $dateTime);

        $timeOfDay = TimeOfDay::fromDateTime($dateTime);

        $this->assertSame(14, $timeOfDay->getHours());
        $this->assertSame(30, $timeOfDay->getMinutes());
        $this->assertSame(45, $timeOfDay->getSeconds());
        $this->assertSame(123, $timeOfDay->getMilliseconds());
        $this->assertSame(123456, $timeOfDay->getMicroseconds());
    }

    public function testFromDateTimeWithDateTimeImmutable(): void
    {
        $dateTime = DateTimeImmutable::createFromFormat('H:i:s.u', '09:15:30.500250');
        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);

        $timeOfDay = TimeOfDay::fromDateTime($dateTime);

        $this->assertSame(9, $timeOfDay->getHours());
        $this->assertSame(15, $timeOfDay->getMinutes());
        $this->assertSame(30, $timeOfDay->getSeconds());
        $this->assertSame(500, $timeOfDay->getMilliseconds());
        $this->assertSame(500250, $timeOfDay->getMicroseconds());
    }

    public function testFromDateTimeWithMidnight(): void
    {
        $dateTime = new DateTime('00:00:00.000000');
        $timeOfDay = TimeOfDay::fromDateTime($dateTime);

        $this->assertSame(0, $timeOfDay->getHours());
        $this->assertSame(0, $timeOfDay->getMinutes());
        $this->assertSame(0, $timeOfDay->getSeconds());
        $this->assertSame(0, $timeOfDay->getMilliseconds());
        $this->assertSame(0, $timeOfDay->getMicroseconds());
    }

    public function testFromDateTimeWithAlmostMidnight(): void
    {
        $dateTime = DateTime::createFromFormat('H:i:s.u', '23:59:59.999999');
        $this->assertInstanceOf(DateTime::class, $dateTime);

        $timeOfDay = TimeOfDay::fromDateTime($dateTime);

        $this->assertSame(23, $timeOfDay->getHours());
        $this->assertSame(59, $timeOfDay->getMinutes());
        $this->assertSame(59, $timeOfDay->getSeconds());
        $this->assertSame(999, $timeOfDay->getMilliseconds());
        $this->assertSame(999999, $timeOfDay->getMicroseconds());
    }

    #[DataProvider('getterMethodsProvider')]
    public function testGetterMethods(int $totalMicroseconds, int $expectedHours, int $expectedMinutes, int $expectedSeconds, int $expectedMilliseconds, int $expectedMicroseconds): void
    {
        $timeOfDay = new TimeOfDay($totalMicroseconds);

        $this->assertSame($expectedHours, $timeOfDay->getHours());
        $this->assertSame($expectedMinutes, $timeOfDay->getMinutes());
        $this->assertSame($expectedSeconds, $timeOfDay->getSeconds());
        $this->assertSame($expectedMilliseconds, $timeOfDay->getMilliseconds());
        $this->assertSame($expectedMicroseconds, $timeOfDay->getMicroseconds());
    }

    public static function getterMethodsProvider(): array
    {
        return [
            'midnight' => [0, 0, 0, 0, 0, 0],
            'noon' => [43200000000, 12, 0, 0, 0, 0],
            'afternoon with microseconds' => [52245123456, 14, 30, 45, 123, 123456], // 14:30:45.123456
            'evening' => [72000000000, 20, 0, 0, 0, 0], // 20:00:00.000000
            'almost midnight' => [86399999999, 23, 59, 59, 999, 999999], // 23:59:59.999999
            'early morning' => [3723500750, 1, 2, 3, 500, 500750], // 01:02:03.500750
        ];
    }

    public function testGetTotalMilliseconds(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(14, 30, 45, 123, 456);
        $expectedMilliseconds = (14 * 3600 + 30 * 60 + 45) * 1000 + 123;

        $this->assertSame($expectedMilliseconds, $timeOfDay->getTotalMilliseconds());
    }

    public function testGetTotalMillisecondsWithMidnight(): void
    {
        $timeOfDay = new TimeOfDay(0);

        $this->assertSame(0, $timeOfDay->getTotalMilliseconds());
    }

    public function testGetTotalMillisecondsWithMaxValue(): void
    {
        $timeOfDay = new TimeOfDay(86399999999);
        $expectedMilliseconds = 86399999; // Last millisecond of the day

        $this->assertSame($expectedMilliseconds, $timeOfDay->getTotalMilliseconds());
    }

    #[DataProvider('toStringProvider')]
    public function testToString(int $totalMicroseconds, string $expected): void
    {
        $timeOfDay = new TimeOfDay($totalMicroseconds);

        $this->assertSame($expected, $timeOfDay->__toString());
        $this->assertSame($expected, (string) $timeOfDay);
    }

    public static function toStringProvider(): array
    {
        return [
            'midnight' => [0, '00:00:00.000000'],
            'noon' => [43200000000, '12:00:00.000000'],
            'afternoon with microseconds' => [52245123456, '14:30:45.123456'],
            'early morning' => [3723500750, '01:02:03.500750'],
            'almost midnight' => [86399999999, '23:59:59.999999'],
            'single digits' => [32523001002, '09:02:03.001002'],
        ];
    }

    public function testRoundTripFromComponentsToGetters(): void
    {
        $hours = 15;
        $minutes = 42;
        $seconds = 33;
        $milliseconds = 789;
        $microseconds = 123;

        $timeOfDay = TimeOfDay::fromComponents($hours, $minutes, $seconds, $milliseconds, $microseconds);

        $this->assertSame($hours, $timeOfDay->getHours());
        $this->assertSame($minutes, $timeOfDay->getMinutes());
        $this->assertSame($seconds, $timeOfDay->getSeconds());
        $this->assertSame($milliseconds, $timeOfDay->getMilliseconds());
        $this->assertSame($milliseconds * 1000 + $microseconds, $timeOfDay->getMicroseconds());
    }

    public function testRoundTripFromDateTimeToGetters(): void
    {
        $originalDateTime = DateTime::createFromFormat('H:i:s.u', '17:25:42.654321');
        $this->assertInstanceOf(DateTime::class, $originalDateTime);

        $timeOfDay = TimeOfDay::fromDateTime($originalDateTime);

        $this->assertSame(17, $timeOfDay->getHours());
        $this->assertSame(25, $timeOfDay->getMinutes());
        $this->assertSame(42, $timeOfDay->getSeconds());
        $this->assertSame(654, $timeOfDay->getMilliseconds());
        $this->assertSame(654321, $timeOfDay->getMicroseconds());
    }

    public function testTotalMicrosecondsCalculation(): void
    {
        $hours = 2;
        $minutes = 30;
        $seconds = 45;
        $milliseconds = 678;
        $microseconds = 910;

        $timeOfDay = TimeOfDay::fromComponents($hours, $minutes, $seconds, $milliseconds, $microseconds);

        $expectedTotal = ($hours * 3600 + $minutes * 60 + $seconds) * 1000000 + $milliseconds * 1000 + $microseconds;
        $this->assertSame($expectedTotal, $timeOfDay->totalMicroseconds);
    }

    public function testGetMicrosecondsVsGetMicrosecondComponent(): void
    {
        // Demonstrate the difference between the two methods
        $timeOfDay = TimeOfDay::fromComponents(10, 20, 30, 456, 789);
        
        // getMicroseconds() returns total microseconds within the second
        $this->assertSame(456789, $timeOfDay->getMicroseconds());
        
        // getMilliseconds() returns just the millisecond component
        $this->assertSame(456, $timeOfDay->getMilliseconds());
    }
}