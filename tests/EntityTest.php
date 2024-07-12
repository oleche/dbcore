<?php
declare(strict_types=1);

namespace Geekcow\Dbcore;

use PHPUnit\Framework\TestCase;

final class EntityTest extends TestCase {
    private $entity;

    protected function setUp(): void {
        $this->entity = new Entity(['id' => ['type' => 'int', 'pk' => true]], 'TestEntity');
    }

    public function canInstantiateEntity(): void {
        $this->assertInstanceOf(Entity::class, $this->entity);
    }

    public function canSetAndGetMapping(): void {
        $mapping = ['id' => ['type' => 'int', 'pk' => true]];
        $entity = new Entity($mapping, 'TestEntity');
        $this->assertEquals($mapping, $entity->get_mapping());
    }

    public function canHandlePaginationWithMultiplePages(): void {
        $_SERVER['REQUEST_URI'] = '/test?page=2';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET['page'] = '2';

        $classMock = $this->createMock(Entity::class);
        $classMock->method('set_ipp')->willReturn(null);
        $classMock->pages = 5;
        $this->entity->set_paging($classMock, ['page' => 2, 'per_page' => 10]);
        $this->entity->paginate($classMock);

        $this->assertStringContainsString('rel="prev"', $this->entity->pagination_link);
        $this->assertStringContainsString('rel="next"', $this->entity->pagination_link);
    }

    public function canHandlePaginationWithFirstPage(): void {
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET['page'] = '1';

        $classMock = $this->createMock(Entity::class);
        $classMock->method('set_ipp')->willReturn(null);
        $classMock->pages = 3;
        $this->entity->set_paging($classMock, ['page' => 1, 'per_page' => 10]);
        $this->entity->paginate($classMock);

        $this->assertStringNotContainsString('rel="prev"', $this->entity->pagination_link);
        $this->assertStringContainsString('rel="next"', $this->entity->pagination_link);
    }

    public function canHandlePaginationWithLastPage(): void {
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET['page'] = '3';

        $classMock = $this->createMock(Entity::class);
        $classMock->method('set_ipp')->willReturn(null);
        $classMock->pages = 3;
        $this->entity->set_paging($classMock, ['page' => 3, 'per_page' => 10]);
        $this->entity->paginate($classMock);

        $this->assertStringContainsString('rel="prev"', $this->entity->pagination_link);
        $this->assertStringNotContainsString('rel="next"', $this->entity->pagination_link);
    }

    public function canHandlePaginationWithSinglePage(): void {
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET['page'] = '1';

        $classMock = $this->createMock(Entity::class);
        $classMock->method('set_ipp')->willReturn(null);
        $classMock->pages = 1;
        $this->entity->set_paging($classMock, ['page' => 1, 'per_page' => 10]);
        $this->entity->paginate($classMock);

        $this->assertEmpty($this->entity->pagination_link);
    }
}