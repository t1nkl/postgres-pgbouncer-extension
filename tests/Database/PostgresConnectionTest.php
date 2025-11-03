<?php

declare(strict_types=1);

namespace Tests\Database;

use Carbon\Carbon;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use PostgresPgbouncerExtension\Database\PostgresConnection;

class PostgresConnectionTest extends TestCase
{
    private function makeConnection(): PostgresConnection
    {
        // We can pass null PDO for these unit-level method tests.
        return new PostgresConnection(null, 'testing', '', []);
    }

    public function testPrepareBindingsFormatsDateTimeAndBooleansAndLeavesOthersUntouched(): void
    {
        $conn = $this->makeConnection();
        $dt = new DateTimeImmutable('2020-01-02 03:04:05');

        $input = [
            'when' => $dt,
            'flag_true' => true,
            'flag_false' => false,
            'str' => 'foo',
            'null' => null,
            'float' => 1.23,
            'int' => 10,
            'numeric_string' => '123',
            'array' => ['a' => 1],
        ];

        $out = $conn->prepareBindings($input);

        // Date should be formatted using grammar default: 'Y-m-d H:i:s'
        $this->assertIsString($out['when']);
        $this->assertSame('2020-01-02 03:04:05', $out['when']);

        $this->assertSame('true', $out['flag_true']);
        $this->assertSame('false', $out['flag_false']);

        // Other types should remain unchanged
        $this->assertSame('foo', $out['str']);
        $this->assertNull($out['null']);
        $this->assertSame(1.23, $out['float']);
        $this->assertSame(10, $out['int']);
        $this->assertSame('123', $out['numeric_string']);
        $this->assertSame(['a' => 1], $out['array']);

        // Order should be preserved by associative array behavior
        $this->assertSame(array_keys($input), array_keys($out));
    }

    public function testPrepareBindingsDropsMicrosecondsAndIgnoresTimezoneFormatting(): void
    {
        $conn = $this->makeConnection();
        $dt = new DateTimeImmutable('2020-01-02 03:04:05.987654', new DateTimeZone('Europe/Kyiv'));

        $out = $conn->prepareBindings(['dt' => $dt]);
        // Should be formatted to 'Y-m-d H:i:s' (no microseconds, no timezone)
        $this->assertSame('2020-01-02 03:04:05', $out['dt']);
    }

    public function testPrepareBindingsDoesNotTouchNestedArraysOrPlainObjects(): void
    {
        $conn = $this->makeConnection();
        $obj = (object) ['a' => true];
        $nested = ['x' => ['flag' => true]];

        $out = $conn->prepareBindings(['obj' => $obj, 'nested' => $nested]);

        $this->assertSame($obj, $out['obj']);
        $this->assertSame($nested, $out['nested']);
    }

    public function testBindValuesBindsByTypeForPositionalKeys(): void
    {
        $conn = $this->makeConnection();

        $stmt = $this->createMock(PDOStatement::class);
        $calls = [];
        // Expect positional bindings: index + 1 starts from 1
        $stmt->expects($this->exactly(3))
            ->method('bindValue')
            ->willReturnCallback(function ($key, $value, $type) use (&$calls) {
                $calls[] = [$key, $value, $type];
                return true;
            });

        $resource = fopen('php://temp', 'rb');
        $conn->bindValues($stmt, [123, 'abc', $resource]);

        $this->assertCount(3, $calls);
        $this->assertSame([1, 123, PDO::PARAM_INT], [$calls[0][0], $calls[0][1], $calls[0][2]]);
        $this->assertSame([2, 'abc', PDO::PARAM_STR], [$calls[1][0], $calls[1][1], $calls[1][2]]);
        $this->assertSame(3, $calls[2][0]);
        $this->assertTrue(is_resource($calls[2][1]));
        $this->assertSame(PDO::PARAM_LOB, $calls[2][2]);

        if (is_resource($resource)) {
            fclose($resource);
        }
    }

    public function testBindValuesBindsByTypeForNamedKeys(): void
    {
        $conn = $this->makeConnection();

        $stmt = $this->createMock(PDOStatement::class);
        $calls = [];
        $stmt->expects($this->exactly(3))
            ->method('bindValue')
            ->willReturnCallback(function ($key, $value, $type) use (&$calls) {
                $calls[] = [$key, $value, $type];
                return true;
            });

        $r = fopen('php://temp', 'rb');
        $conn->bindValues($stmt, [
            ':id' => 99,
            ':name' => 'john',
            ':blob' => $r,
        ]);

        $this->assertCount(3, $calls);
        $this->assertSame([':id', 99, PDO::PARAM_INT], [$calls[0][0], $calls[0][1], $calls[0][2]]);
        $this->assertSame([':name', 'john', PDO::PARAM_STR], [$calls[1][0], $calls[1][1], $calls[1][2]]);
        $this->assertSame(':blob', $calls[2][0]);
        $this->assertTrue(is_resource($calls[2][1]));
        $this->assertSame(PDO::PARAM_LOB, $calls[2][2]);

        if (is_resource($r)) {
            fclose($r);
        }
    }

    public function testBindValuesNumericStringRemainsString(): void
    {
        $conn = $this->makeConnection();

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(1, '42', PDO::PARAM_STR)
            ->willReturn(true);

        $conn->bindValues($stmt, ['42']);
    }

    public function testBindValuesNullBindsAsStringByDefault(): void
    {
        $conn = $this->makeConnection();
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(1, null, PDO::PARAM_STR)
            ->willReturn(true);
        $conn->bindValues($stmt, [null]);
    }

    public function testBindValuesNegativeAndLargeIntegersBindAsInt(): void
    {
        $conn = $this->makeConnection();
        $stmt = $this->createMock(PDOStatement::class);
        $calls = [];
        $stmt->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnCallback(function ($key, $value, $type) use (&$calls) {
                $calls[] = [$key, $value, $type];
                return true;
            });
        $conn->bindValues($stmt, [-5, 2147483648]);
        $this->assertSame([1, -5, PDO::PARAM_INT], [$calls[0][0], $calls[0][1], $calls[0][2]]);
        $this->assertSame([2, 2147483648, PDO::PARAM_INT], [$calls[1][0], $calls[1][1], $calls[1][2]]);
    }

    public function testBindValuesNamedKeyWithoutColonIsUsedAsIs(): void
    {
        $conn = $this->makeConnection();
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with('id', 7, PDO::PARAM_INT)
            ->willReturn(true);
        $conn->bindValues($stmt, ['id' => 7]);
    }

    public function testBindValuesMixedNamedAndPositional(): void
    {
        $conn = $this->makeConnection();
        $stmt = $this->createMock(PDOStatement::class);
        $calls = [];
        $stmt->expects($this->exactly(3))
            ->method('bindValue')
            ->willReturnCallback(function ($key, $value, $type) use (&$calls) {
                $calls[] = [$key, $value, $type];
                return true;
            });

        $conn->bindValues($stmt, [10, ':name' => 'alice', 20]);

        $this->assertSame([1, 10, PDO::PARAM_INT], [$calls[0][0], $calls[0][1], $calls[0][2]]);
        $this->assertSame([':name', 'alice', PDO::PARAM_STR], [$calls[1][0], $calls[1][1], $calls[1][2]]);
        // The last positional item has numeric key 1, so bind index is 2
        $this->assertSame([2, 20, PDO::PARAM_INT], [$calls[2][0], $calls[2][1], $calls[2][2]]);
    }

    public function testPrepareBindingsFormatsMutableDateTime(): void
    {
        $conn = $this->makeConnection();
        $dt = new DateTimeImmutable('2021-05-06 07:08:09.123456');
        $out = $conn->prepareBindings(['dt' => $dt]);
        $this->assertSame('2021-05-06 07:08:09', $out['dt']);
    }

    public function testPrepareBindingsFormatsCarbonInstance(): void
    {
        $conn = $this->makeConnection();
        $carbon = Carbon::parse('1999-12-31 23:59:59.999999', 'UTC');
        $out = $conn->prepareBindings(['c' => $carbon]);
        $this->assertSame('1999-12-31 23:59:59', $out['c']);
    }

    public function testPrepareBindingsPreservesNumericIndexes(): void
    {
        $conn = $this->makeConnection();
        $dt = new DateTimeImmutable('2020-01-01 00:00:00');
        $bindings = [0 => $dt, 2 => true];
        $out = $conn->prepareBindings($bindings);
        $this->assertSame(['2020-01-01 00:00:00', '2' => 'true'], $out);
        $this->assertArrayHasKey(0, $out);
        $this->assertArrayHasKey(2, $out);
    }

    public function testBindValuesBindsFloatsAsString(): void
    {
        $conn = $this->makeConnection();
        $stmt = $this->createMock(PDOStatement::class);
        $calls = [];
        $stmt->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnCallback(function ($key, $value, $type) use (&$calls) {
                $calls[] = [$key, $value, $type];
                return true;
            });
        $conn->bindValues($stmt, [42.0, 3.14]);
        $this->assertSame([1, 42.0, PDO::PARAM_STR], [$calls[0][0], $calls[0][1], $calls[0][2]]);
        $this->assertSame([2, 3.14, PDO::PARAM_STR], [$calls[1][0], $calls[1][1], $calls[1][2]]);
    }

    public function testBindValuesBindsBooleansAsStringType(): void
    {
        $conn = $this->makeConnection();
        $stmt = $this->createMock(PDOStatement::class);
        $calls = [];
        $stmt->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnCallback(function ($key, $value, $type) use (&$calls) {
                $calls[] = [$key, $value, $type];
                return true;
            });
        $conn->bindValues($stmt, [true, false]);
        $this->assertSame([1, true, PDO::PARAM_STR], [$calls[0][0], $calls[0][1], $calls[0][2]]);
        $this->assertSame([2, false, PDO::PARAM_STR], [$calls[1][0], $calls[1][1], $calls[1][2]]);
    }

    public function testBindValuesNumericKeysOffsetToOneBased(): void
    {
        $conn = $this->makeConnection();
        $stmt = $this->createMock(PDOStatement::class);
        $calls = [];
        $stmt->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnCallback(function ($key, $value, $type) use (&$calls) {
                $calls[] = [$key, $value, $type];
                return true;
            });
        // Non-sequential numeric keys; bind index should be key + 1
        $conn->bindValues($stmt, [2 => 'a', 5 => 7]);
        $this->assertSame([3, 'a', PDO::PARAM_STR], [$calls[0][0], $calls[0][1], $calls[0][2]]);
        $this->assertSame([6, 7, PDO::PARAM_INT], [$calls[1][0], $calls[1][1], $calls[1][2]]);
    }

    public function testBindValuesWithEmptyBindingsMakesNoCalls(): void
    {
        $conn = $this->makeConnection();
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->never())->method('bindValue');
        $conn->bindValues($stmt, []);
        $this->assertTrue(true); // just to mark the assertion
    }

    public function testBindValuesBindsTmpfileResourceAsLob(): void
    {
        $conn = $this->makeConnection();
        $stmt = $this->createMock(PDOStatement::class);
        $calls = [];
        $stmt->expects($this->once())
            ->method('bindValue')
            ->willReturnCallback(function ($key, $value, $type) use (&$calls) {
                $calls[] = [$key, $value, $type];
                return true;
            });
        $res = tmpfile();
        $conn->bindValues($stmt, [$res]);
        $this->assertSame([1, $res, PDO::PARAM_LOB], [$calls[0][0], $calls[0][1], $calls[0][2]]);
        if (is_resource($res)) {
            fclose($res);
        }
    }
}
