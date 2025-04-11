<?php

namespace Milanmadar\CoolioORM;

abstract class Entity implements Event\AnnouncerInterface
{
    use Event\AnnouncerInterfaceTrait;

    protected ORM $orm; // So we can handle Related Entities

    /**
     * Set to true when the Entity is deleted ($this->_delete())
     * It will clead all its data and it will no longer be possible to set/get any data on it<br>
     * $entity->isDeleted() will return true after this
     * @var bool
     */
    private bool $_isDeleted;
    private bool $_isDeletedCommited;
    private ?int $_deletedId;

    /** @var array<string, mixed> Holds all the data */
    private array $_data;

    /** @var array<string, mixed> We can rollback to this and calculate changes */
    protected array $_dataOrigi;

    /** @var array<string, bool> To quickly know what datapoint has changed */
    private array $_changedDataKeys;

    /** @var bool Even if the 'id' field has value, the next $mgr->save() will run an INSERT query (useful when migrating) */
    private bool $_forceInsertOnNextSave;

    /** @var array<string, EntityRelation> array keys are db table column names */
    protected array $_relatedEntities;

    /**
     * Entity constructor. <b>ONLY CREATE ENTITIES VIA THEIR MANAGER</b>
     * @param ORM $orm So we can handle Related Entities
     * @param array<string, mixed> $data
     */
    public function __construct(ORM $orm, array $data = [])
    {
        $this->orm = $orm;
        $this->_data = $this->_dataOrigi = $data;
        $this->_changedDataKeys = [];
        $this->_isDeleted = false;
        $this->_deletedId = null;
        $this->_isDeletedCommited = false;
        $this->_forceInsertOnNextSave = false;

        // relations
        $this->_relatedEntities = [];
        $relations = $this->relations();
        if(isset($relations)) foreach($relations as $myFld=>$refMgrClass_Field) {
            $this->_relatedEntities[$myFld] = new EntityRelation($orm, $myFld, $refMgrClass_Field[1], $refMgrClass_Field[0]);
        }
    }

    /**
     * Maps the foreign key fields to the related entity manager class
     * @return array<string, array{0:class-string<mixed>,1:string}>|null key=foreign key fieldname , value=reference entity manager classname
     */
    protected function relations(): ?array
    {
        return null;
    }

    /**
     * @return array<string, EntityRelation> array keys are db table column names
     */
    public function _getRelatedEntities(): array
    {
        return $this->_relatedEntities;
    }

    /**
     * Sets a datapoint and tracks if it changed compared to the original data given in the constructor
     * @param string $fieldName
     * @param mixed $value
     * @throws \LogicException When the Entity was deleted. To get the 'id' it had, call _getDeletedId()
     * @Event Event\EntityEventTypeEnum::DATA_CHANGED , @EventArg [string:'field_name', mixed:'new id', mixed:'old id']
     * @Event Event\EntityEventTypeEnum::ID_CHANGED , @EventArg [int|null:'new id', int|null:'old id']
     */
    public function _set(string $fieldName, mixed $value): void
    {
        $this->_deletedAccessException();

        // relations

        // Is the new data different from the original (what's in the db)
        if(!array_key_exists($fieldName, $this->_dataOrigi)) { // this field was not in the original data
            if(isset($value)) {
                $this->_changedDataKeys[$fieldName] = true;
            } else {
                unset($this->_changedDataKeys[$fieldName]);
            }
        } else {
            if($this->_dataOrigi[$fieldName] === $value) {
                unset($this->_changedDataKeys[$fieldName]);
            } else {
                $this->_changedDataKeys[$fieldName] = true;
            }
        }

        // Change it
        $oldValue = $this->_data[$fieldName] ?? null;
        $this->_data[$fieldName] = $value;

        // Event dispatch
        if($oldValue !== $value) {
            $this->eventAnnounce(Event\EntityEventTypeEnum::DATA_CHANGED, $this, [$fieldName, $value, $oldValue]);
            if ($fieldName == 'id') {
                $this->eventAnnounce(Event\EntityEventTypeEnum::ID_CHANGED, $this, [$value, $oldValue]);
            }
        }

        if(isset($this->_relatedEntities[$fieldName])) {
            $this->_relatedEntities[$fieldName]->ownerFieldWasSet($this, $value);
        }
    }

    /**
     * Returns the datapoint
     * @param string $fieldName
     * @return mixed
     * //throws \LogicException When the Entity was deleted. To get the 'id' it had, call _getDeletedId()
     */
    public function _get(string $fieldName): mixed
    {
//        if($fieldName != 'id') {
//            $this->_deletedAccessException();
//        }

        return $this->_data[$fieldName] ?? null;
    }

    /**
     * Is this field set to anything (including null)?
     * @param string $fieldName
     * @return bool
     * //throws \LogicException When the Entity was deleted. To get the 'id' it had, call _getDeletedId()
     */
    public function _has(string $fieldName): bool
    {
        //$this->_deletedAccessException();

        return array_key_exists($fieldName, $this->_data);
    }

    /**
     * Set the primary id
     * @param int|null $id
     * @return $this
     * @throws \LogicException When the Entity was deleted. To get the 'id' it had, call _getDeletedId()
     * @Event Event\EntityEventTypeEnum::DATA_CHANGED , @EventArg [string:'id', int|null:'new id', int|null:'old id']
     * @Event Event\EntityEventTypeEnum::ID_CHANGED , @EventArg [int|null:'new id', int|null:'old id']
     */
    public function setId(?int $id): static
    {
        $id = ($id > 0) ? $id : null;
        $this->_set('id', $id);

        if(!isset($id)) {
            unset($this->_data['id']);
        }

        return $this;
    }

    /**
     * Primary id
     * @return int|null
     * //throws \LogicException When the Entity was deleted. To get the 'id' it had, call _getDeletedId()
     */
    public function getId(): ?int
    {
        return $this->_get('id');
    }

    /**
     * Does it have a primary id that is a positive integer
     * @return bool
     * //throws \LogicException When the Entity was deleted. To get the 'id' it had, call _getDeletedId()
     */
    public function hasId(): bool
    {
        //$this->_deletedAccessException();
        return !empty($this->_data['id']);
    }

    /**
     * Returns only those fields that changed. The values are the NEW values.
     * @return array<string, mixed>
     * @throws \LogicException When the Entity was deleted To get the 'id' it had, call _getDeletedId()
     */
    public function _getDataChanged(): array
    {
        $this->_deletedAccessException();

        $changedData = [];
        foreach($this->_changedDataKeys as $fieldName=>$_) {
            $changedData[$fieldName] = $this->_data[$fieldName];
        }
        return $changedData;
    }

    /**
     * Did the data change?
     * @param ?string $fieldName Optional
     * @return bool
     * @throws \LogicException When the Entity was deleted. To get the 'id' it had, call _getDeletedId()
     */
    public function _didDataChange(?string $fieldName = null): bool
    {
        $this->_deletedAccessException();

        if(isset($fieldName)) {
            return isset($this->_changedDataKeys[$fieldName]);
        }
        return !empty($this->_changedDataKeys);
    }

    /**
     * Sets the data back to what it got in the constructor
     * @throws \LogicException When the Entity was deleted+committed. To get the 'id' it had, call _getDeletedId()
     * @Event Event\EntityEventTypeEnum::ROLLBACK , @EventArg array<string, mixed> (the entire new data)
     */
    public function _rollback(): void
    {
        if($this->_isDeletedCommited) {
            $this->_deletedAccessException();
        }

        $fldsToDel = array_diff(array_keys($this->_data), array_keys($this->_dataOrigi));
        foreach($fldsToDel as $fld) {
            $this->_set($fld, null);
            unset($this->_data[$fld]);
        }

        // This should not be needed, coz we never unset($this->_data['...']), but lets keep it to be future proof
        $fldsToAdd = array_diff(array_keys($this->_dataOrigi), array_keys($this->_data));
        foreach($fldsToAdd as $fld) {
            $this->_set($fld, $this->_dataOrigi[$fld]);
        }

        $fldsToSet = array_diff_assoc($this->_dataOrigi, $this->_data);
        foreach($fldsToSet as $fld=>$val) {
            $this->_set($fld, $val);
        }

        $this->_isDeleted = false;

        $this->_changedDataKeys = [];

        if($this->eventHasSubscribers(Event\EntityEventTypeEnum::ROLLBACK)) {
            $this->eventAnnounce(Event\EntityEventTypeEnum::ROLLBACK, $this, $this->_data);
        }
    }

    /**
     * Rolls back only the given fields
     * @param array<string> $fieldNames
     */
    public function _rollbackSomeFields(array $fieldNames): void
    {
        foreach($fieldNames as $fld)
        {
            $currHas = array_key_exists($fld, $this->_data);
            $origHas = array_key_exists($fld, $this->_dataOrigi);

            if($currHas && !$origHas) {
                $this->_set($fld, null);
                unset($this->_data[$fld]);

            } elseif(!$currHas && !$origHas) {
                ;

            } else {
                $this->_set($fld, $this->_dataOrigi[$fld]);
            }
        }
    }

    /**
     * The current data will be set as Original, and changes will be tracked against this state
     * @Event Event\EntityEventTypeEnum::COMMITTED , @EventArg array<string, mixed> (the entire new data)
     */
    public function _commit(): void
    {
        if($this->_isDeleted) {
            $this->_isDeletedCommited = true;
        }

        $this->_dataOrigi = $this->_data;
        $this->_changedDataKeys = [];

        if($this->eventHasSubscribers(Event\EntityEventTypeEnum::COMMITTED)) {
            $this->eventAnnounce(Event\EntityEventTypeEnum::COMMITTED, $this, $this->_data);
        }
    }

    /**
     * Sets all the data
     * @param array<string, mixed> $data All the data from the Entity
     * @param bool $changeIdToo
     */
    public function _setData(array $data, bool $changeIdToo): void
    {
        $this->_deletedAccessException();

        $wasIdDefined = array_key_exists('id', $this->_data);
        $origiId = $this->_data['id'] ?? null;

        $this->_data = [];

        // keep the original id
        if(!$changeIdToo) {
            if($wasIdDefined) {
                $this->_data['id'] = $origiId;
            }
        }

        foreach($data as $k=>$v) {
            if($k == 'id') {
                if($changeIdToo) {
                    $this->setId($v);
                }
            } else {
                $this->_set($k, $v);
            }
        }
    }

    /**
     * @return array<string, mixed> All the data from the Entity
     * @throws \LogicException When the Entity was deleted. To get the 'id' it had, call _getDeletedId()
     */
    public function _getData(): array
    {
        $this->_deletedAccessException();

        return $this->_data;
    }

    /**
     * Returns the original data (commit() or $mgr->save() sets the current data to be the origi)
     * @return array<string, mixed>
     */
    public function _getDataOrigi(): array
    {
        return $this->_dataOrigi;
    }

    /**
     * Marks the Entity as deleted.
     * <br><b>EVENTS: EventDeleted IS SENT AND THE id CHANGE RELATED EVENTS IF APPLICABLE (EventDataChanged, EventIdChanged). THE OTHER data CHANGES ARE NOT SENDING THE EventDataChanged EVENTS.</b>
     * <br>It will clear all its data and it will no longer be possible to set/get any data on it
     * <br>$entity->isDeleted() will return true after this.
     * <br>$entity->_rollback() is possible after this, to set back its last commited state
     * <br>After $entity->delete() and $entity->_commit(), its not possibel to $entity->_rollback()
     * @Event Event\EntityEventTypeEnum::DELETED , @EventArg int|null (the old id)
     * @Event Event\EntityEventTypeEnum::ID_CHANGED , @EventArg [string:'id', null, int:'old id']
     * @Event Event\EntityEventTypeEnum::ID_CHANGED , @EventArg [null, int:'old id']
     */
    public function _delete(): void
    {
        if($this->_isDeleted) return; // Already deleted

        $this->_deletedId = $this->_get('id'); // The old id (for EventDeleted and getDeletedId())
        $this->setId(null); // Send the 'id' change related events
        $this->_isDeleted = true; // Mark as deleted
        $this->_data = []; // Remove all data
        $this->eventAnnounce(Event\EntityEventTypeEnum::DELETED, $this, $this->_deletedId); // Send EventDeleted event
    }

    /**
     * @return bool
     */
    public function _isDeleted(): bool
    {
        return $this->_isDeleted;
    }

    /**
     * Returns the 'id' it had before deleting. If the Entity is not _delete()-ed, it returns null,
     * @return int|null
     */
    public function _getDeletedId(): ?int
    {
        return ($this->_isDeleted ? $this->_deletedId : null);
    }

    /**
     * If its marked as deleted, it will throw the exception.
     * @throws \LogicException When the Entity was deleted. To get the 'id' it had, call _getDeletedId()
     */
    protected function _deletedAccessException(): void
    {
        if($this->_isDeleted) {
            if($this->_isDeletedCommited) {
                throw new \LogicException(get_class($this)." was deleted+commited, can't _rollback() anymore. To get the 'id' it had, call _getDeletedId()");
            } else {
                throw new \LogicException(get_class($this) . " was deleted (you can still _rollback()). To get the 'id' it had, call _getDeletedId()");
            }
        }
    }

    /**
     * Even if this Entity has an 'id' field, the next $mgr->save() will run an INSERT query.
     * (Useful when migrating)
     * @param bool $is
     * @return $this
     */
    public function _setForceInsertOnNextSave(bool $is): self
    {
        $this->_forceInsertOnNextSave = $is;
        return $this;
    }

    /**
     * Even if this Entity has an 'id' field, the next $mgr->save() will run an INSERT query.
     * (Useful when migrating)
     * @return bool
     */
    public function _getForceInsertOnNextSave(): bool
    {
        return $this->_forceInsertOnNextSave;
    }

    /**
     * EventHandler: called when the related Entity changes its id
     * @param Entity $relatedEntity
     * @param array<int|null,int|null> $newOldIds See const EventIdChanged
     * @param string $fieldName
     */
    public function _relationOnEntityIdChanged(Entity $relatedEntity, array $newOldIds, string $fieldName): void
    {
        if(!$this->_isDeleted()) {
            $this->_set($fieldName, $newOldIds[0]);
        }
    }

    /**
     * EventHandler: called when the related Entity changes its foreign_key field
     * @param Entity $relatedEntity
     * @param array<mixed> $fldNameNewOldValues See const EventDataChanged
     * @param array<string> $fieldNames [$fieldName, $refFieldName]
     */
    public function _relationOnEntityFldChanged(Entity $relatedEntity, array $fldNameNewOldValues, array $fieldNames): void
    {
        if(!$this->_isDeleted() && $fldNameNewOldValues[0] == $fieldNames[1]) {
            $this->_set($fieldNames[0], $fldNameNewOldValues[1]);
        }
    }

    /**
     * Sets the related entity and synchronizes the related id field too
     * @param string $fieldName
     * @param Entity|null $relatedEntity
     */
    protected function _relationSetEntity(string $fieldName, ?Entity $relatedEntity): void
    {
        $this->_deletedAccessException();
        $this->_relatedEntities[$fieldName]->setRefEntity($this, $relatedEntity);
    }

    /**
     * Returns the related entity. If it was now set yet, but the related id field was set, it will fetch the entity from the db and set it
     * @param string $fieldName
     * @return Entity|null
     */
    protected function _relationGetEntity(string $fieldName): ?Entity
    {
        $this->_deletedAccessException();
        return $this->_relatedEntities[$fieldName]->getRefEntity($this);
    }

    /**
     * Returns the related entity. If it was now set yet, but the related id field was set, it will fetch the entity from the db and set it
     * @param string $fieldName
     * @return bool
     */
    protected function _relationHasEntity(string $fieldName): bool
    {
        $this->_deletedAccessException();
        return $this->_relatedEntities[$fieldName]->hasRefEntity($this);
    }

    /**
     * For visual debug
     * @return string
     */
    public function debugHtml(): string
    {
        $data = $this->_data;
        ksort($data);

        $html = '<table style="width:100%; text-align:left; vertical-align: top;">';

        foreach($data as $fld=>$val)
        {
            if(!isset($val)) {
                $valHtml = '(null)';

            } elseif($val === false) {
                $valHtml = '(false)';

            } elseif($val === true) {
                $valHtml = '(true)';

            } elseif($val === '') {
                $valHtml = '(empty string)';

            } elseif(is_array($val) && empty($val)) {
                $valHtml = '(empty array)';

            } elseif(str_contains($fld, '_url')) {
                $valHtml = '<a href="'.htmlspecialchars($val).'" target="_blank">'.htmlspecialchars($val).'</a>';

            } elseif(is_array($val)) {
                $valHtml = '<pre>'.print_r($val, true).'</pre>';

            } elseif(is_int($val) && str_contains($fld, 'time')) {
                $valHtml = $val.' ('.date('d.M.Y H:i:s', $val).')';

            } else {
                if(is_int($val)) { $type = '(int) '; }
                elseif(is_float($val)) { $type = '(int) '; }
                elseif(is_string($val)) { $type = '(string) '; }
                else { $type = ' '; }
                $valHtml = $type . nl2br(htmlspecialchars( (string)$val ));
            }

            $html .=
                '<tr style="text-align:left; vertical-align: top; border-top: 1px solid #999; border-bottom:1px solid #999; border-left: none; border-right: none">'
                    .'<td style="width:150px; padding:3px;">'
                    .   '<b>'.$fld.':</b>'
                    .'</td>'
                    .'<td style="padding: 3px;">'
                        .$valHtml
                    .'</td>'
                .'</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * For visual debug
     * @return string
     */
    public function debugCli(): string
    {
        $data = $this->_data;
        ksort($data);

        $output = "\n";

        foreach($data as $fld=>$val)
        {
            if(!isset($val)) {
                $valHtml = '(null)';

            } elseif($val === false) {
                $valHtml = '(false)';

            } elseif($val === true) {
                $valHtml = '(true)';

            } elseif($val === '') {
                $valHtml = '(empty string)';

            } elseif(is_array($val) && empty($val)) {
                $valHtml = '(empty array)';

            } elseif(is_array($val)) {
                $valHtml = print_r($val, true);

            } elseif(is_int($val) && (str_contains($fld, 'time') || $fld == 'created_at' || $fld == 'updated_at' || $fld == 'deleted_at' || $fld == 'published_at' || $fld == 'publish_at')) {
                $valHtml = $val.' ('.date('d.M.Y H:i:s', $val).')';

            } else {
                if(is_int($val)) { $type = '(int) '; }
                elseif(is_float($val)) { $type = '(int) '; }
                elseif(is_string($val)) { $type = '(string) '; }
                else { $type = ' '; }
                $valHtml = $type . ((string)$val);
            }

            $output .= "\n".$fld.': '.$valHtml;
        }
        $output .= "\n";
        return $output;
    }

    public function __destruct()
    {
        $this->eventAnnounce(Event\EntityEventTypeEnum::DESTRUCT, $this);
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            '_data' => $this->_data,
            '_dataOrigi' => $this->_dataOrigi,
            '_isDeleted' => $this->_isDeleted,
            '_isDeletedCommited' => $this->_isDeletedCommited,
            '_deletedId' => $this->_deletedId,
            '_changedDataKeys' => $this->_changedDataKeys,
            '_forceInsertOnNextSave' => $this->_forceInsertOnNextSave,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->_data = $data['_data'];
        $this->_dataOrigi = $data['_dataOrigi'];
        $this->_isDeleted = $data['_isDeleted'];
        $this->_isDeletedCommited = $data['_isDeletedCommited'];
        $this->_deletedId = $data['_deletedId'];
        $this->_changedDataKeys = $data['_changedDataKeys'];
        $this->_forceInsertOnNextSave = $data['_forceInsertOnNextSave'];

        $this->orm = ORM::instance();

        // relations
        $this->_relatedEntities = [];
        $relations = $this->relations();
        if(isset($relations)) foreach($relations as $myFld=>$refMgrClass_Field) {
            $this->_relatedEntities[$myFld] = new EntityRelation($this->orm, $myFld, $refMgrClass_Field[1], $refMgrClass_Field[0]);
        }
    }

}