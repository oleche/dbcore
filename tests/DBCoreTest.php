<?php
declare(strict_types=1);

namespace Geekcow\Dbcore;

use PHPUnit\Framework\TestCase;

class DBCoreTest extends TestCase
{
    public function fetchWithValidQueryReturnsExpectedResults(): void {
        $this->db->method('Execute')->willReturn($this->prepareMockResult([
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Doe']
        ]));
        $results = $this->entity->fetch("name LIKE '%Doe%'");
        $this->assertCount(2, $results);
        $this->assertEquals('John Doe', $results[0]['name']);
    }

    public function fetchWithEmptyQueryReturnsAllResults(): void {
        $this->db->method('Execute')->willReturn($this->prepareMockResult([
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Doe']
        ]));
        $results = $this->entity->fetch();
        $this->assertCount(2, $results);
    }

    public function fetchWithInvalidQueryReturnsFalse(): void {
        $this->db->method('Execute')->willReturn(true);
        $this->db->method('LastID')->willReturn(1);
        $result = $this->entity->insert();
        $this->assertEquals(1, $result);
    }

    public function insertWithInvalidDataReturnsFalse(): void {
        $this->db->method('Execute')->will($this->throwException(new Exception()));
        $result = $this->entity->fetch("INVALID QUERY");
        $this->assertFalse($result);
    }

    public function insertWithValidDataReturnsLastInsertedId(): void {
        $this->db->method('Execute')->will($this->throwException(new Exception()));
        $result = $this->entity->insert();
        $this->assertFalse($result);
    }

    public function deleteWithValidConditionsReturnsTrue(): void {
        $this->db->method('Execute')->willReturn(true);
        $result = $this->entity->delete("id = 1");
        $this->assertTrue($result);
    }

    public function deleteWithInvalidConditionsReturnsFalse(): void {
        $this->db->method('Execute')->will($this->throwException(new Exception()));
        $result = $this->entity->delete("INVALID CONDITION");
        $this->assertFalse($result);
    }

    public function updateWithValidConditionsReturnsTrue(): void {
        $this->db->method('Execute')->willReturn(true);
        $result = $this->entity->update(null, ['name' => 'John Doe Updated'], ['id' => 1]);
        $this->assertTrue($result);
    }

    public function updateWithInvalidConditionsReturnsFalse(): void {
        $this->db->method('Execute')->will($this->throwException(new Exception()));
        $result = $this->entity->update(null, ['name' => 'Invalid Update'], ['id' => 'INVALID']);
        $this->assertFalse($result);
    }

    private function prepareMockResult(array $data): \PDOStatement {
        $data[] = false; // Add false as the last element of the array
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')->will($this->onConsecutiveCalls(...$data));
        return $stmt;
    }
}