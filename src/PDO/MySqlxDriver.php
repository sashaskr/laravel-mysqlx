<?php

namespace Sashaskr\Mysqlx\PDO;

use Illuminate\Database\PDO\MySqlDriver;

class MySqlxDriver extends MySqlDriver
{
    public function getName()
    {
        return 'pdo_mysqlx';
    }
}
