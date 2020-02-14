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
      "test = 123"
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
      "test $expected 123"
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
