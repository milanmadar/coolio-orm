<?php

namespace Milanmadar\CoolioORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;

class Utils
{
    /**
     * 'IN (:ArrayParam)'
     * @param string $sql
     * @param array<int<0, max>|string, mixed> $binds
     * @throws \InvalidArgumentException
     */
    public static function handleArrayInSQLParams(string &$sql, array &$binds): void
    {
        $goodBinds = [];

        foreach($binds as $k=>$v)
        {
            if(is_array($v) && !empty($v)) {
                if(is_string($k)) // named parameters
                {
                    $replPlaceholder = '';
                    foreach($v as $i=>$val) {
                        if(!isset($val)) {
                            $replPlaceholder .= ',NULL';
                        } else {
                            $replK = '__' . $k . '__' . $i;
                            $replPlaceholder .= ',:' . $replK;
                            $goodBinds[$replK] = $val;
                        }
                    }
                    // replace (ltrim: remove starting comma)
                    $sql = str_replace(':'.$k, ltrim($replPlaceholder, ','), $sql);
                }
                else // questionmarked parameters
                {
                    throw new \InvalidArgumentException("Question mark placeholders can't have arrays as parameter value. Use named parameters");
                }
            } else {
                $goodBinds[$k] = $v;
            }
        }

        $binds = $goodBinds;
    }

    public static function getDbConnUrl(Connection $db): string
    {
        /** @var array<string, string> $connParams */
        $connParams = $db->getParams();
        if(isset($connParams['url'])) {
            $connUrl = $connParams['url'];
        } elseif(isset($connParams['dbname'], $connParams['user'], $connParams['password'], $connParams['host'], $connParams['driver'])) {
            // mysqli://username:password@127.0.0.1/database_name
            $connUrl = $connParams['driver'].'://'.$connParams['user'].':'.$connParams['password'].'@'.$connParams['host'].'/'.$connParams['dbname'];
        } else {
            throw new \InvalidArgumentException("\Milanmadar\CoolioORM\Utils::getDbConnUrl(): donno how to get the db connection params");
        }
        return $connUrl;
    }

    /**
     * @param string $sql
     * @param array<mixed> $binds
     * @param Connection $conn
     * @param StatementRepository|null $statementRepo
     * @return Result
     * @throws Exception
     */
    public static function executeQuery_bindValues(string $sql, array $binds, Connection $conn, StatementRepository|null $statementRepo): \Doctrine\DBAL\Result
    {
        self::handleArrayInSQLParams($sql, $binds);

        $paramTypes = [];
        if(!empty($binds)) {
            $_bindsCopy = [];
            $questionmark_i = 0;
            foreach($binds as $k=>$v) {
                // named params are easy. question marks must be sequential starting from 1
                $bind_Name_or_qmarkIndex = is_int($k) ? ++$questionmark_i : $k;
                if(is_array($v)) {
                    $paramTypes[$bind_Name_or_qmarkIndex] = \Doctrine\DBAL\Types\Types::SIMPLE_ARRAY;
                } else {
                    $paramTypes[$bind_Name_or_qmarkIndex] = \Doctrine\DBAL\Types\Types::STRING;
                }
                $_bindsCopy[$bind_Name_or_qmarkIndex] = $v;
            }
            $binds = $_bindsCopy;
        }

        // for phpstan
        /** @var array<int<0, max>|string, mixed> $binds */
        /** @var array<int<1,max>|string, 'simple_array'|'string'> $paramTypes */

        // Postgres already cries on $db->prepare() (inside $statementRepo->get()) if we have mixed params (so questionmarks and named params too)
        try {
            if(isset($statementRepo)) {
                $stmt = $statementRepo->get($sql);
                foreach ($paramTypes as $name_or_qmarkIndex => $type) {
                    $stmt->bindValue($name_or_qmarkIndex, $binds[$name_or_qmarkIndex], $type);
                }
            }
        } catch (Exception\DriverException $e) {
            throw self::handleDriverException($e, $sql, $binds);
        }

        $maxTries = ($_ENV['COOLIO_ORM_RETRY_ATTEMPTS'] ?? 0)+1;
        $retrySleep = $_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2;

        for ($i=1; $i<=$maxTries; ++$i) {
            try {
                if(isset($stmt)) {
                    return $stmt->executeQuery();
                } else {
                    return $conn->executeQuery($sql, $binds, $paramTypes);
                }
            }
            catch (Exception\ConnectionException | Exception\ConnectionLost | Exception\RetryableException $e) {
                if ($i == $maxTries) {
                    throw self::handleDriverException($e, $sql, $binds);
                }
                sleep($retrySleep);
            }
            catch (Exception $e) {
                throw self::handleDriverException($e, $sql, $binds);
            }
        }

        // this is just so IDE doesn't complain, but the loop above always returns or throws
        return $conn->executeQuery($sql, $binds, $paramTypes);
    }

    /**
     * @param string $sql
     * @param array<mixed> $binds
     * @param Connection $conn
     * @param StatementRepository|null $statementRepo
     * @return int The number of affected rows
     * @throws Exception
     */
    public static function executeStatement_bindValues(string $sql, array $binds, Connection $conn, StatementRepository|null $statementRepo): int
    {
        self::handleArrayInSQLParams($sql, $binds);

        $paramTypes = [];
        if(!empty($binds)) {
            $_bindsCopy = [];
            $questionmark_i = 0;
            foreach($binds as $k=>$v) {
                // named params are easy. question marks must be sequential starting from 1
                $bind_Name_or_qmarkIndex = is_int($k) ? ++$questionmark_i : $k;
                if(is_array($v)) {
                    $paramTypes[$bind_Name_or_qmarkIndex] = \Doctrine\DBAL\Types\Types::SIMPLE_ARRAY;
                } else {
                    $paramTypes[$bind_Name_or_qmarkIndex] = \Doctrine\DBAL\Types\Types::STRING;
                }
                $_bindsCopy[$bind_Name_or_qmarkIndex] = $v;
            }
            $binds = $_bindsCopy;
        }

        // for phpstan
        /** @var array<int<0, max>|string, mixed> $binds */
        /** @var array<int<1,max>|string, 'simple_array'|'string'> $paramTypes */

        // Postgres already cries on $db->prepare() (inside $statementRepo->get()) if we have mixed params (so questionmarks and named params too)
        try {
            if(isset($statementRepo)) {
                $stmt = $statementRepo->get($sql);
                foreach ($paramTypes as $name_or_qmarkIndex => $type) {
                    $stmt->bindValue($name_or_qmarkIndex, $binds[$name_or_qmarkIndex], $type);
                }
            }
        } catch (Exception\DriverException $e) {
            throw self::handleDriverException($e, $sql, $binds);
        }

        $maxTries = ($_ENV['COOLIO_ORM_RETRY_ATTEMPTS'] ?? 0)+1;
        $retrySleep = $_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2;

        for ($i=1; $i<=$maxTries; ++$i) {
            try {
                if(isset($stmt)) {
                    return (int)$stmt->executeStatement();
                } else {
                    return (int)$conn->executeStatement($sql, $binds, $paramTypes);
                }
            }
            catch (Exception\ConnectionException | Exception\ConnectionLost | Exception\RetryableException $e) {
                if ($i == $maxTries) {
                    throw self::handleDriverException($e, $sql, $binds);
                }
                sleep($retrySleep);
            }
            catch (Exception $e) {
                throw self::handleDriverException($e, $sql, $binds);
            }
        }

        // this is just so IDE doesn't complain, but the loop above always returns or throws
        return (int)$conn->executeStatement($sql, $binds, $paramTypes);
    }

    /**
     * @param Exception | Exception\RetryableException $e
     * @param string|null $sql
     * @param array<int|string, mixed>|null $binds
     * @return ORMException
     */
    public static function handleDriverException(Exception|Exception\RetryableException $e, ?string $sql, ?array $binds): ORMException
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[2];
        $callerClass = isset($caller['class']) ? $caller['class'] : null;
        $callerFunction = $caller['function'];
        $from = $callerClass.'::'.$callerFunction.'()';

        if($e instanceof Exception\DriverException)
        {
            $exClass = get_class($e);
            $query = $e->getQuery();
            $theirPrevEx = $e->getPrevious();
            $newMsg =
                "\n".$exClass
                ."\nin ".$from
                ."\n".($theirPrevEx?->getMessage() ?? $e->getMessage())
                ."\n\n".($query?->getSQL() ?? $sql ?? '(no sql)')
                ."\n\n".print_r($query?->getParams() ?? $binds ?? [], true);
            $code = ($theirPrevEx instanceof \Exception) ? $theirPrevEx->getCode() : $e->getCode();
            return new ORMException($newMsg, $code, $e);
        }

        $newMsg =
            "\n".get_class($e)
            ."\nin ".$from
            ."\n".$e->getMessage();
        if(!empty($sql)) {
            $newMsg .= "\n\n" . $sql;
        }
        if(!empty($sql)) {
            $newMsg .= "\n\n".print_r($binds, true);
        }

        return new ORMException($newMsg, $e->getCode(), $e);
    }

    /**
     * @param string $fieldOrTable
     * @param string $dbType
     * @return string
     */
    public static function escapeColumnName(string $fieldOrTable, string $dbType): string
    {
        return match($dbType) {
            'my' => '`'.$fieldOrTable.'`',
            'ms' => '['.$fieldOrTable.']',
            default => '"'.$fieldOrTable.'"' // pg, oracle, etc
        };
    }

}