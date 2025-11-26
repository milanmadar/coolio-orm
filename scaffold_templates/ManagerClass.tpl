<?php

namespace _NAMESPACE_;

use Milanmadar\CoolioORM;

/**
 * @method Entity createEntity(mixed[] $php_data = [], bool $skipEntityRepo = false)
 * @method Entity createEntityFromDbData(mixed[] $db_data = [], bool $skipEntityRepo = false, bool $checkIfTheColumnsBelongToThisManager = false)
 * @method Entity|null findByField(string $field, mixed $value, bool $forceToGetFromDb = false)
 * @method Entity|null findById(?int $id, bool $forceToGetFromDb = false)
 * @method Entity|null findOneWhere(string $sqlAfterWHERE, mixed[] $binds = [], bool $forceToGetFromDb = false)
 * @method Entity|null findOne(string $sql, mixed[] $binds = [], bool $forceToGetFromDb = false)
 * @method Entity[] findManyWhere(string $sqlAfterWHERE, mixed[] $binds = [], bool $forceToGetFromDb = false)
 * @method Entity[] findMany(string $sql, mixed[] $binds = [], bool $forceToGetFromDb = false)
 */
class Manager extends CoolioORM\Manager
{
    /**
     * @inheritDoc
     * @param Entity $ent
     */
    public function save(CoolioORM\Entity $ent): void
    {
        if( ! $ent instanceof Entity) { /* @phpstan-ignore-line */
            throw new \InvalidArgumentException(get_class($this)."::save() can't save ".get_class($ent));
        }
        parent::save($ent);
    }

    /**
     * @inheritDoc
     * @param Entity $ent
     */
    public function delete(CoolioORM\Entity $ent): void
    {
        if( ! $ent instanceof Entity) { /* @phpstan-ignore-line */
            throw new \InvalidArgumentException(get_class($this)."::delete() can't delete ".get_class($ent));
        }
        parent::delete($ent);
    }

    ///
    /// BELOW ARE INHERITED METHODS FROM THE CoolioORM\Manager CLASS
    ///

    /**
     * @inheritDoc
     */
    public function getFieldTypes(): array { return [_FIELD_TYPES_
    ]; }

    /**
     * @inheritDoc
     */
    public function getTopoGeometryFieldInfo(): array { return [_FIELD_TOPOGEO_TYPES_
    ]; }

    /**
    * @inheritDoc
    */
    protected function getDefaultValues(): array { return [_DEFAULT_VALUES_
    ]; }

    /**
    * @inheritDoc
    */
    protected function afterConvertFromDb(array &$php_data): void {
    }

    /**
    * @inheritDoc
    */
    protected function beforeToDb(array &$data): void {
    }

    /**
     * @inheritDoc
     */
    public function getDefaultDbTable(): string {
        return '_DB_TBL_NAME_';
    }

    /**
     * @inheritDoc
     */
    public static function getDbDefaultConnectionUrl(): string {
        return $_ENV['_DB_ENV_NAME_'];
    }

    /**
     * @inheritDoc
     * @return Entity
     */
    protected function createEntityDo(CoolioORM\ORM $orm, array $php_data = []): Entity {
        return new Entity($orm, $php_data);
    }

}