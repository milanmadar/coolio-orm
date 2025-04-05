<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\GeoShapeAll;

class GeoTest extends TestCase
{
    private static DbHelper $dbHelper;

    // This method runs once when the test class is loaded
    public static function setUpBeforeClass(): void
    {
        // Load the database helper
        $conn = ORM::instance()->getDoctrineConnectionByUrl($_ENV['DB_POSTGRES_DB1']);
        self::$dbHelper = new DbHelper( $conn );
    }

    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
    }

    public function testSelectAllShapes_FindOne_QueryBuilder_ExplicitSELECTlist()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        /** @var GeoShapeAll\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->select('polygon_geom','circularstring_geom')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\Polygon', $ent->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\CircularString', $ent->getCircularStringGeom());
    }

    public function testSelectAllShapes_FindOne_QueryBuilder_Star()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        /** @var GeoShapeAll\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->select('*')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\Point', $ent->getPointGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\LineString', $ent->getLinestringGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\Polygon', $ent->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\MultiPoint', $ent->getMultipointGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\MultiLineString', $ent->getMultilinestringGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\MultiPolygon', $ent->getMultipolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\GeometryCollection', $ent->getGeomcollectionGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\CircularString', $ent->getCircularStringGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\CompoundCurve', $ent->getCompoundcurveGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\CurvePolygon', $ent->getCurvepolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\MultiCurve', $ent->getMulticurveGeom());
    }

    public function testSelectAllShapes_FindOne_QueryBuilder_NoSELECT()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        /** @var GeoShapeAll\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\Polygon', $ent->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\CircularString', $ent->getCircularStringGeom());
    }

    public function testSelectAllShapes_FindOne_noQueryBuilder()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        /** @var GeoShapeAll\Entity $ent */
        $ent = $mgr->findById(1);

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\Polygon', $ent->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\CircularString', $ent->getCircularStringGeom());
    }
    public function testSelectAllShapes_FindMany_QueryBuilder_ExplicitSELECTlist()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        /** @var GeoShapeAll\Entity[] $ents */
        $ents = $mgr->createQueryBuilder()
//            ->select(
//                'ST_AsGeoJSON(polygon_geom) AS polygon_geom',
//                'ST_SRID(polygon_geom) AS polygon_geom_srid',
//                'ST_AsEWKT(circularstring_geom) AS circularstring_geom'
//            )
            ->select('polygon_geom','circularstring_geom')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchManyEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\Polygon', $ents[0]->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\CircularString', $ents[0]->getCircularStringGeom());
    }

    public function testSelectAllShapes_FindMany_QueryBuilder_Star()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        /** @var GeoShapeAll\Entity[] $ents */
        $ents = $mgr->createQueryBuilder()
            ->select('*')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchManyEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\Polygon', $ents[0]->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\CircularString', $ents[0]->getCircularStringGeom());
    }
    public function testSelectAllShapes_FindMany_QueryBuilder_NoSELECT()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        /** @var GeoShapeAll\Entity[] $ents */
        $ents = $mgr->createQueryBuilder()
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchManyEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\Polygon', $ents[0]->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\CircularString', $ents[0]->getCircularStringGeom());
    }

    public function testSelectAllShapes_FindMany_noQueryBuilder()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        /** @var GeoShapeAll\Entity[] $ents */
        $ents = $mgr->findManyWhere("1=1");

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\Polygon', $ents[0]->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape\CircularString', $ents[0]->getCircularStringGeom());
    }
}