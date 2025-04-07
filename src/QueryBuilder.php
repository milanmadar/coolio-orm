<?php

namespace Milanmadar\CoolioORM;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;

class QueryBuilder extends DoctrineQueryBuilder
{
    private const TYPE_SELECT = 'SELECT';
    private const TYPE_INSERT = 'INSERT';
    private const TYPE_UPDATE = 'UPDATE';
    private const TYPE_DELETE = 'DELETE';
    private string $type;
    /** @var array< array<string> > */
    private array $orderBys;

    /** @var int in the whereColumn() methods we use this to generate the named placeholder */
    private int $placeholderI;

    private ORM $orm;
    private Connection $db;
    private StatementRepository $statementRepo;
    private ?Manager $entityMgr;
    private bool $select_asterist_is_done;

    public function __construct(ORM $orm, Connection $db, ?Manager $entityMgr = null)
    {
        parent::__construct($db);

        $this->type = self::TYPE_SELECT;
        $this->orderBys = [];
        $this->placeholderI = 0;
        $this->db = $db;
        $this->orm = $orm;
        $this->statementRepo = $this->orm->getStatementRepositoryByConnection($db);
        $this->entityMgr = $entityMgr;
        $this->select_asterist_is_done = false;
    }

    /**
     * @param Manager $entityMgr
     * @return $this
     */
    public function setEntityManager(Manager $entityMgr): self
    {
        $this->entityMgr = $entityMgr;
        return $this;
    }

    /**
     * @return Manager|null
     */
    public function getEntityManager(): ?Manager
    {
        return $this->entityMgr;
    }

    /**
     * @inheritDoc
     * @return self
     */
    public function select(string ...$expressions): self
    {
        // Tiny optimization to avoid running the "transform geometries" section below.
        // It's needed, because the baseManager::createQueryBuilder() calls $this->select('*')
        // and then the user may also call $this->select('*').
        // So just don't do the "transform geometries" section below twice
        if($expressions[0] == '*') {
            if($this->select_asterist_is_done) {
                return $this;
            }
            $this->select_asterist_is_done = true;
        } else {
            $this->select_asterist_is_done = false;
        }

        // transform geometries
        if(isset($this->entityMgr))
        {
            if($expressions[0] == '*') {
                $expressions = $this->entityMgr->getFields();
            }

            $_exps = [];
            $fieldTypes = $this->entityMgr->getFieldTypes();
            foreach($expressions as $e) {
                if(isset($fieldTypes[$e])) {
                    if($fieldTypes[$e] == 'geometry') {
                        $_exps[] = "ST_AsGeoJSON({$e}) AS {$e}";
                        $_exps[] = "ST_SRID({$e}) AS {$e}_srid";
                    } elseif($fieldTypes[$e] == 'geometry_curved') {
                        $_exps[] = "ST_AsEWKT({$e}) as {$e}";
                    } else {
                        $_exps[] = $e;
                    }
                } else {
                    $_exps[] = $e;
                }
            }
        }
        else {
            $_exps = $expressions;
        }

        $this->type = self::TYPE_SELECT;
        parent::select(...$_exps);
        return $this;
    }

    /**
     * It will include all the fields from the manager (so from the table), except those that you give as a param
     * @param array<string> $exceptFields
     * @return QueryBuilder
     */
    public function selectExcept(array $exceptFields): self
    {
        if(!isset($this->entityMgr)) {
            throw new \ErrorException("CoolioORM\\QueryBuilder::selectExcept() doesn't have an $"."entityMgr. Fields were: ".implode(',', $exceptFields));
        }
        return $this->select(implode(',', $this->entityMgr->getFields($exceptFields)));
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function from(string $table, ?string $alias = null): self
    {
        parent::from($table, $alias);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function join(string $fromAlias, string $join, string $alias, ?string $condition = null): self
    {
        parent::join($fromAlias, $join, $alias, $condition);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function innerJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self
    {
        parent::innerJoin($fromAlias, $join, $alias, $condition);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function leftJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self
    {
        parent::leftJoin($fromAlias, $join, $alias, $condition);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function rightJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self
    {
        parent::rightJoin($fromAlias, $join, $alias, $condition);
        return $this;
    }

    /**
     * @inheritDoc
     * @param string $key
     * @param string|int|float|null $value
     * @return $this
     */
    public function set(string $key, string|int|float|null $value): self
    {
        if(is_null($value)) $value = 'null';
        parent::set($key, (string)$value);
        return $this;
    }

    /**
     * It handles:
     * - '=' and '!=' operator to 'IS NULL' or 'IS NOT NULL' when $value is NULL
     * - '=' and '!=! to 'IN' or 'NOT IN' when $value is an array
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return array<string, mixed> [<br>
     *    'sql' => Pass this to the $this->where() method,<br>
     *    'paramName' => Placeholder name. (If its NULL then no need to call $this->setParameter())<br>
     * ]
     */
    private function correctWhereColumnParams(string $column, string $operator, mixed $value): array
    {
        $paramName = 'AutoGen' . ++$this->placeholderI;

        $operator = strtoupper(trim($operator));

        if(is_array($value)) {
            if($operator == '=') $operator = 'IN';
            elseif($operator == '!=') $operator = 'NOT IN';

            if(empty($value)) {
                //return ['sql'=>$column.' '.$operator.' ()'];
                if($operator == 'IN') {
                    return ['sql' => '1=2'];
                } else {
                    return ['sql' => '1=1'];
                }
            }

            $sql = $column.' '.$operator.' (:'.$paramName.')';
        }
        elseif($value == 'NOT NULL' && ($operator == '=' || $operator == 'IS')) {
            $sql = $column.' IS NOT NULL';
            $paramName = null;
        }
        elseif(is_null($value) || $value == 'NULL') {
            if($operator == '=') $operator = 'IS';
            elseif($operator == '!=') $operator = 'IS NOT';
            $sql = $column.' '.$operator.' NULL';
            $paramName = null;
        }
        else {
            $sql = $column.' '.$operator.' :'.$paramName;
        }

        return ['sql'=>$sql, 'paramName'=>$paramName];
    }

    /**
     * It handles NULL and corrrect '=' to 'IN' when $value is an array
     * @param string $column Field name
     * @param string $operator
     * @param mixed $value
     * @return QueryBuilder
     */
    public function whereColumn(string $column, string $operator, mixed $value): QueryBuilder
    {
        $x = $this->correctWhereColumnParams($column, $operator, $value);
        $this->where($x['sql']);
        if(isset($x['paramName'])) $this->setParameter($x['paramName'], $value);
        return $this;
    }

    /**
     * It handles NULL and corrrect '=' to 'IN' when $value is an array
     * @param string $column Field name
     * @param string $operator
     * @param mixed $value
     * @return QueryBuilder
     */
    public function andWhereColumn(string $column, string $operator, mixed $value): QueryBuilder
    {
        $x = $this->correctWhereColumnParams($column, $operator, $value);
        $this->andWhere($x['sql']);
        if(isset($x['paramName'])) $this->setParameter($x['paramName'], $value);
        return $this;
    }

    /**
     * It handles NULL and correct '=' to 'IN' when $value is an array
     * @param string $column Field name
     * @param string $operator
     * @param mixed $value
     * @return QueryBuilder
     */
    public function orWhereColumn(string $column, string $operator, mixed $value): QueryBuilder
    {
        $x = $this->correctWhereColumnParams($column, $operator, $value);
        $this->orWhere($x['sql']);
        if(isset($x['paramName'])) $this->setParameter($x['paramName'], $value);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function insert(string|null $table = null): self
    {
        $this->type = self::TYPE_INSERT;

        if(!isset($table)) {
            if(isset($this->entityMgr)) {
                $table = $this->entityMgr->getDbTable();
            } else {
                throw new \InvalidArgumentException('QueryBuilder::insert() You must provide a table name or set an entity manager.');
            }
        }

        parent::insert($table);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function update(string|null $table = null): self
    {
        $this->type = self::TYPE_UPDATE;

        if(!isset($table)) {
            if(isset($this->entityMgr)) {
                $table = $this->entityMgr->getDbTable();
            } else {
                throw new \InvalidArgumentException('QueryBuilder::update() You must provide a table name or set an entity manager.');
            }
        }

        parent::update($table);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function delete(string|null $table = null): self
    {
        $this->type = self::TYPE_DELETE;

        if(!isset($table)) {
            if(isset($this->entityMgr)) {
                $table = $this->entityMgr->getDbTable();
            } else {
                throw new \InvalidArgumentException('QueryBuilder::delete() You must provide a table name or set an entity manager.');
            }
        }

        parent::delete($table);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function where(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        parent::where($predicate, ...$predicates);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function andWhere(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        parent::andWhere($predicate, ...$predicates);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function orWhere(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        parent::orWhere($predicate, ...$predicates);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function groupBy(string $expression, string ...$expressions): self
    {
        parent::groupBy($expression, ...$expressions);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function addGroupBy(string $expression, string ...$expressions): self
    {
        parent::addGroupBy($expression, ...$expressions);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function setValue(string $column, string $value): self
    {
        parent::setValue($column, $value);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function values(array $values): self
    {
        parent::values($values);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function having(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        parent::having($predicate, ...$predicates);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function andHaving(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        parent::andHaving($predicate, ...$predicates);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function orHaving(string|CompositeExpression $predicate, string|CompositeExpression ...$predicates): self
    {
        parent::orHaving($predicate, ...$predicates);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function orderBy(string $sort, ?string $order = null): self
    {
        if(!isset($order)) $order = 'ASC';
        $this->orderBys = [ [$sort, $order] ];
        parent::orderBy($sort, $order);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function addOrderBy(string $sort, ?string $order = null): self
    {
        if(!isset($order)) $order = 'ASC';
        $this->orderBys[] = [$sort, $order];
        parent::addOrderBy($sort, $order);
        return $this;
    }

    /**
     * LIMIT part
     * @param int $offset
     * @param int $maxResults
     * @return $this
     */
    public function limit(int $offset, int $maxResults): self
    {
        $this->setFirstResult($offset)->setMaxResults($maxResults);
        return $this;
    }

    /**
     * Adjusts the LIMIT part of the query
     * @param int $page Minimum 1
     * @param int $maxResults Minimum 1
     * @return $this
     */
    public function page(int $page, int $maxResults): self
    {
        assert($page >= 1, new \InvalidArgumentException('$page (1st) param must by minimum 1'));
        assert($maxResults >= 1, new \InvalidArgumentException('$maxResults (2nd) param must by minimum 1'));
        if($page < 1) $page = 1; /** @phpstan-ignore-line */
        if($maxResults < 1) $maxResults = 1; /** @phpstan-ignore-line */
        return $this->limit(($page-1)*$maxResults, $maxResults);
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function setParameter(
        int|string $key,
        mixed $value,
        string|ParameterType|Type|ArrayParameterType $type = ParameterType::STRING
    ): self
    {
        // bool
        if(is_bool($value)) {
            $value = (int)$value;
        }
        elseif(is_array($value) && !empty($value) && is_bool(array_values($value)[0])) {
            $_values = [];
            foreach($value as $_v) {
                if(is_bool($_v)) $_v = (int)$_v;
                $_values[] = $_v;
            }
            $value = $_values;
        }

        if($type == ParameterType::STRING && is_array($value)) {
            if(empty($value)) {
                $type = ArrayParameterType::INTEGER;
            } else {
                $type = is_string(array_values($value)[0])
                    ? ArrayParameterType::STRING
                    : ArrayParameterType::INTEGER;
            }
        }

        parent::setParameter($key, $value, $type);

        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function setParameters(array $params, array $types = []): self
    {
        if(empty($types)) {
            /**
             * @var int<0, max>|string $k
             * @var mixed $v
             */
            foreach($params as $k=>$v) {
                self::setParameter($k, $v);
            }
        } else {
            parent::setParameters($params, $types);
        }
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function setFirstResult(int $firstResult): self
    {
        parent::setFirstResult($firstResult);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function setMaxResults(?int $maxResults): QueryBuilder
    {
        parent::setMaxResults($maxResults);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function distinct(bool $distinct = true): self
    {
        parent::distinct($distinct);
        return $this;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    public function addSelect(string $expression, string ...$expressions): self
    {
        parent::addSelect($expression, ...$expressions);
        return $this;
    }

    /**
     * @inheritDoc
     * @return Result
     */
    public function executeQuery(): Result
    {
        return Utils::executeQuery_bindValues($this->getSQL(), $this->getParameters(), $this->db, $this->statementRepo);
    }

    /**
     * @inheritDoc
     * @return int The number of affected rows
     */
    public function executeStatement(): int
    {
        $sql = $this->getSQL();

        // MYSQL: Doctrine would only add LIMIT and ORDER to the SELECT query, but we can add it to DELETE and UPDATE too
        // OTHERS: Can't do it
        if($this->type == self::TYPE_DELETE || $this->type == self::TYPE_UPDATE)
        {
            $addedStuff = false;

            // add ORDER BY
            if(!empty($this->orderBys) && !str_contains($sql, 'ORDER BY')) {
                $addedStuff = true;
                $sql .= ' ORDER BY ' . implode(', ',
                    array_map(fn($value): string => implode(' ',$value), $this->orderBys)
                );
            }

            // add LIMIT
            if($this->getMaxResults() !== null || $this->getFirstResult() !== 0) {
                $addedStuff = true;
                $sql = $this->db->getDatabasePlatform()->modifyLimitQuery(
                    $sql,
                    $this->getMaxResults(),
                    $this->getFirstResult()
                );
            }

            // Postgres doesn't like LIMIT in DELETE and UPDATE
            if($addedStuff) {
                if(!str_contains(get_class($this->db->getDatabasePlatform()), 'MySQL')) {
                    throw Utils::handleDriverException(new ORMException("Can't do LIMIT or ORDER BY on DELETE statements"), $sql, $this->getParameters());
                }
            }
        }

        return Utils::executeStatement_bindValues($sql, $this->getParameters(), $this->db, $this->statementRepo);
    }

    /**
     * Returns the ID of the last inserted row.
     * @return int The last inserted ID.
     * @throws Exception
     */
    public function lastInsertId(): int
    {
        return (int)$this->db->lastInsertId();
    }

    /**
     * @inheritDoc
     * @return array<string, mixed>|false False is returned if no rows are found.
     * @throws Exception
     */
    public function fetchAssociative(): array|false
    {
        $maxTries = ($_ENV['COOLIO_ORM_RETRY_ATTEMPTS'] ?? 0)+1;
        $retrySleep = $_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2;
        $sql = $this->getSQL();

        for ($i=1; $i<=$maxTries; ++$i) {
            try {
                $params = $this->getParameters();

                // Questionmark params can be handled in Doctrine Query Builder
                if (isset($params[0])) {
                    return parent::fetchAssociative();
                }

                $paramTypes = $this->getParameterTypes();
                Utils::handleArrayInSQLParams($sql, $params);
                return $this->db->fetchAssociative($sql, $params, $paramTypes);
            }
            catch (Exception\ConnectionException|Exception\ConnectionLost|Exception\RetryableException $e) {
                if ($i == $maxTries) {
                    throw Utils::handleDriverException($e, $sql, $params);
                }
                sleep($retrySleep);
            }
            catch (Exception $e) {
                throw Utils::handleDriverException($e, $sql, $params);
            }
        }

        // this is just so IDE doesn't complain, but the loop above always returns or throws
        return parent::fetchAssociative();
    }

    /**
     * @inheritDoc
     * @return array<int, mixed>|false False is returned if no rows are found.
     * @throws Exception
     */
    public function fetchNumeric(): array|false
    {
        $maxTries = ($_ENV['COOLIO_ORM_RETRY_ATTEMPTS'] ?? 0)+1;
        $retrySleep = $_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2;
        $sql = $this->getSQL();

        for ($i=1; $i<=$maxTries; ++$i) {
            try {
                $params = $this->getParameters();

                // Questionmark params can be handled in Doctrine Query Builder
                if (isset($params[0])) {
                    return parent::fetchNumeric();
                }

                $paramTypes = $this->getParameterTypes();
                Utils::handleArrayInSQLParams($sql, $params);
                return $this->db->fetchNumeric($sql, $params, $paramTypes);
            }
            catch (Exception\ConnectionException|Exception\ConnectionLost|Exception\RetryableException $e) {
                if ($i == $maxTries) {
                    throw Utils::handleDriverException($e, $sql, $params);
                }
                sleep($retrySleep);
            }
            catch (Exception $e) {
                throw Utils::handleDriverException($e, $sql, $params);
            }
        }

        // this is just so IDE doesn't complain, but the loop above always returns or throws
        return parent::fetchNumeric();
    }

    /**
     * @inheritDoc
     * @return array<int,array<int,mixed>>
     * @throws Exception
     */
    public function fetchAllNumeric(): array
    {
        $maxTries = ($_ENV['COOLIO_ORM_RETRY_ATTEMPTS'] ?? 0)+1;
        $retrySleep = $_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2;
        $sql = $this->getSQL();

        for ($i=1; $i<=$maxTries; ++$i) {
            try {
                $params = $this->getParameters();

                // Questionmark params can be handled in Doctrine Query Builder
                if (isset($params[0])) {
                    return parent::fetchAllNumeric();
                }

                $paramTypes = $this->getParameterTypes();
                Utils::handleArrayInSQLParams($sql, $params);
                return $this->db->fetchAllNumeric($sql, $params, $paramTypes);
            }
            catch (Exception\ConnectionException|Exception\ConnectionLost|Exception\RetryableException $e) {
                if ($i == $maxTries) {
                    throw Utils::handleDriverException($e, $sql, $params);
                }
                sleep($retrySleep);
            }
            catch (Exception $e) {
                throw Utils::handleDriverException($e, $sql, $params);
            }
        }

        // this is just so IDE doesn't complain, but the loop above always returns or throws
        return parent::fetchAllNumeric();
    }

    /**
     * @inheritDoc
     * @return array<int,array<string,mixed>>
     * @throws Exception
     */
    public function fetchAllAssociative(): array
    {
        $maxTries = ($_ENV['COOLIO_ORM_RETRY_ATTEMPTS'] ?? 0)+1;
        $retrySleep = $_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2;
        $sql = $this->getSQL();

        for ($i=1; $i<=$maxTries; ++$i) {
            try {
                $params = $this->getParameters();

                // Questionmark params can be handled in Doctrine Query Builder
                if (isset($params[0])) {
                    return parent::fetchAllAssociative();
                }

                $paramTypes = $this->getParameterTypes();
                Utils::handleArrayInSQLParams($sql, $params);
                return $this->db->fetchAllAssociative($sql, $params, $paramTypes);
            }
            catch (Exception\ConnectionException|Exception\ConnectionLost|Exception\RetryableException $e) {
                if ($i == $maxTries) {
                    throw Utils::handleDriverException($e, $sql, $params);
                }
                sleep($retrySleep);
            }
            catch (Exception $e) {
                throw Utils::handleDriverException($e, $sql, $params);
            }
        }

        // this is just so IDE doesn't complain, but the loop above always returns or throws
        return parent::fetchAllAssociative();
    }

    /**
     * @inheritDoc
     * @return array<string|int|float, string|int|float|bool|null>
     * @throws Exception
     */
    public function fetchAllKeyValue(): array
    {
        $maxTries = ($_ENV['COOLIO_ORM_RETRY_ATTEMPTS'] ?? 0)+1;
        $retrySleep = $_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2;
        $sql = $this->getSQL();

        for ($i=1; $i<=$maxTries; ++$i) {
            try {
                $params = $this->getParameters();

                // Questionmark params can be handled in Doctrine Query Builder
                if (isset($params[0])) {
                    return parent::fetchAllKeyValue();
                }

                $paramTypes = $this->getParameterTypes();
                Utils::handleArrayInSQLParams($sql, $params);
                return $this->db->fetchAllKeyValue($sql, $params, $paramTypes);
            }
            catch (Exception\ConnectionException|Exception\ConnectionLost|Exception\RetryableException $e) {
                if ($i == $maxTries) {
                    throw Utils::handleDriverException($e, $sql, $params);
                }
                sleep($retrySleep);
            }
            catch (Exception $e) {
                throw Utils::handleDriverException($e, $sql, $params);
            }
        }

        // this is just so IDE doesn't complain, but the loop above always returns or throws
        return parent::fetchAllKeyValue();
    }

    /**
     * @inheritDoc
     * @return array<mixed,array<string,mixed>>
     * @throws Exception
     */
    public function fetchAllAssociativeIndexed(): array
    {
        $maxTries = ($_ENV['COOLIO_ORM_RETRY_ATTEMPTS'] ?? 0)+1;
        $retrySleep = $_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2;
        $sql = $this->getSQL();

        for ($i=1; $i<=$maxTries; ++$i) {
            try {
                $params = $this->getParameters();

                // Questionmark params can be handled in Doctrine Query Builder
                if (isset($params[0])) {
                    return parent::fetchAllAssociativeIndexed();
                }

                $paramTypes = $this->getParameterTypes();
                Utils::handleArrayInSQLParams($sql, $params);
                return $this->db->fetchAllAssociativeIndexed($sql, $params, $paramTypes);
            }
            catch (Exception\ConnectionException|Exception\ConnectionLost|Exception\RetryableException $e) {
                if ($i == $maxTries) {
                    throw Utils::handleDriverException($e, $sql, $params);
                }
                sleep($retrySleep);
            }
            catch (Exception $e) {
                throw Utils::handleDriverException($e, $sql, $params);
            }
        }

        // this is just so IDE doesn't complain, but the loop above always returns or throws
        return parent::fetchAllAssociativeIndexed();
    }

    /**
     * @inheritDoc
     * @return mixed|false False is returned if no rows are found.
     * @throws Exception
     */
    public function fetchOne(): mixed
    {
        $maxTries = ($_ENV['COOLIO_ORM_RETRY_ATTEMPTS'] ?? 0)+1;
        $retrySleep = $_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2;
        $sql = $this->getSQL();

        for ($i=1; $i<=$maxTries; ++$i) {
            try {
                $params = $this->getParameters();

                // Questionmark params can be handled in Doctrine Query Builder
                if (isset($params[0])) {
                    return parent::fetchOne();
                }

                $paramTypes = $this->getParameterTypes();
                Utils::handleArrayInSQLParams($sql, $params);
                return $this->db->fetchOne($sql, $params, $paramTypes);
            }
            catch (Exception\ConnectionException|Exception\ConnectionLost|Exception\RetryableException $e) {
                if ($i == $maxTries) {
                    throw Utils::handleDriverException($e, $sql, $params);
                }
                sleep($retrySleep);
            }
            catch (Exception $e) {
                throw Utils::handleDriverException($e, $sql, $params);
            }
        }

        // this is just so IDE doesn't complain, but the loop above always returns or throws
        return parent::fetchOne();
    }

    /**
     * @inheritDoc
     * @return array<int,mixed>
     * @throws Exception
     */
    public function fetchFirstColumn(): array
    {
        $maxTries = ($_ENV['COOLIO_ORM_RETRY_ATTEMPTS'] ?? 0)+1;
        $retrySleep = $_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2;
        $sql = $this->getSQL();

        for ($i=1; $i<=$maxTries; ++$i) {
            try {
                $params = $this->getParameters();

                // Questionmark params can be handled in Doctrine Query Builder
                if (isset($params[0])) {
                    return parent::fetchFirstColumn();
                }

                $paramTypes = $this->getParameterTypes();
                Utils::handleArrayInSQLParams($sql, $params);
                return $this->db->fetchFirstColumn($sql, $params, $paramTypes);
            }
            catch (Exception\ConnectionException|Exception\ConnectionLost|Exception\RetryableException $e) {
                if ($i == $maxTries) {
                    throw Utils::handleDriverException($e, $sql, $params);
                }
                sleep($retrySleep);
            }
            catch (Exception $e) {
                throw Utils::handleDriverException($e, $sql, $params);
            }
        }

        // this is just so IDE doesn't complain, but the loop above always returns or throws
        return parent::fetchFirstColumn();
    }

    /**
     * Returns 1 Entity If a Manager was set in the constructor
     * @param bool $forceToGetFromDb Optional, Set to TRUE if you wan to skip the EntityRepository and surely fetch it from the db
     * @return Entity|null
     * @throws \LogicException|Exception
     */
    public function fetchOneEntity(bool $forceToGetFromDb = false): ?Entity
    {
        if(!isset($this->entityMgr)) {
            throw new \LogicException("To get entities you must set a Manager in the QueryBuilder constructor() or setEntityManager()");
        }
        return $this->entityMgr->findOne($this->getSQL(), $this->getParameters(), $forceToGetFromDb);
    }

    /**
     * Returns an array Entity If a Manager was set in the constructor
     * @param bool $forceToGetFromDb Optional, Set to TRUE if you wan to skip the EntityRepository and surely fetch it from the db
     * @return array<Entity>
     * @throws \LogicException|Exception
     */
    public function fetchManyEntity(bool$forceToGetFromDb = false): array
    {
        if(!isset($this->entityMgr)) {
            throw new \LogicException("To get entities you must set a Manager in the QueryBuilder constructor() or setEntityManager()");
        }
        return $this->entityMgr->findMany($this->getSQL(), $this->getParameters(), $forceToGetFromDb);
    }

    /**
     * Only works with named params
     * @return string
     */
    public function getSQLNamedParameters(): string
    {
        $sql = $this->getSQL();

        // replace placeholder values in the SQL
        $params = $this->getParameters();
        uksort($params, function($a ,$b) {
            return strlen((string)$b) - strlen((string)$a);
        });
        foreach($params as $k=>$v) {
            if(is_array($v)) {
                $all = '';
                foreach($v as $vv) {
                    if(!empty($all)) {
                        $all .= ',';
                    }
                    if(is_string($vv)) {
                        $all .= "'" . str_replace("'", "''", $vv) . "'";
                    } else {
                        $all .= $vv;
                    }
                }
                $sql = str_replace(':'.$k, $all, $sql);
            } else {
                if (is_string($v)) {
                    $v = "'" . str_replace("'", "''", $v) . "'";
                }
                $sql = str_replace(':'.$k, (string)$v, $sql);
            }
        }

        return $sql;
    }
}