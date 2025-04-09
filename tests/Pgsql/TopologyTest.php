<?php

namespace Pgsql;

use Milanmadar\CoolioORM\Geo\Shape2D\GeometryCollection;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiLineString;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiPoint;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiPolygon;
use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\Polygon;
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

    public function testInsert_asObjects()
    {
        $mgr = self::$dbHelper->getManager(TopologyTestEnt\Manager::class);
        $mgr->clearRepository(false);

        $oCnt = self::$dbHelper->countRows('geometry_test');

        //
        // Create All Shapes
        //
        $multiPoint = new MultiPoint([
            new Point(1, 1), new Point(2, 2), new Point(3, 3), new Point(4, 4)
        ], 4326);
        $multiLineString = new MultiLineString([
            new LineString([new Point(1, 1), new Point(2, 2), new Point(3, 3)], 4326),
            new LineString([new Point(4, 4), new Point(5, 5)], 4326),
            new LineString([new Point(6, 6), new Point(7, 7), new Point(8, 8)], 4326)
        ], 4326);
        $multiPolygon = new MultiPolygon([
            new Polygon([
                new LineString([new Point(0, 0), new Point(0, 5), new Point(5, 5), new Point(5, 0), new Point(0, 0),]),
                new LineString([new Point(1, 1), new Point(1, 2), new Point(2, 2), new Point(2, 1), new Point(1, 1),])
            ], 4326),
            new Polygon([
                new LineString([new Point(8, 8), new Point(0, 5), new Point(5, 5), new Point(5, 0), new Point(8, 8),]),
                new LineString([new Point(9, 9), new Point(1, 2), new Point(2, 2), new Point(2, 1), new Point(9, 9),])
            ], 4326)
        ], 4326);
        $geometryCollection = new GeometryCollection([
            new Point(1, 1, 4326),
            new LineString([new Point(2, 2, 4326), new Point(3, 3, 4326), new Point(4, 4, 4326)], 4326),
            new Polygon([
                new LineString([new Point(0, 0, 4326), new Point(0, 5, 4326), new Point(5, 5, 4326), new Point(5, 0, 4326), new Point(0, 0, 4326),], 4326),
                new LineString([new Point(1, 1, 4326), new Point(1, 2, 4326), new Point(2, 2, 4326), new Point(2, 1, 4326), new Point(1, 1, 4326),], 4326)
            ], 4326)
        ], 4326);

        //
        // Insert All Shapes
        //
        $mgr->createQueryBuilder()
            ->insert()
            ->setValue('topo_geom_point', $multiPoint)
            ->setValue('topo_geom_linestring', $multiLineString)
            ->setValue('topo_geom_polygon', $multiPolygon)
            ->setValue('topo_geom_collection', $geometryCollection)
            ->executeStatement()
        ;
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geometry_test'));

        //
        // Select All Shapes
        //
        $mgr->_getEntityRepository()->clear();
        $ent = $mgr->findById(2);

        $this->assertEquals($multiPolygon->toEWKT(), $ent->getTopoGeomPolygon()->toEWKT());

        $this->assertTrue($multiPoint == $ent->getTopoGeomPoint());
        $this->assertTrue($multiLineString == $ent->getTopoGeomLinestring());
        $this->assertTrue($multiPolygon == $ent->getTopoGeomPolygon());
        $this->assertTrue($geometryCollection == $ent->getTopoGeomGeometrycollection());
    }
}