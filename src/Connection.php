<?php

namespace Sashaskr\Mysqlx;

use Sashaskr\Mysqlx\PDO\MySqlxDriver;
use Illuminate\Database\MySqlConnection as BaseConnection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Arr;

use PDO;
use function mysql_xdevapi\getSession;

class Connection extends BaseConnection
{
    /** @var \mysql_xdevapi\Schema  */
    protected $db;

    protected $connection;

    protected $transactions;

    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);

        $dsn = $this->getDsn($config);
        $options = Arr::get($config, 'options', []);
        $this->connection = $this->createConnection($dsn, $config, $options);
        $defaultDB = $this->getDefaultDatabaseName('', $config);
        $this->db = $this->connection->getSchema($defaultDB);

        $this->useDefaultPostProcessor();
        $this->useDefaultSchemaGrammar();
        $this->useDefaultQueryGrammar();
    }

    protected function createConnection($dsn, array $config, array $options)
    {
        // By default driver options is an empty array.
        $driverOptions = [];

//        $connection = app(ConnectionFactory::class);
//        $a = $connection->make($config, 'mysql');

        if (isset($config['driver_options']) && is_array($config['driver_options'])) {
            $driverOptions = $config['driver_options'];
        }

        // Check if the credentials are not already set in the options
        if (! isset($options['username']) && ! empty($config['username'])) {
            $options['username'] = $config['username'];
        }
        if (! isset($options['password']) && ! empty($config['password'])) {
            $options['password'] = $config['password'];
        }

        return getSession($dsn);
    }

    protected function getDsn(array $config)
    {
        return $this->hasDsnString($config)
            ? $this->getDsnString($config)
            : $this->getHostDsn($config);
    }

    public function table($table, $as = null)
    {
        return $this->collection($table);
    }

    public function collection($collection)
    {
        $query = new Query\Builder($this, $this->getPostProcessor());
        return $query->from($collection);
    }

    public function getCollection($name)
    {
        return $this->db->getCollection($name);
    }

    protected function hasDsnString(array $config)
    {
        return isset($config['dsn']) && ! empty($config['dsn']);
    }

    protected function getDsnString(array $config)
    {
        return $config['dsn'];
    }

    protected function getHostDsn(array $config)
    {
        $host = Arr::get($config, 'host');
        $username = Arr::get($config, 'username');
        $password = Arr::get($config, 'password');
//        $sslMode = (bool)Arr::get($config, 'ssl-mode', false) ?
//            strtoupper('enabled') :
//            strtoupper('disabled');
        $xPort = Arr::get($config, 'xport', 33060);

        return sprintf(
            'mysqlx://%s%s@%s:%d',
            $password ? $username. ':' : $username,
            $password,
            $host,
            $xPort
        );
    }

    protected function getDefaultDatabaseName($dsn, $config)
    {
//        if (empty($config['database'])) {
//            if (preg_match('/^mysqlx(?:[+]srv)?:\\/\\/.+\\/([^?&]+)/s', $dsn, $matches)) {
//                $config['database'] = $matches[1];
//            } else {
//                throw new InvalidArgumentException('Database is not properly configured.');
//            }
//        }

        return $config['database'];
    }

    public function getSchemaBuilder()
    {
        return new Schema\Builder($this);
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->db, $method], $parameters);
    }

    /**
     * @inheritdoc
     */
    public function getDriverName()
    {
        return 'mysqlx';
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        unset($this->connection);
    }

    protected function getDefaultPostProcessor()
    {
        return new Query\Processor();
    }

    protected function getDefaultQueryGrammar()
    {
        return new Query\Grammar();
    }

    public function getMysqlxSchema(): \mysql_xdevapi\Schema
    {
        return $this->db;
    }

    public function beginTransaction()
    {
        $this->connection->startTransaction();
        $this->transactionsManager->begin($this->connection, 1);
    }

    public function rollBack($toLevel = null)
    {
        $this->connection->rollback();
        $this->transactionsManager->rollback($this->connection, 1);
    }
}
