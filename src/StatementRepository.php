<?php

namespace Milanmadar\CoolioORM;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;

class StatementRepository
{
    private Connection $db;

    /** @var array<string, Statement> */
    private array $statements;
    private int $statementsCount;

    /**
     * MySQL Prepared Statements EntityRepository
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->statements = [];
        $this->statementsCount = 0;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function get(string $sql): Statement
    {
        if(!isset($this->statements[$sql])) {
            // dont go unlimited
            if(++$this->statementsCount >= 40) {
                $this->statements = [];
                $this->statementsCount = 1;
            }
            $this->statements[$sql] = $this->db->prepare($sql);
        }
        return $this->statements[$sql];
    }

    /**
     * Removes all cached prepared statements
     */
    public function clear(): void
    {
        $this->statements = [];
    }

    /**
     * Returns the database connection object for which the statements are cached in the repo
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->db;
    }

}