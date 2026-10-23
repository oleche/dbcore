<?php
declare(strict_types=1);

namespace Geekcow\Dbcore;

use PHPUnit\Framework\TestCase;

final class SearchyTest extends TestCase {
  public function testCanBeCreated(): void {
    $searchy = new Searchy();
    $this->assertInstanceOf(
      Searchy::class,
      $searchy
    );
  }

  public function testCanGenerateAQuery(): void {
    $searchy = new Searchy();
    $query = $searchy->assemblySearch(array('q'=>array("test-eq"=>"123")));
    $this->assertEquals(
      $query,
      "test = '123'"
    );
  }

  /**
    * @dataProvider comparatorsProvider
    */
  public function testValidComparators($comparator, $expected): void {
    $searchy = new Searchy();
    $query = $searchy->assemblySearch(array('q'=>array("test-$comparator"=>"123")));
    $this->assertEquals(
      $query,
      "test $expected '123'"
    );
  }

  public function testValidNotNull(): void {
    $searchy = new Searchy();
    $query = $searchy->assemblySearch(array('q'=>array("test-nn"=>"123")));
    $this->assertEquals(
      $query,
      "test IS NOT NULL"
    );
  }

  public function testLike(): void {
    $searchy = new Searchy();
    $query = $searchy->assemblySearch(array('q'=>array("test-matches"=>"Test")));
    $this->assertEquals(
      $query,
      "test LIKE '%Test%'"
    );
  }

  public function testBindsValues(): void {
    $bind = [];
    $query = Searchy::assemblySearch(array('q'=>array("name-matches"=>"Te'st", "a.code-eq"=>"007", "x-nn"=>"")), $bind);
    $this->assertEquals("name LIKE ? AND a.code = ? AND x IS NOT NULL", $query);
    $this->assertSame(["%Te'st%", "007"], $bind);
  }

  public function testEscapesLiterals(): void {
    $query = Searchy::assemblySearch(array('q'=>array("name-eq"=>"x' OR '1'='1")));
    $this->assertEquals("name = 'x\\' OR \\'1\\'=\\'1'", $query);
  }

  public function testRejectsInvalidColumnsAndComparators(): void {
    $bind = [];
    $query = Searchy::assemblySearch(array('q'=>array("1=1 OR name-eq"=>"x", "name-zz"=>"y", "id-eq"=>"5")), $bind);
    $this->assertEquals("1 = 0 AND 1 = 0 AND id = ?", $query);
    $this->assertSame(["5"], $bind);
  }

  public function comparatorsProvider()
    {
        return [
            ['eq', '='],
            ['ne', '<>'],
            ['gt', '>'],
            ['gte', '>='],
            ['lt', '<'],
            ['lte', '<=']
        ];
    }
}
