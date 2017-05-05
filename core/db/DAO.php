<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 05.05.2017
 * Time: 20:35
 */

namespace TriAn\IqoTest\core\db;


class DAO extends \PDO
{
    /**
     * @var \PDOStatement[]
     */
    protected $cache = [];

    /**
     * Prepare and cache a query
     * @param string $query
     * @return \PDOStatement
     */
    public function cacheQuery($query)
    {
        if (isset($query, $this->cache)) {
            return $this->cache[$query];
        }

        $statement = $this->prepare($query);
        $this->cache[$query] = $statement;

        return $statement;
    }


}