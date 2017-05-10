<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 03.05.2017
 * Time: 21:14
 */

namespace TriAn\IqoTest\core\db;


use TriAn\IqoTest\core\exception\TransactionException;

class Transaction
{
    /**
     * @var DAO
     */
    protected $connection;

    protected $ended = false;

    public function __construct(DAO $connection)
    {
        $this->connection = $connection;
        $this->connection->beginTransaction();
    }

    /**
     * Executes SQL query in a transaction
     * @param string $query query
     * @param array $parameters
     * @return \PDOStatement
     * @throws TransactionException
     */
    public function execute($query, array $parameters)
    {
        if ($this->ended) {
            throw new TransactionException('Trying to execute statements on an ended transaction');
        }

        $statement = $this->connection->cacheQuery($query);
        $statement->execute($parameters);

        return $statement;
    }

    public function commit()
    {
        if ($this->ended) {
            return;
        }
        $this->connection->commit();
        $this->ended = true;
    }

    public function rollBack()
    {
        if ($this->ended) {
            return;
        }
        $this->connection->rollBack();
        $this->ended = true;
    }
}