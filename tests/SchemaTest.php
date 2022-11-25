<?php
declare(strict_types=1);

use Sashaskr\Mysqlx\Schema\Blueprint;
use Sashaskr\Mysqlx\Schema\Builder;

class SchemaTest extends TestCase
{
    public function testCreate(): void
    {
        \Illuminate\Support\Facades\Schema::connection('mysqlx')->create('collection1', function (Blueprint $schema) {
            $this->assertTrue($schema::hasCollection('collection1'));
            $this->assertTrue($schema::hasTable('collection1'));
        });

    }
}