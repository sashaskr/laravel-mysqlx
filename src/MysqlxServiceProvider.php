<?php

namespace Sashaskr\Mysqlx;

use Sashaskr\Database\Connectors\MySqlxConnector;
use Sashaskr\Mysqlx\Eloquent\Model;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Support\ServiceProvider;

class MysqlxServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);

        $this->setMysqlxConfigFromMysql();
    }

    public function register()
    {
        // Add database driver.
        $this->app->resolving('db', function ($db) {
            $db->extend('mysqlx', function ($config, $name) use ($db) {
                $config['name'] = $name;
                return new Connection(
                    $db->getRawPdo(),
                    $db->getDatabaseName(),
                    $db->getTablePrefix(),
                    $config
                );
            });
        });

        // Add connector for queue support.
        $this->app->resolving('queue', function ($queue) {
            $queue->addConnector('mysqlx', function () {
                return new MysqlxConnector($this->app['db']);
            });
        });
    }

    private function setMysqlxConfigFromMysql(): void
    {
        // TODO: from ENV?
        \Config::set(
            'database.connections.mysqlx',
            \Config::get('database.connections.mysql')
        );
    }
}
