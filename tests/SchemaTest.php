<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Sashaskr\Mysqlx\Schema\Blueprint;

class SchemaTest extends TestCase
{
    public function tearDown(): void
    {
        Schema::connection(self::MYSQLX_CONNECTION)
            ->dropAllTables();
    }

    public function testCreate(): void
    {
        Schema::connection(self::MYSQLX_CONNECTION)
            ->create('collection1', function (Blueprint $schema) {
            });

        $this->assertTrue(Schema::connection(self::MYSQLX_CONNECTION)
            ->hasTable('collection1'));
    }

    public function testDrop(): void
    {
        Schema::connection(self::MYSQLX_CONNECTION)
            ->create('collection2', function (Blueprint $schema) {
            });
        Schema::connection(self::MYSQLX_CONNECTION)->drop('collection2');
        $this->assertFalse(Schema::connection(self::MYSQLX_CONNECTION)
            ->hasTable('collection2'));
    }

    public function testBlueprint(): void
    {
        $instance = $this;

        Schema::connection(self::MYSQLX_CONNECTION)
            ->collection('newcollection', function ($collection) use ($instance) {
                $instance->assertInstanceOf(Blueprint::class, $collection);
            });

        Schema::connection(self::MYSQLX_CONNECTION)
            ->table('newcollection', function ($collection) use ($instance) {
                $instance->assertInstanceOf(Blueprint::class, $collection);
            });
    }

    public function testIndex(): void
    {
        Schema::connection(self::MYSQLX_CONNECTION)->create('collection1', function($collection) {
            $collection->index(['col1'], 'idx1', ['col1' => ['type' => 'INTEGER', 'required' => false ]]);
            $this->assertTrue($collection->hasIndex('idx1'));
            $this->assertFalse($collection->hasIndex('idx2'));

            $idx1 = $collection->getIndex('idx1');

            $this->assertEquals('YES', $idx1['Null']);
            $this->assertEquals(1, $idx1['Non_unique']);
            $this->assertEquals('idx1', $idx1['Key_name']);

            $collection->index(['col1, col2'], 'idx2', [
                'col1' => ['type' => 'INTEGER',],
                'col2' => ['type' => 'TEXT(16)', 'required' => true,],
            ]);

            $idx2 = $collection->getIndex('idx2');
            $this->assertEquals('YES', $idx2['Null']);
            $this->assertEquals(1, $idx2['Non_unique']);
            $this->assertEquals('idx2', $idx2['Key_name']);
        });
    }

    public function testUniqueIndex(): void
    {
        $this->markTestSkipped('Test it after unique index functionality will be clear');
    }

    public function testDropIndex(): void
    {
        Schema::connection(self::MYSQLX_CONNECTION)->create('collection3', function ($collection) {
            $collection->index(['col1'], 'idx1', ['col1' => ['type' => 'INTEGER', 'required' => false]]);
        });

        Schema::connection(self::MYSQLX_CONNECTION)->collection('collection3', function ($collection) {
            $this->assertTrue($collection->hasIndex('idx1'));
            $collection->dropIndex('idx1');
            $this->assertFalse($collection->hasIndex('idx1'));
        });
    }

    public function testDropIfExistCollection(): void
    {
        Schema::connection(self::MYSQLX_CONNECTION)->create('col1', function () {});
        $this->assertTrue(Schema::connection(self::MYSQLX_CONNECTION)->hasTable('col1'));
        Schema::connection(self::MYSQLX_CONNECTION)->dropIfExists('col1');
        $this->assertFalse(Schema::connection(self::MYSQLX_CONNECTION)->hasTable('col1'));
    }

    /** @test */
    public function itShouldReturnFalseIfCollectionNotExistWhenDropIfExist(): void
    {
        $this->assertFalse(Schema::connection(self::MYSQLX_CONNECTION)->dropIfExists('col2'));
    }

    /** @test */
    public function itShouldTrueForHasColumnOrColumns(): void
    {
        $this->assertTrue(Schema::connection(self::MYSQLX_CONNECTION)->hasColumn('', ''));
        $this->assertTrue(Schema::connection(self::MYSQLX_CONNECTION)->hasColumns('',  []));
    }

    public function testGetAllCollections(): void
    {
        Schema::connection(self::MYSQLX_CONNECTION)->create('test1', function () {});
        Schema::connection(self::MYSQLX_CONNECTION)->create('test2', function () {});
        $this->assertEquals(['test1', 'test2'], Schema::connection(self::MYSQLX_CONNECTION)->getAllTables());
    }
}
