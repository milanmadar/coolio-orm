<?php

namespace tests;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;

class DbHelper
{
    /** @var array<string>  */
    private array $sqls = [];

    private Connection $conn;

    /**
     * DbHelper constructor.
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function getConnection(): Connection
    {
        return $this->conn;
    }

    /**
     * Counts the number of rows in the table
     * @param string $table Name of the table
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function countRows(string $table): int
    {
        return (int)$this->conn->fetchOne("select count(*) from ".$table);
    }

    /**
     * @param string $filename
     * @return void
     */
    private function loadFile(string $filename): void
    {
        $filename = ltrim($filename, '/');
        if(!isset($this->sqls[$filename])) {
            $path = dirname(__DIR__).'/'.$filename;
            if(!file_exists($path)) {
                throw new \InvalidArgumentException("No file: ".$path);
            }
            $this->sqls[$filename] = file_get_contents($path);
        }
    }

    /**
     * @param string $filename Relative to the tests folder
     */
    public function resetTo(string $filename): void
    {
        $this->executeFile($filename);
//        $this->loadFile($filename);
//
//        // empty the database (drop all tables)
//        $rows = $this->executeQuery("show tables")->fetchAllNumeric();
//        $this->executeQuery('SET FOREIGN_KEY_CHECKS = 0');
//        foreach($rows as $row) {
//            $this->executeQuery("drop table ".$row[0]);
//        }
//        $this->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
//
//        // execute the file
//        $this->executeStatement($this->sqls[$filename]);
    }

    /**
     * @param string $filename Relative to the tests folder
     * @return void
     */
    public function executeFile(string $filename): void
    {
        $this->loadFile($filename);
        $this->executeStatement($this->sqls[$filename]);
    }

    /**
     * Executes an, optionally parametrized, SQL query.
     *
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string                                                               $sql    SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @throws \Exception
     */
    public function executeQuery(string $sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null): Result {
        return $this->conn->executeQuery($sql, $params, $types, $qcp);
    }

    public function executeStatement(string $sql, array $params = [], $types = []): int|string {
        return $this->conn->executeStatement($sql, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return array<string, mixed>|false False is returned if no rows are found.
     *
     * @throws \Exception
     */
    public function fetchAssociative(string $query, array $params = [], array $types = []) {
        return $this->fetchAssociative($query, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as a numerically indexed array.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list< mixed>|false False is returned if no rows are found.
     *
     * @throws \Exception
     */
    public function fetchNumeric(string $query, array $params = [], array $types = []) {
        return $this->fetchNumeric($query, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return mixed|false False is returned if no rows are found.
     *
     * @throws \Exception
     */
    public function fetchOne(string $query, array $params = [], array $types = []) {
        return $this->conn->fetchOne($query, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of numeric arrays.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list<list<mixed>>
     *
     * @throws \Exception
     */
    public function fetchAllNumeric(string $query, array $params = [], array $types = []): array {
        return $this->conn->fetchAllNumeric($query, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list<array<string,mixed>>
     *
     * @throws \Exception
     */
    public function fetchAllAssociative(string $query, array $params = [], array $types = []): array {
        return $this->conn->fetchAllAssociative($query, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys
     * mapped to the first column and the values mapped to the second column.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return array<mixed,mixed>
     *
     * @throws \Exception
     */
    public function fetchAllKeyValue(string $query, array $params = [], array $types = []): array {
        return $this->conn->fetchAllKeyValue($query, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return array<mixed,array<string,mixed>>
     *
     * @throws \Exception
     */
    public function fetchAllAssociativeIndexed(string $query, array $params = [], array $types = []): array {
        return $this->conn->fetchAllAssociativeIndexed($query, $params, $types);
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of the first column values.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list<mixed>
     *
     * @throws \Exception
     */
    public function fetchFirstColumn(string $query, array $params = [], array $types = []): array {
        return $this->conn->fetchAllAssociativeIndexed($query, $params, $types);
    }

    /**
     * Entity Manager, <b>SINGLETON</b>
     * @template T
     * @param T $mgrClassName
     * @return T
     *
     * @phpstan-param class-string<T> $mgrClassName
     * @phpstan-return T
     */
    public function getManager(string $mgrClassName): \Milanmadar\CoolioORM\Manager
    {
        $orm = \Milanmadar\CoolioORM\ORM::instance();
        $mgr = $orm->entityManager($mgrClassName);
        $mgr->clearRepository(false);
        return $mgr->setDb($this->getConnection());
    }

}