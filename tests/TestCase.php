<?php
declare(strict_types=1);

class TestCase extends Orchestra\Testbench\TestCase
{
    public const MYSQLX_CONNECTION = 'mysqlx';

    protected function getApplicationProviders($app)
    {
        return parent::getApplicationProviders($app);
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            Sashaskr\Mysqlx\MysqlxServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['path.base'] = __DIR__ . '/../src';

        $config = require 'config/database.php';

        $app['config']->set('app.key', 'aaaaaaaaaaasdsdasdasdasd');

        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', $config['connections']['mysql']);

        $app['config']->set('auth.model', 'User');
        $app['config']->set('auth.providers.users.model', 'User');
        $app['config']->set('cache.driver', 'array');

        $app['config']->set('queue.default', 'database');
        $app['config']->set('queue.connections.database', [
            'driver' => 'mysql',
            'table' => 'jobs',
            'queue' => 'default',
            'expire' => 60,
        ]);
        $app['config']->set('queue.failed.database', 'mysql');
        $app['config']->set('queue.failed.driver', 'mysql');
    }
}
