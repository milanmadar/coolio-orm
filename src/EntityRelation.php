<?php

namespace Milanmadar\CoolioORM;

class EntityRelation
{
    private ORM $orm;
    private ?Entity $refEnt;
    private string $fld;
    private string $refFld;
    /** @var class-string<Manager> */
    private string $refMgrClass;

    /**
     * @param ORM $orm
     * @param string $fld
     * @param class-string<Manager> $refMgrClass
     */
    public function __construct(ORM $orm, string $fld, string $refFld, string $refMgrClass)
    {
        $this->orm = $orm;
        $this->fld = $fld;
        $this->refFld = $refFld;
        $this->refMgrClass = $refMgrClass;
        $this->refEnt = null;
    }

    /**
     * @return class-string<Manager>
     */
    public function getRefMgrClass(): string
    {
        return $this->refMgrClass;
    }

    public function getRefEntity(Entity $ownerEnt): ?Entity
    {
        if(!isset($this->refEnt))
        {
            $value = $ownerEnt->_get($this->fld);
            if(!empty($value))
            {
                $refEnt = $this->orm->entityManager($this->refMgrClass)->findByField($this->refFld, $value);
                $this->setRefEntity($ownerEnt, $refEnt);
            }
        }
        return $this->refEnt;
    }

    public function _getRefEntityNotFromDb(Entity $ownerEnt): ?Entity
    {
        return $this->refEnt;
    }

    public function setRefEntity(Entity $ownerEnt, ?Entity $refEnt): void
    {
        // If we already had a referenced entity, and its changing, then ubsubscribe from that old one
        // UNIT TESTS PASS WITH THIS SECTION AND WITHOUT THIS SECTION TOO, BUT I THINK ITS NEEDED
        if(isset($this->refEnt))
        {
            if(!isset($refEnt) || $this->refEnt !== $refEnt)
            {
                if($this->refFld == 'id') {
                    $this->refEnt->eventUnsubscribe(Event\EntityEventTypeEnum::ID_CHANGED, $ownerEnt);
                } else {
                    $this->refEnt->eventUnsubscribe(Event\EntityEventTypeEnum::DATA_CHANGED, $ownerEnt);
                }
            }
        }

        // Set it (update it)
        $this->refEnt = $refEnt;

        // When the related item changes its referenced field, we wanna update our foreign key field
        if(isset($refEnt) && $refEnt !== $ownerEnt) {
            if($this->refFld == 'id') {
                $refEnt->eventSubscribe(Event\EntityEventTypeEnum::ID_CHANGED, $ownerEnt, '_relationOnEntityIdChanged', $this->fld);
            } else {
                $refEnt->eventSubscribe(Event\EntityEventTypeEnum::DATA_CHANGED, $ownerEnt, '_relationOnEntityFldChanged', [$this->fld, $this->refFld]);
            }
        }

        try {
            $ownerEnt->_set($this->fld, $refEnt?->_get($this->refFld));
        } catch(\LogicException $e) { // ref entity deleted
            $ownerEnt->_set($this->fld, null);
        }

        // at this point $this->ownerFieldWasSet() will be called from the \ORM\Entity::_set()
    }

    public function hasRefEntity(Entity $ownerEnt): bool
    {
        if(isset($this->refEnt)) return true;

        $value = $ownerEnt->_get( $this->fld );
        if(empty($value)) return false;

        // fetch the entity (to make sure it really does exist in the db)
        // and then btw, set it since we already fetched it
        $refEnt = $this->orm->entityManager($this->refMgrClass)->findByField($this->refFld, $value);
        $this->setRefEntity($ownerEnt, $refEnt);
        return isset($this->refEnt);
    }

    public function ownerFieldWasSet(Entity $ownerEnt, int|string|float|bool|null $value): void
    {
        if($this->refFld == 'id') {
            if (isset($this->refEnt) && $this->refEnt->getId() != $value) {
                $this->refEnt->eventUnsubscribe(Event\EntityEventTypeEnum::ID_CHANGED, $ownerEnt);
                $this->refEnt = null;
            }
        } else {
            if (isset($this->refEnt) && $this->refEnt->_get($this->refFld) != $value) {
                $this->refEnt->eventUnsubscribe(Event\EntityEventTypeEnum::DATA_CHANGED, $ownerEnt);
                $this->refEnt = null;
            }
        }
    }

}