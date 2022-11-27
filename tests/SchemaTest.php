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
        });

    }
}
