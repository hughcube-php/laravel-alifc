<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/21
 * Time: 19:49
 */

namespace HughCube\Laravel\AliFC\Queue;

class DatabaseJobId
{
    protected $id;

    protected $connection;

    public function __construct(string $connection, $id)
    {
        $this->connection = $connection;
        $this->id = $id;
    }
}
