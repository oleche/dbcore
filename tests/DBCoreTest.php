<?php
declare(strict_types=1);

namespace Geekcow\Dbcore;

use PHPUnit\Framework\TestCase;
use Exception;

class DBCoreTest extends TestCase
{
    private $dbCore;
    private $pdoMock;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(DataBase::class);
        $this->dbCore = new DBCore($this->pdoMock, 'test_table', ['id', 'name'], ['id']);
    }

    public function testFetchWithValidQueryReturnsExpectedResults(): void
    {
        $this->pdoMock->method('Execute')->willReturn($this->prepareMockResult([
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Doe']
        ]));
        $results = $this->dbCore->fetch("name LIKE '%Doe%'");
        $this->assertCount(2, $results);
        $this->assertEquals('John Doe', $results[0]['name']);
    }

    public function testFetchWithNoResultsReturnsEmptyArray(): void
    {
        $this->pdoMock->method('Execute')->willReturn($this->prepareMockResult([]));
        $results = $this->dbCore->fetch("name = 'Nonexistent'");
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testInsertWithValidDataReturnsLastInsertedId(): void
    {
        $this->pdoMock->method('Execute')->willReturn(true);
        $this->pdoMock->method('LastID')->willReturn(1);
        $result = $this->dbCore->insert(['name' => 'New Entry']);
        $this->assertEquals(1, $result);
    }

    public function insertWithInvalidDataThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->pdoMock->method('Execute')->will($this->throwException(new Exception()));
        $this->dbCore->insert(['invalid_column' => 'value']);
    }

    public function updateWithValidConditionsReturnsTrue(): void
    {
        $this->pdoMock->method('Execute')->willReturn(true);
        $result = $this->dbCore->update("id = 1", ['name' => 'Updated Name']);
        $this->assertTrue($result);
    }

    public function updateWithInvalidConditionsReturnsFalse(): void
    {
        $this->pdoMock->method('Execute')->will($this->throwException(new Exception()));
        $result = $this->dbCore->update("id = 999", ['name' => 'Ghost Update']);
    }

    private function prepareMockResult(array $data): array
    {
        // Convert the input array into a format that mimics the database fetch operation.
        // This example assumes a fetch style that returns an associative array for each row.
        $result = [];
        foreach ($data as $row) {
            $result[] = (object)$row; // Convert each associative array to an object
        }
        return $result;
    }
}