<?php

namespace Milanmadar\CoolioORM;

class EntityRepository
{
    /** @var int For testing */
    public static int $_Testing_Instance_Counter_ = 0;

    /**
     * The spl_object_id() of the Entities
     * @var array<string, array<int, array{0:Entity, 1:int|null}>>
     */
    private array $splIds;

    /**
     * The database 'id' fields of the Entities
     * @var array<string, array<int, array{0:Entity, 1:int}>>
     */
    private array $dbIds;

    /** @var array<int, string> */
    private array $splIdToDbTable;

    /** @var int It can store maximum this amount of Enities, then it will clear the largest table */
    private int $maxEntityCount;

    /** @var int How many are stored currently */
    private int $currentEntityCount;

    /**
     * Entity EntityRepository constructor.
     */
    public function __construct()
    {
        ++self::$_Testing_Instance_Counter_;
        $this->maxEntityCount = $_ENV['COOLIO_ORM_ENTITY_REPO_MAX_ITEMS'] ?? 10000;
        $this->clear();
    }

    /**
     * It can store maximum this amount of Enities, then it will clear the largest table
     * @return int
     */
    public function getMaxEntityCount(): int
    {
        return $this->maxEntityCount;
    }

    /**
     * It can store maximum this amount of Enities, then it will clear the largest table
     * @param int $maxEntityCount
     * @return $this
     */
    public function setMaxEntityCount(int $maxEntityCount): self
    {
        $this->maxEntityCount = $maxEntityCount;
        return $this;
    }

    /**
     * The given entity will be added the repo
     * @param Entity $ent
     * @param string $dbTable
     * @throws \LogicException
     */
    public function add(Entity $ent, string $dbTable): void
    {
        // Repo is full
        if($this->currentEntityCount >= $this->maxEntityCount)
        {
            // clear all tables
            $this->clear();

            /*// find the largest table
            $_max = 0;
            $_maxTable = '';
            $_tables = array_keys($this->splIds);
            foreach($_tables as $_table) {
                $_cnt = count($this->splIds[$_table]);
                if($_cnt > $_max) {
                    $_maxTable = $_table;
                    $_max = $_cnt;
                }
            }

            // cleare the largest table
            $this->clear($_maxTable);*/
        }

        $splId = spl_object_id($ent);

        // Create splId storate for this dbTable
        if(!isset($this->splIds[$dbTable])) {
            $this->splIds[$dbTable] = [];
        } elseif(isset($this->splIds[$dbTable][$splId])) {
            throw new \LogicException("splId (".$splId.") already in EntityRepository");
        }

        // Create dbId storate for this dbTable
        if(!isset($this->dbIds[$dbTable])) {
            $this->dbIds[$dbTable] = [];
        }

        // Add to dbIds: The db 'id' comes differently for deleted Entities
        /** @var int|null $dbId */
        $dbId = $ent->_isDeleted() ? $ent->_getDeletedId() : $ent->_get('id');
        if(isset($dbId)) {
            //assert( !isset($this->dbIds[$dbTable][$dbId]), new \LogicException($dbTable.".id=".$dbId." already exists") );
            if (isset($this->dbIds[$dbTable][$dbId])) throw new \LogicException($dbTable.".id=".$dbId." already exists");
            $this->dbIds[$dbTable][$dbId] = [$ent, $splId];
        }

        // Add to splIds
        $this->splIds[$dbTable][$splId] = [$ent, $dbId];

        // Scribe to important events
        $ent->eventSubscribe( Event\EntityEventTypeEnum::ID_CHANGED, $this, 'onEntityIdChanged' );
        $ent->eventSubscribe( Event\EntityEventTypeEnum::DESTRUCT, $this, 'onEntityDestruct' );
        // Remember the dbTable of this splId (used in onEntityIdChanged() and onEntityDestruct())
        $this->splIdToDbTable[$splId] = $dbTable;

        ++$this->currentEntityCount;
    }

    /**
     * The given entity will be deleted from the repo
     * @param Entity $ent
     * @param string $dbTable
     */
    public function del(Entity $ent, string $dbTable): void
    {
        $splId = spl_object_id($ent);
        if(isset($this->splIds[$dbTable][$splId]))
        {
            // Remove from dbIds
            $dbId = $this->splIds[$dbTable][$splId][1];
            if(isset($dbId)) {
                unset($this->dbIds[$dbTable][$dbId]);
            }

            // Remove from splId
            unset($this->splIds[$dbTable][$splId]);
            unset($this->splIdToDbTable[$splId]);

            --$this->currentEntityCount;
        }
    }

    /**
     * Tells how many Entities are stored in total
     * @param string|null $dbTable If given only the Entities for this table will be counted
     * @return int
     */
    public function count(string|null $dbTable = null): int
    {
        if(isset($dbTable)) {
            return empty($this->splIds[$dbTable]) ? 0 : count($this->splIds[$dbTable]);
        } else {
            return $this->currentEntityCount;
        }
    }

    /**
     * Returns an Entity or null
     * @param int $dbId
     * @param string $dbTable
     * @return Entity|null
     */
    public function getByDbId(int $dbId, string $dbTable): ?Entity
    {
        return $this->dbIds[$dbTable][$dbId][0] ?? null;
    }

    /**
     * Removes everything
     * @param string|null $dbTable If given, only this table will be emptied
     */
    public function clear(?string $dbTable = null): void
    {
        if(isset($dbTable))
        {
            if(!empty($this->splIds[$dbTable]))
            {
                $count = count($this->splIds[$dbTable]);

                foreach($this->splIds[$dbTable] as $splId=>$_)  {
                    unset($this->splIdToDbTable[$splId]);
                }
                unset($this->splIds[$dbTable]);
                unset($this->dbIds[$dbTable]);
            }
        }
        else
        {
            $this->splIds = [];
            $this->dbIds = [];
            $this->splIdToDbTable = [];
        }

        $this->currentEntityCount = 0;
    }

    /**
     * @param Entity $ent
     * @param array<int|null, int|null> $args [int|null:'new id', int|null:'old id']
     * @throws \LogicException
     */
    public function onEntityIdChanged(Entity $ent, array $args): void
    {
        $evArgOldId = $args[1];
        $evArgNewId = $args[0];

        $splId = spl_object_id($ent);
        if (isset($this->splIdToDbTable[$splId]))
        {
            $dbTable = $this->splIdToDbTable[$splId];

            assert( isset($this->splIds[$dbTable][$splId]), new \LogicException('id('.$evArgOldId.'->'.$evArgNewId.') $this->splIds["'.$dbTable.'"]['.$splId.'] doesnt exist'));

            $storedOldDbId = $this->splIds[$dbTable][$splId][1];

            // Validate: the Repo's stored old dbId must match with the one the Event says
            //assert($evArgOldId === $storedOldDbId, new \LogicException("id('.$evArgOldId.'->'.$evArgNewId.') The DB id in Reposiroty (".$storedOldDbId.") is not in sync with the EventArg old id (".$evArgOldId.")") );
            if($evArgOldId !== $storedOldDbId) throw new \LogicException("id('.$evArgOldId.'->'.$evArgNewId.') The DB id in Reposiroty (".$storedOldDbId.") is not in sync with the EventArg old id (".$evArgOldId.")");

            if(isset($evArgOldId)) // (123 -> 123) OR (123 -> null) The event says there was an old id
            {
                // Validate: Make sure we have the old db key for real
                //assert( isset($this->dbIds[$dbTable][$storedOldDbId]), new \LogicException('id('.$evArgOldId.'->'.$evArgNewId.') Missing $this->dbIds["'.$dbTable.'"]['.$storedOldDbId.'] from Repo'));
                if(!isset($this->dbIds[$dbTable][$storedOldDbId])) throw new \LogicException('id('.$evArgOldId.'->'.$evArgNewId.') Missing $this->dbIds["'.$dbTable.'"]['.$storedOldDbId.'] from Repo');

                if(isset($evArgNewId)) // (123 -> 123) There was an old id, and there's also a new id
                {
                    // Make sure this new db id is free in the Repo's dbIds
                    //assert(!isset($this->dbIds[$dbTable][$evArgNewId]), throw new \LogicException('id('.$evArgOldId.'->'.$evArgNewId.') New db id slot should be free in $this->dbIds["'.$dbTable.'"]['.$evArgNewId.'], but its not (old id existed)'));
                    if(isset($this->dbIds[$dbTable][$evArgNewId])) throw new \LogicException('id('.$evArgOldId.'->'.$evArgNewId.') New db id slot should be free in $this->dbIds["'.$dbTable.'"]['.$evArgNewId.'], but its not (old id existed)');

                    // Remove the old dbIds info
                    unset($this->dbIds[$dbTable][$storedOldDbId]);

                    // Add the new dbIds info
                    $this->dbIds[$dbTable][$evArgNewId] = [$ent, $splId];
                }
                else // (123 -> null) There was an old id, and there's no a new id
                {
                    // Remove the old dbIds info
                    unset($this->dbIds[$dbTable][$storedOldDbId]);
                }
            }
            else // (null -> 123) The event says there wasn't an old id, then now there must be a new id (otherwise the Event would come coz there was no change)
            {
                // Make sure this new db id is free in the Repo's dbIds
                //assert(!isset($this->dbIds[$dbTable][$evArgNewId]), throw new \LogicException('id('null->'.$evArgNewId.') New db id slot should be free in $this->dbIds["'.$dbTable.'"]['.$evArgNewId.'], but its not (old id didnt exist)'));
                if(isset($this->dbIds[$dbTable][$evArgNewId])) throw new \LogicException('id(null->'.$evArgNewId.') New db id slot should be free in $this->dbIds["'.$dbTable.'"]['.$evArgNewId.'], but its not (old id didnt exist). The Entity data there looks like this: '.print_r($this->dbIds[$dbTable][$evArgNewId][0]->_getData(), true));

                // Add the new dbIds info
                /** @var int $evArgNewId */
                $this->dbIds[$dbTable][$evArgNewId] = [$ent, $splId];
            }

            // Change splIds arr
            $this->splIds[$dbTable][$splId][1] = $evArgNewId;

            $this->currentEntityCount = count($this->splIdToDbTable);
        }
    }

    public function onEntityDestruct(Entity $ent): void
    {
        $splId = spl_object_id($ent);
        if($dbTable = $this->splIdToDbTable[$splId] ?? null) {
            $this->del($ent, $dbTable);
        }
    }

    /**
     * For testing
     * @internal
     * @return string
     */
    public function _debug(): string
    {
        $r = '';
        foreach($this->splIds as $table=>$arr) {
            $r .= 'splIds: '.$table.': '.count($arr)."\n";
        }
        foreach($this->dbIds as $table=>$arr) {
            $r .= 'dbIds: '.$table.': '.count($arr)."\n";
        }
        $r .= 'splIdToDbTable: '.count($this->splIdToDbTable)."\n";
        return $r;
    }

}