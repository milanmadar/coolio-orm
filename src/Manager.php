<?php

namespace Milanmadar\CoolioORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\ParameterType;

abstract class Manager
{
    protected ORM $orm;
    protected Connection $db;
    protected ?StatementRepository $statementRepo;
    protected EntityRepository $entityRepository;
    protected bool $useEntityRepository;
    protected string $dbConnUrl;
    /** @var array<string> */
    private array $fields;

    /** @var array<string, string> */
    protected array $fieldTypes;

    /** @var string name of the database table */
    protected string $dbTable;

    /** @var string pg, my, ms */
    private string $dbType;

    private static int $placeholderNameIndex = 0;

    /**
     * Entity Manager
     * @param ORM $orm
     * @param Connection $db
     * @param EntityRepository $entityRepositoy
     */
    public function __construct(ORM $orm, Connection $db, EntityRepository $entityRepositoy)
    {
        $this->orm = $orm;
        $this->setDb($db);
        $this->setEntityReposity($entityRepositoy);
        $this->useEntityRepository = true;
        $this->fieldTypes = $this->getFieldTypes();
        $this->dbTable = $this->getDefaultDbTable();
    }

    /**
     * The manager will use this database. Typecally an environment variable like $_ENV['DB_CONNECTION_URL']
     * @return string
     */
    abstract public static function getDbDefaultConnectionUrl(): string;

    /**
     * Should it use the EntityRepository (kinda like an Entity Cache)
     * @param bool $useOrNot
     * @return $this
     */
    public function setUseEntityRepositry(bool $useOrNot): self
    {
        $this->useEntityRepository = $useOrNot;
        if(!$useOrNot) {
            $this->clearRepository(false);
        }
        return $this;
    }

    /**
     * Returns a new empty Entity. (If you are not really doing it from the database row, then use $manager->createEntity())
     * @param array<string, string> $db_data Db table row. (If you are not really doing it from the database row, then use $manager->createEntity())
     * @param bool $skipEntityRepo Optional. If TRUE it will not store this Entity in the Entity Repository
     * @return Entity
     * @throws \LogicException If non of the given data belongs to this Entity
     */
    public function createEntityFromDbData(array $db_data = [], bool $skipEntityRepo = false): Entity
    {
        // Maybe we got some data, but non of that is for this entity. That makes no sense
//        $is_empty_data = empty($db_data);

        $php_data = $this->fromDBdata_toPHPdata($db_data);

//        if(!$is_empty_data && empty($php_data)) {
//            throw new \LogicException("Non of the given data belongs to the ".get_class($this));
//        }

        $this->afterConvertFromDb($php_data);

        return $this->createEntity( $php_data, $skipEntityRepo );
    }

    /**
     * Returns a new empty Entity. (If you are doing it from a database table row then use $manager->createEntityFromDbData())
     * @param array<string, mixed>|null $php_data Already correctly PHP typed. (If you are doing it from a database table row then use $manager->createEntityFromDbData())
     * @param bool $skipEntityRepo Optional. If TRUE it will not store this Entity in the Entity Repository. TOPOGEOMETRY will always skip the Entity Repository
     * @return Entity
     */
    public function createEntity(array|null $php_data = null, bool $skipEntityRepo = false): Entity
    {
        // topology changes the geometry, so always fetch it from the db, so skip the entity repository
        if( !empty( $this->getTopoGeometryFieldInfo() ) ) {
            $skipEntityRepo = true;
        }

        if(!isset($php_data))
        {
            $php_data = $this->getDefaultValues();
        }
        elseif(!empty($php_data['id']) && $this->useEntityRepository && !$skipEntityRepo) // We have the id, so mayb it's already in the Entity EntityRepository?
        {
            $existingEnt = $this->entityRepository->getByDbId($php_data['id'], $this->dbTable .$this->getDbConnUrl());
            if(isset($existingEnt))
            {
                // See if some new data should be added to the existing Entity
                // (we don't overwrite what's already in the existing Entity, just add new stuff)
                $addFieldNames = array_diff(array_keys($php_data), array_keys($existingEnt->_getData()));
                if(!empty($addFieldNames)) {
                    foreach ($addFieldNames as $fieldName) {
                        // This should call the user defined setters, but we can't be sure if they exist with the same name
                        $existingEnt->_set($fieldName, $php_data[$fieldName]);
                    }
                }

                // Return the existing entity, now filled with additional data if needed
                return $existingEnt;
            }
        }

        $newEnt = static::createEntityDo($this->orm, $php_data);

        // Wasn't in the Repo (or we aren't using it), so create it and add it to the Repo
        if($this->useEntityRepository && !$skipEntityRepo) {
            $this->entityRepository->add($newEnt, $this->dbTable.$this->getDbConnUrl());
        }

        return $newEnt;
    }

    /**
     * Returns a new empty Entity
     * @param ORM $orm So we can handle Related Entities
     * @param array<string, mixed> $php_data Already correct PHP tyed
     * @return Entity
     */
    abstract protected function createEntityDo(ORM $orm, array $php_data = []): Entity;

    /**
     * Called after the Db data has been converted into PHP types (so after $manager->fromDBdata_toPHPdata()), and before its passed to the Entity constructor
     * @param array<string, mixed> $php_data By Reference!
     */
    abstract protected function afterConvertFromDb(array &$php_data): void;

    /**
     * Called just before the PHP data is about to be saved to the Db (so before $manager->fromPHPdata_toDBdata())
     * @param array<string, mixed> $data By Reference!
     */
    abstract protected function beforeToDb(array &$data): void;

    /**
     * @return array<string, string>
     */
    abstract public function getFieldTypes(): array;

    /**
     * Info for topogeometry type fields
     * @return array<string, array{'topology_name':string, 'topology_layer':int, 'tolerance':float}>
     */
    abstract public function getTopoGeometryFieldInfo(): array;

    /**
     * @param string $column
     * @return null|array{'topology_name':string, 'topology_layer':int, 'tolerance':float}
     */
    public function getTopoGeometryFieldInfo_column(string $column): array|null
    {
        return $this->getTopoGeometryFieldInfo()[$column] ?? null;
    }

    /**
     * All the field names
     * @param array<string>|null $exceptFields
     * @return array<string>
     */
    public function getFields(array|null $exceptFields = null): array
    {
        if(isset($exceptFields)) {
            $allFields = $this->getFieldTypes();
            foreach($exceptFields as $exceptField) {
                if(isset($allFields[$exceptField])) {
                    unset($allFields[$exceptField]);
                }
            }
            return array_keys($allFields);
        }

        if(!isset($this->fields)) {
            $this->fields = array_keys($this->getFieldTypes());
        }

        return $this->fields;
    }

    /**
     * Tells if this field belongs to this Manager (this Entity, this table)
     * @param string $field
     * @return bool
     */
    public function hasField(string $field): bool
    {
        return isset($this->getFieldTypes()[$field]);
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function getDefaultValues(): array;

    /**
     * Default Database table name
     * @return string
     */
    abstract public function getDefaultDbTable(): string;

    /**
     * @return string
     */
    public function getDbTable(): string
    {
        return $this->dbTable;
    }

    /**
     * @param string $dbTable
     * @return $this
     */
    public function setDbTable(string $dbTable): self
    {
        $this->dbTable = $dbTable;
        return $this;
    }

    /**
     * Returns the db connection url
     * @return string
     */
    public function getDbConnUrl(): string
    {
        return $this->dbConnUrl;
    }

    /**
     * Returns the db connection url
     * @param string $dbConnUrl
     * @return $this
     */
    public function setDbConnUrl(string $dbConnUrl): self
    {
        $db = $this->orm->getDbByUrl($dbConnUrl);
        $this->setDb($db);
        return $this;
    }

    /**
     * The Database
     * @return Connection
     */
    public function getDb(): Connection
    {
        return $this->db;
    }

    /**
     * Change database (also changes the StatementRepository if needed)
     * @param Connection $db
     * @return $this
     */
    public function setDb(Connection $db): self
    {
        $ischangingDb = (isset($this->db) && $this->db !== $db); // @phpstan-ignore-line
        $this->db = $db;
        $this->statementRepo = $this->orm->getStatementRepositoryByConnection($db);
        if($ischangingDb) {
            $this->clearRepository(true);
        }
        $this->dbConnUrl = Utils::getDbConnUrl($this->db);

        if(str_contains($this->dbConnUrl, 'pgsql')) {
            $this->dbType = 'pg';
        } elseif(str_contains($this->dbConnUrl, 'sqlsrv')) {
            $this->dbType = 'ms';
        } else {
            $this->dbType = 'my';
        }

        return $this;
    }

    /**
     * Change database (also changes the StatementRepository if needed)
     * @param string $connUrl
     * @return $this
     */
    public function setDbByConnectionUrl(string $connUrl): self
    {
        return $this->setDb($this->orm->getDbByUrl($connUrl));
    }

    /**
     * Returns 1 Entity
     * @param int|null $id
     * @param bool $forceToGetFromDb Optional, Set to TRUE if you wan to skip the EntityRepository and surely fetch it from the db
     * @return Entity|null
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \InvalidArgumentException
     */
    public function findById(?int $id, bool $forceToGetFromDb = false): ?Entity
    {
        if(!isset($id)) return null;

        // topology changes the geometry, so always fetch it from the db, so skip the entity repository
        if( !empty( $this->getTopoGeometryFieldInfo() ) ) {
            $forceToGetFromDb = true;
        }

        // If its in the Entity Repository, return what we already have
        if($this->useEntityRepository && !$forceToGetFromDb) {
            $existingEnt = $this->entityRepository->getByDbId($id, $this->dbTable.$this->getDbConnUrl());
            if (isset($existingEnt)) {
                return $existingEnt;
            }
        }

        // Get it from to the database
        return $this->findOneWhere('id = ?', [$id], $forceToGetFromDb);
    }

    /**
     * Returns 1 Entity. USED BY ENTITY RELATIONS
     * @param string $field
     * @param mixed $value
     * @param bool $forceToGetFromDb Optional, Set to TRUE if you wan to skip the EntityRepository and surely fetch it from the db
     * @return Entity|null
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \InvalidArgumentException
     */
    public function findByField(string $field, mixed $value, bool $forceToGetFromDb = false): ?Entity
    {
        if($field == 'id') {
            return $this->findById(isset($value) ? (int)$value : null, $forceToGetFromDb);
        }

        if(!isset($value)) {
            return null;
        }

        // Get it from the database
        return $this->findOneWhere($field.' = ?', [$value], $forceToGetFromDb);
    }

    /**
     * Returns 1 Entity
     * @param string $sqlAfterWHERE Example: 'field=? ORDER BY field ASC GROUP BY field'
     * @param array<mixed>|array<string, mixed> $binds Optional
     * @param bool $forceToGetFromDb Optional. Set to TRUE if you want to skip the EntityRepository and surely fetch it from the db
     * @return Entity|null
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \InvalidArgumentException
     */
    public function findOneWhere(string $sqlAfterWHERE, array $binds = [], bool $forceToGetFromDb = false): ?Entity
    {
        return $this->findOne("SELECT * FROM ".$this->dbTable." WHERE ".$sqlAfterWHERE, $binds, $forceToGetFromDb);
    }

    /**
     * Returns 1 Entity
     * @param string $sql
     * @param array<string|int, mixed> $binds Optional
     * @param bool $forceToGetFromDb Optional, Set to TRUE if you wan to skip the EntityRepository and surely fetch it from the db
     * @return Entity|null
     * @throws \Doctrine\DBAL\Exception
     * @throws \InvalidArgumentException
     */
    public function findOne(string $sql, array $binds = [], bool $forceToGetFromDb = false): ?Entity
    {
        $sql = Geo\GeoQueryProcessor::processQuery($sql, $this);
        $row = Utils::executeQuery_bindValues($sql, $binds, $this->db, $this->statementRepo)->fetchAssociative();
        return $row ? $this->createEntityFromDbData($row, $forceToGetFromDb) : null;
    }

    /**
     * Returns an array of Entities
     * @param string $sqlAfterWHERE Example: 'field=? ORDER BY field ASC GROUP BY field'
     * @param array<mixed>|array<string, mixed> $binds Optional
     * @param bool $forceToGetFromDb Optional, Set to TRUE if you wan to skip the EntityRepository and surely fetch it from the db
     * @return array<Entity>
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \InvalidArgumentException
     */
    public function findManyWhere(string $sqlAfterWHERE, array $binds = [], bool $forceToGetFromDb = false): array
    {
        return $this->findMany("SELECT * FROM ".$this->dbTable." WHERE ".$sqlAfterWHERE, $binds, $forceToGetFromDb);
    }

    /**
     * Returns an array of Entities
     * @param string $sql
     * @param array<string|int, mixed> $binds Optional
     * @param bool $forceToGetFromDb Optional, Set to TRUE if you wan to skip the EntityRepository and surely fetch it from the db
     * @return array<Entity>
     * @throws \Doctrine\DBAL\Exception
     * @throws \InvalidArgumentException
     */
    public function findMany(string $sql, array $binds = [], bool $forceToGetFromDb = false): array
    {
        $arrRes = [];
        $sql = Geo\GeoQueryProcessor::processQuery($sql, $this);
        $doctrineRes = Utils::executeQuery_bindValues($sql, $binds, $this->db, $this->statementRepo);
        while($row = $doctrineRes->fetchAssociative()) {
            $arrRes[] = $this->createEntityFromDbData($row, $forceToGetFromDb);
        }
        return $arrRes;
    }

    /**
     * NEW SQL Builder. It extends Doctrine DBAL SQL Query Builder.<br>
     * Table ('FROM') already set
     * @return QueryBuilder
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return (new QueryBuilder( $this->orm, $this->db, $this ))
            //->select('*') // now its set with $qb->autoSetSelect() because we do magic with the '*' select
            //->from($this->dbTable) // now its set with $qb->autoSetFrom() because Doctrine ADDs a new FROM expression to the query every time you call ->from()
            ;
    }

    /**
     * Save the Entity to the database
     * @param Entity $ent
     * @throws \Doctrine\DBAL\Exception
     * @throws \LogicException When the Entity was deleted. To get the 'id' it had, call _getDeletedId().
     * @Event Event\EntityEventTypeEnum::DATA_CHANGED , @EventArg [string:'id', int:'new id', null]
     * @Event Event\EntityEventTypeEnum::ID_CHANGED , @EventArg [int|null:'new value', int|null:'old value]
     */
    public function save(Entity $ent): void
    {
        if($ent->_isDeleted()) {
            throw new \LogicException(get_class($ent) . ' was deleted (if not yet commited, you can still $entity->_rollback()). To get the "id" it had call $entity->_getDeletedId()');
        }

        // save the unsaved related entities
        $entityRelations = $ent->_getRelatedEntities();
        foreach($entityRelations as $_field=>$relationObj) {
            $relatedEntity = $relationObj->_getRefEntityNotFromDb($ent);
            if(isset($relatedEntity) && !$relatedEntity->hasId()) {
                $this->orm->entityManager( $relationObj->getRefMgrClass(), $this->db)->save( $relatedEntity );
            }
        }

        $forceInsert = $ent->_getForceInsertOnNextSave();
        if($forceInsert || !$ent->hasId()) // INSERT
        {
            $dataToSave = $ent->_getData();
            $this->beforeToDb($dataToSave);

            try {
                $this->insert($dataToSave);
            }
            catch (\Doctrine\DBAL\Exception\ConnectionException|\Doctrine\DBAL\Exception\ConnectionLost|\Doctrine\DBAL\Exception\RetryableException $e) {
                sleep($_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2);
                $this->insert($dataToSave);
            }
            // @codeCoverageIgnoreStart
            catch(\Doctrine\DBAL\Exception $e) {
                // log the data too, but truncate the too long things
                foreach($dataToSave as $field=>$value) {
                    if(is_string($value) && mb_strlen($value, 'UTF-8') > 1024) {
                        $dataToSave[$field] = mb_substr($value, 0, 1018).'[...]';
                    }
                }
                throw Utils::handleDriverException($e, "Manager::save() INSERT ".get_class($this), $dataToSave);
            }
            // @codeCoverageIgnoreEnd

            if($forceInsert) {
                $ent->_setForceInsertOnNextSave(false);
            } else {
                $ent->setId(intval($this->db->lastInsertId()));
            }

            $ent->_commit();
        }
        elseif($ent->_didDataChange()) // UPDATE
        {
            $dataToSave = $ent->_getDataChanged();
            $this->beforeToDb($dataToSave);

            try {
                // its the primary id that changed
                $whereId = isset($dataToSave['id'])
                    ? $ent->_getDataOrigi()['id']
                    : $ent->_get('id');

                $this->update($dataToSave, ['id' => $whereId]);
            }
            // @codeCoverageIgnoreStart
            catch (\Doctrine\DBAL\Exception\ConnectionException|\Doctrine\DBAL\Exception\ConnectionLost|\Doctrine\DBAL\Exception\RetryableException $e) {
                try {
                    sleep($_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2);
                    $this->update($dataToSave, ['id' => $whereId]);
                }
                catch (\Doctrine\DBAL\Exception $e) {
                    // log the data too, but truncate the too long things
                    foreach ($dataToSave as $field => $value) {
                        if (is_string($value) && mb_strlen($value, 'UTF-8') > 1024) {
                            $dataToSave[$field] = mb_substr($value, 0, 1000) . '... [truncated for log]';
                        }
                    }
                    throw Utils::handleDriverException($e, "Manager::save() UPDATE " . get_class($this) . " (id:" . $ent->_get('id') . ")", $dataToSave);
                }
            }
            // @codeCoverageIgnoreEnd

            $ent->_commit();
        }
    }

    /**
     * Deletes the Entity and Marks the Entity as deleted.
     * <br>It will clead all its data and it will no longer be possible to set/get any data on it
     * <br>$entity->isDeleted() will return true after this.
     * <br>$entity->_rollback() is possible after this, to set back its last commited state
     * <br>After $entity->delete() and $entity->_commit(), its not possibel to $entity->_rollback()
     * <br>------
     * <br>EVENTS: EventDeleted IS SENT AND THE id CHANGE RELATED EVENTS IF APPLICABLE (EventDataChanged, EventIdChanged). THE OTHER data CHANGES ARE NOT SENDING THE EventDataChanged EVENTS.
     * @param Entity $ent
     * @Event Event\EntityEventTypeEnum::DELETED , @EventArg int|null (the old id)
     * @Event Event\EntityEventTypeEnum::ID_CHANGED , @EventArg [string:'id', null, int:'old id']
     * @Event Event\EntityEventTypeEnum::ID_CHANGED , @EventArg [null, int:'old id']
     * @throws \Doctrine\DBAL\Exception
     */
    public function delete(Entity $ent): void
    {
        if($ent->_isDeleted()) {
            return;
        }

        $ent->_delete();
        $ent->_commit();

        // Only bother the db if it had an id at all
        $oldId = $ent->_getDeletedId();
        if(!empty($oldId)) {
            try {
                $this->db->delete($this->dbTable, ['id' => $oldId]);
            }
            catch (\Doctrine\DBAL\Exception\ConnectionException|\Doctrine\DBAL\Exception\ConnectionLost|\Doctrine\DBAL\Exception\RetryableException $e) {
                try {
                    sleep($_ENV['COOLIO_ORM_RETRY_SLEEP'] ?? 2);
                    $this->db->delete($this->dbTable, ['id' => $oldId]);
                }
                catch (\Doctrine\DBAL\Exception $e) {
                    throw Utils::handleDriverException($e, "Manager::delete() (" . get_class($ent) . " ,id: " . $oldId . ")", null);
                }
            }
        }
    }

    /**
     * Truncates the table
     * @param string $confirm Pass: "I know what I'm doing!"
     * @param bool $fkOff Foreign Key Checks Off
     */
    public function truncate(string $confirm, bool $fkOff = false): void
    {
        if($confirm != "I know what I'm doing!") {
            throw new \InvalidArgumentException(get_class($this)."::truncate() seems like you don't know what you are doing, so don't do it");
        }
        if($fkOff) {
            $this->db->executeStatement("SET foreign_key_checks = 0");
        }
        try {
            $this->db->executeStatement('TRUNCATE '.$this->getDbTable());
        } finally {
            if($fkOff) {
                $this->db->executeStatement("SET foreign_key_checks = 1");
            }
        }
    }

    /**
     * For tests only
     * @return EntityRepository
     * @internal
     */
    public function _getEntityRepository(): EntityRepository
    {
        return $this->entityRepository;
    }

    /**
     * @param EntityRepository $entityRepository
     * @return $this
     */
    public function setEntityReposity(EntityRepository $entityRepository): self
    {
        $this->entityRepository = $entityRepository;
        return $this;
    }

    /**
     * Removes all Entities from the Entity EntityRepository
     * @param bool $allTables FALSE=clear only for the table of this Entity. TRUE=clear the entire repository for all tables (all Entity types).
     */
    public function clearRepository(bool $allTables): void
    {
        $this->entityRepository->clear($allTables ? null : $this->dbTable.$this->getDbConnUrl());
    }

    /**
     * Deletes the rows where each field column name matches the value
     * @param array<string, mixed> $fields <column name, value>
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteByField(array $fields): int
    {
        $qb = $this->createQueryBuilder()->delete();
        foreach($fields as $k=>$v){
            if(! $this->hasField($k)){
                throw new \InvalidArgumentException($this->dbTable." has no field '$k'");
            }
            
            $qb = $qb->andWhereColumn($k, '=', $v);
        }
        return $qb->executeStatement();
    }

    /**
     * @param array<string, mixed> $data
     * @return int|string
     * @throws \Doctrine\DBAL\Exception
     */
    private function insert(array $data): int|string
    {
        if (count($data) === 0) {
            return $this->db->executeStatement('INSERT INTO ' . $this->dbTable . ' () VALUES ()');
        }

        [$columns, $values, $placeholders, $types] = $this->fromPHPdata_toDBdata($data);

        $escapedColumns = [];
        foreach($columns as $col) {
            $escapedColumns[] = Utils::escapeColumnName($col, $this->dbType);
        }

        return $this->db->executeStatement(
            'INSERT INTO ' . $this->dbTable . ' (' . implode(', ', $escapedColumns) . ')' .
            ' VALUES (' . implode(', ', $placeholders) . ')',
            $values,
            $types,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $criteria
     * @return int|string
     * @throws \Doctrine\DBAL\Exception
     */
    private function update(array $data, array $criteria = []): int|string
    {
        [$columns, $values, $placeholders, $types] = $this->fromPHPdata_toDBdata($data);

        $set = [];
        foreach ($columns as $i => $colName) {
            $set[] = Utils::escapeColumnName($colName, $this->dbType) . ' = ' . $placeholders[$i];
        }

        $sql = 'UPDATE ' . $this->dbTable . ' SET ' . implode(', ', $set);

        if (!empty($criteria))
        {
            [$where_columns, $where_values, $where_placeholders, $where_types] = $this->fromPHPdata_toDBdata($criteria);

            $whereConditions = [];
            foreach($where_columns as $i => $colName) {
                $whereConditions[] = $colName . ' = ' . $where_placeholders[$i];
            }
            $values = array_merge($values, $where_values);
            $types = array_merge($types, $where_types);

            $sql .= ' WHERE ' . implode(' AND ', $whereConditions);
        }

        return $this->db->executeStatement($sql, $values, $types);
    }

    /**
     * @param array<string, mixed> $phpData
     * @return array{ array<string>, array<string, mixed>, array<string>, array<string, mixed> }
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Types\ConversionException
     */
    private function fromPHPdata_toDBdata(array $phpData): array
    {
        $columns      = [];
        $values       = [];
        $placeholders = [];
        $types        = [];

        foreach($phpData as $colName=> $val)
        {
            $columns[] = $colName;

            if(!isset($val))
            {
                $placeholders[] = 'NULL';
//                $p = 'mgrPm' . ++self::$placeholderNameIndex;
//                $placeholders[] = $p;
//                $values[$p] = null;
//                $types[$p] = ParameterType::STRING;
            }
            elseif(isset($this->fieldTypes[$colName]))
            {
                switch ($this->fieldTypes[$colName]) {
                    case 'date':
                    case 'time':
                    case 'datetime':
                    case 'datetime2':
                    case 'smalldatetime':
                    case 'datetimeoffset':
                    case 'timestamp':
                    case 'timestamp_tz':
                    case 'timestamp_micro':
                    case 'timestamp_tz_micro':
                        $p = 'mgrPm' . ++self::$placeholderNameIndex;
                        $placeholders[] = ':'.$p;
                        if($val instanceof \DateTime) {
                            $values[$p] = match($this->fieldTypes[$colName]) {
                                'date' => $val->format('Y-m-d'),
                                'time' => $val->format('H:i:s'),
                                'datetime', 'datetime2', 'smalldatetime', 'datetimeoffset', 'timestamp' => $val->format('Y-m-d H:i:s'),
                                'timestamp_tz_micro' => $val->format('Y-m-d H:i:s.uP'),
                                'timestamp_micro' => $val->format('Y-m-d H:i:s.u'),
                                default => $val->format('Y-m-d H:i:sP'),
                            };
                        } else {
                            $values[$p] = (string)$val;
                        }
                        $types[$p] = ParameterType::STRING;
                        break;
                    case 'string':
                    case 'text':
                        $p = 'mgrPm' . ++self::$placeholderNameIndex;
                        $placeholders[] = ':'.$p;
                        $values[$p] = (string)$val;
                        $types[$p] = ParameterType::STRING;
                        break;
                    case 'integer':
                    case 'smallint':
                    case 'bigint':
                    case 'boolean':
                        $p = 'mgrPm' . ++self::$placeholderNameIndex;
                        $placeholders[] = ':'.$p;
                        $values[$p] = (int)$val;
                        $types[$p] = ParameterType::INTEGER;
                        break;
                    case 'float':
                    case 'decimal':
                        $p = 'mgrPm' . ++self::$placeholderNameIndex;
                        $placeholders[] = ':'.$p;
                        $values[$p] = (float)$val;
                        $types[$p] = ParameterType::STRING;
                        break;
                    case 'array':
                    case 'simple_array':
                        $p = 'mgrPm' . ++self::$placeholderNameIndex;
                        $placeholders[] = ':'.$p;
                        $values[$p] = serialize($val);
                        $types[$p] = ParameterType::STRING;
                        break;
                    case 'json':
                    case 'json_array':
                        $p = 'mgrPm' . ++self::$placeholderNameIndex;
                        $placeholders[] = ':'.$p;
                        $jsonStr = json_encode($val);
                        if($jsonStr === false) {
                            $values[$p] = json_encode(['json'=>'errored']);
                        } else {
                            $values[$p] = $jsonStr;
                        }
                        $types[$p] = ParameterType::STRING;
                        break;
                    case 'geometry':
                    case 'geometry_curved':
                    case 'topogeometry':
                        [$geo_sqlPart, $geo_paramValues, $geo_paramTypes] = Geo\GeoQueryProcessor::geoFunction_sqlPart_andParams(
                            $this,
                            $colName,
                            $val
                        );
                        $placeholders[] = $geo_sqlPart;
                        $values = array_merge($values, $geo_paramValues);
                        $types = array_merge($types, $geo_paramTypes);
                        break;
                    default:
                        $p = 'mgrPm' . ++self::$placeholderNameIndex;
                        $placeholders[] = ':'.$p;
                        $values[$p] = Type::getType($this->fieldTypes[$colName])->convertToDatabaseValue($val, $this->db->getDatabasePlatform());
                        $types[$p] = ParameterType::STRING;
                        break;
                }
            }
            else
            {
                $p = 'mgrPm' . ++self::$placeholderNameIndex;
                $placeholders[] = ':'.$p;
                $values[$p] = $val;
                $types[$p] = ParameterType::STRING;
            }
        }

        return [$columns, $values, $placeholders, $types];
    }

    /**
     * Change the types
     * @param array<string, mixed> $dbData
     * @return array<string, mixed>
     */
    protected function fromDBdata_toPHPdata(array $dbData): array
    {
        foreach($dbData as $k=> $v)
        {
            /*if(!isset($this->fieldTypes[$k])) // this field doesn't belong to this entity
            {
                unset($data[$k]);
            }
            else*/
            if(isset($v)) {
                if(!str_ends_with($k, '_srid')) {
                    $dbData[$k] = match($this->fieldTypes[$k] ?? 'doesnt_belong_to_this_entity') {
                        'doesnt_belong_to_this_entity' => $v,
                        'string', 'text' => (string)$v,
                        'integer', 'smallint', 'bigint' => (int)$v,
                        'float', 'decimal' => (float)$v,
                        'boolean' => (bool)$v,
                        'array', 'simple_array' => unserialize($v),
                        'json', 'json_array' => json_decode($v, true),
                        'date', 'time', 'datetime', 'datetime2', 'smalldatetime', 'datetimeoffset', 'timestamp', 'timestamp_tz', 'timestamp_micro', 'timestamp_tz_micro' => new \DateTime($v),
                        //'geometry' => Geo\Shape2D3DFactory::createFromGeoJSONString($v, $data[$k.'_srid'] ?? null),
                        //'geometry_curved', 'topogeometry' => Geo\Shape2D3DFactory::createFromGeoEWKTString($v),
                        'geometry' , 'geometry_curved', 'topogeometry' => Geo\Shape2D3DFactory::createFromGeoEWKTString($v),
                        default => Type::getType($this->fieldTypes[$k])->convertToPHPValue($v, $this->db->getDatabasePlatform()),
                    };
                }
            }
            else // this else was not here and all test passed
            {
                $dbData[$k] = null;
            }
        }
        return $dbData;
    }

}