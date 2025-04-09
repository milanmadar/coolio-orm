<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\TopologyTest as TopologyTestEnt;

class TopologyTest extends TestCase
{
    private static DbHelper $dbHelper;

    // This method runs once when the test class is loaded
    public static function setUpBeforeClass(): void
    {
        // Load the database helper
        $conn = ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB1']);
        self::$dbHelper = new DbHelper( $conn );
    }

    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
        self::$dbHelper->resetTo('Pgsql/fixtures/topology.sql');
    }

    public function testSelectAllShapes_FindOne_QueryBuilder_ExplicitSELECTlist()
    {
        $mgr = self::$dbHelper->getManager(TopologyTestEnt\Manager::class);
        $mgr->clearRepository(false);

        /** @var TopologyTestEnt\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->select('topo_geom_point','topo_geom_collection')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiPoint', $ent->getTopoGeomPoint());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\GeometryCollection', $ent->getTopoGeomGeometrycollection());
    }

    public function testSelectAllShapes_FindOne_QueryBuilder_Star()
    {
        $mgr = self::$dbHelper->getManager(TopologyTestEnt\Manager::class);
        $mgr->clearRepository(false);

        /** @var TopologyTestEnt\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->select('*')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiPoint', $ent->getTopoGeomPoint());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiLineString', $ent->getTopoGeomLinestring());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiPolygon', $ent->getTopoGeomPolygon());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\GeometryCollection', $ent->getTopoGeomGeometrycollection());
    }

    public function testSelectAllShapes_FindOne_QueryBuilder_NoSELECT()
    {
        $mgr = self::$dbHelper->getManager(TopologyTestEnt\Manager::class);
        $mgr->clearRepository(false);

        /** @var TopologyTestEnt\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiPoint', $ent->getTopoGeomPoint());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiLineString', $ent->getTopoGeomLinestring());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiPolygon', $ent->getTopoGeomPolygon());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\GeometryCollection', $ent->getTopoGeomGeometrycollection());
    }

    public function testSelectAllShapes_FindOne_noQueryBuilder()
    {
        $mgr = self::$dbHelper->getManager(TopologyTestEnt\Manager::class);
        $mgr->clearRepository(false);

        /** @var TopologyTestEnt\Entity $ent */
        $ent = $mgr->findById(1);

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiPoint', $ent->getTopoGeomPoint());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiLineString', $ent->getTopoGeomLinestring());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiPolygon', $ent->getTopoGeomPolygon());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\GeometryCollection', $ent->getTopoGeomGeometrycollection());
    }

    public function testSelectAllShapes_FindMany_QueryBuilder_ExplicitSELECTlist()
    {
        $mgr = self::$dbHelper->getManager(TopologyTestEnt\Manager::class);
        $mgr->clearRepository(false);

        /** @var TopologyTestEnt\Entity[] $ents */
        $ents = $mgr->createQueryBuilder()
            ->select('topo_geom_point','topo_geom_collection')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchManyEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiPoint', $ents[0]->getTopoGeomPoint());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\GeometryCollection', $ents[0]->getTopoGeomGeometrycollection());
    }
}