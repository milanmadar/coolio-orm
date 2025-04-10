<?php

namespace tests\Model\GeoShapeAll;

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
        'point_geom' => 'geometry',
        'linestring_geom' => 'geometry',
        'polygon_geom' => 'geometry',
        'multipoint_geom' => 'geometry',
        'multilinestring_geom' => 'geometry',
        'multipolygon_geom' => 'geometry',
        'geomcollection_geom' => 'geometry',
        'circularstring_geom' => 'geometry_curved',
        'compoundcurve_geom' => 'geometry_curved',
        'curvedpolygon_geom' => 'geometry_curved',
        'multicurve_geom' => 'geometry_curved',
    ]; }

    /**
    * @inheritDoc
    */
    protected function getDefaultValues(): array { return [
    ]; }

    /**
     * @inheritDoc
     */
    public function getTopoGeometryFieldInfo(): array { return [
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
        return 'public.geometry_test';
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