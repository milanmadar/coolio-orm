<?php

namespace tests\Model\GeoShapeZAll;

use Milanmadar\CoolioORM\ORM;

/**
 * @method Entity createEntity(array $php_data = [], bool $skipEntityRepo = false)
 * @method Entity createEntityFromDbData(array $db_data = [], bool $skipEntityRepo = false)
 * @method Entity|null findById(?int $id, bool $forceToGetFromDb = false)
 * @method Entity|null findOneWhere(string $sqlAfterWHERE, array $binds = [], bool $forceToGetFromDb = false)
 * @method Entity|null findOne(string $sql, array $binds = [], bool $forceToGetFromDb = false)
 * @method Entity[] findManyWhere(string $sqlAfterWHERE, array $binds = [], bool $forceToGetFromDb = false)
 * @method Entity[] findMany(string $sql, array $binds = [], bool $forceToGetFromDb = false)
 */
class Manager extends \Milanmadar\CoolioORM\Manager
{
    /**
     * @inheritDoc
     * @param Entity $ent
     */
    public function save(\Milanmadar\CoolioORM\Entity $ent): void
    {
        if( ! $ent instanceof Entity) {
            throw new \InvalidArgumentException(get_class($this)."::save() can't save ".get_class($ent));
        }
        parent::save($ent);
    }

    /**
     * @inheritDoc
     * @param Entity $ent
     */
    public function delete(\Milanmadar\CoolioORM\Entity $ent): void
    {
        if( ! $ent instanceof Entity) {
            throw new \InvalidArgumentException(get_class($this)."::delete() can't delete ".get_class($ent));
        }
        parent::delete($ent);
    }

    ///
    /// BELOW ARE INHERITED METHODS FROM THE \ORM\Manager CLASS
    ///

    /**
     * @inheritDoc
     */
    public function getFieldTypes(): array { return [
        'id' => 'integer',
        'pointz_geom' => 'geometry',
        'linestringz_geom' => 'geometry',
        'polygonz_geom' => 'geometry',
        'multipointz_geom' => 'geometry',
        'multilinestringz_geom' => 'geometry',
        'multipolygonz_geom' => 'geometry',
        'geomcollectionz_geom' => 'geometry',
        'circularstringz_geom' => 'geometry_curved',
//        'compoundcurvez_geom' => 'geometry_curved',
//        'curvedpolygonz_geom' => 'geometry_curved',
//        'multicurvez_geom' => 'geometry_curved',
    ]; }

    /**
    * @inheritDoc
    */
    protected function getDefaultValues(): array { return [
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
    public static function getDbDefaultConnectionUrl(): string
    {
        return $_ENV['DB_POSTGRES_DB1'];
    }

    /**
     * @inheritDoc
     */
    public function getDefaultDbTable(): string {
        return 'geometryz_test';
    }

    /**
     * @inheritDoc
     * @return Entity
     */
    protected function createEntityDo(ORM $orm, array $php_data = []): Entity
    {
        return new Entity($orm, $php_data);
    }

}