<?php

namespace Pgsql;

use Milanmadar\CoolioORM\Geo\Shape\CircularString;
use Milanmadar\CoolioORM\Geo\Shape\CompoundCurve;
use Milanmadar\CoolioORM\Geo\Shape\CurvePolygon;
use Milanmadar\CoolioORM\Geo\Shape\GeometryCollection;
use Milanmadar\CoolioORM\Geo\Shape\LineString;
use Milanmadar\CoolioORM\Geo\Shape\MultiCurve;
use Milanmadar\CoolioORM\Geo\Shape\MultiLineString;
use Milanmadar\CoolioORM\Geo\Shape\MultiPoint;
use Milanmadar\CoolioORM\Geo\Shape\MultiPolygon;
use Milanmadar\CoolioORM\Geo\Shape\Point;
use Milanmadar\CoolioORM\Geo\Shape\Polygon;
use Milanmadar\CoolioORM\ORM;
use Milanmadar\CoolioORM\Geo\Shape;
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

    public function testInsert_asText()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $oCnt = self::$dbHelper->countRows('geometry_test');

        $mgr->createQueryBuilder()
            ->insert()
            ->setValue('point_geom', 'ST_GeomFromText(\'POINT(1 2)\', 4326)')
            ->executeStatement()
        ;
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geometry_test'));
    }

    public function testInsert_asEWKT()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $oCnt = self::$dbHelper->countRows('geometry_test');

        $mgr->createQueryBuilder()
            ->insert()
            ->setValue('point_geom', 'ST_GeomFromEWKT(\'SRID=4326;POINT(1 2)\')')
            ->executeStatement()
        ;
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geometry_test'));
    }

    public function testInsert_asObject()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $oCnt = self::$dbHelper->countRows('geometry_test');

        //
        // Create All Shapes
        //
        $point = new Shape\Point(6, 7, 4326);
        $lineString = new LineString([
            new Point(1, 1), new Point(2, 2), new Point(3, 3), new Point(4, 4)
        ], 4326);
        $multiPoint = new MultiPoint([
            new Point(1, 1), new Point(2, 2), new Point(3, 3), new Point(4, 4)
        ], 4326);
        $polygon = new Polygon([
            new LineString([new Point(0, 0), new Point(0, 5), new Point(5, 5), new Point(5, 0), new Point(0, 0)]),
            new LineString([new Point(1, 1), new Point(1, 2), new Point(2, 2), new Point(2, 1), new Point(1, 1)])
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
        $compoundCurve = new CompoundCurve([
            new LineString([new Point(2, 0), new Point(3, 1)]),
            new CircularString([new Point(3, 1), new Point(4, 2), new Point(5, 1)]),
            new LineString([new Point(5, 1), new Point(6, 0)]),
        ], 4326);
        $circularString = new CircularString([
            new Point(0, 0), new Point(4, 0), new Point(4, 4), new Point(0, 4), new Point(0, 0)
        ], 4326);
        $curvePolygon = new CurvePolygon([
            new CircularString([new Point(0, 0, 4326), new Point(6, 0, 4326), new Point(6, 6, 4326), new Point(0, 6, 4326), new Point(0, 0, 4326)], 4326),
            new LineString([new Point(2, 2, 4326), new Point(3, 2, 4326), new Point(3, 3, 4326), new Point(2, 3, 4326), new Point(2, 2, 4326)], 4326),
            new CircularString([new Point(1, 1, 4326), new Point(2, 1, 4326), new Point(2, 2, 4326), new Point(1, 2, 4326), new Point(1, 1, 4326)], 4326)
        ], 4326);
        $multiCurve = new MultiCurve([
            new CircularString([new Point(0, 0, 4326), new Point(1, 2, 4326), new Point(2, 0, 4326)], 4326),
            new LineString([new Point(3, 3, 4326), new Point(4, 4, 4326), new Point(5, 5, 4326)], 4326)
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
            ->setValue('point_geom', $point)
            ->setValue('linestring_geom', $lineString)
            ->setValue('polygon_geom', $polygon)
            ->setValue('multipoint_geom', $multiPoint)
            ->setValue('multilinestring_geom', $multiLineString)
            ->setValue('multipolygon_geom', $multiPolygon)
            ->setValue('geomcollection_geom', $geometryCollection)
            ->setValue('circularstring_geom', $circularString)
            ->setValue('compoundcurve_geom', $compoundCurve)
            ->setValue('curvedpolygon_geom', $curvePolygon)
            ->setValue('multicurve_geom', $multiCurve)
            ->executeStatement()
        ;
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geometry_test'));

        //
        // Select All Shapes
        //
        $ent = $mgr->findById(2);

        $this->assertTrue($point == $ent->getPointGeom());
        $this->assertTrue($lineString == $ent->getLinestringGeom());
        $this->assertTrue($polygon == $ent->getPolygonGeom());
        $this->assertTrue($multiPoint == $ent->getMultipointGeom());
        $this->assertTrue($multiLineString == $ent->getMultilinestringGeom());
        $this->assertTrue($multiPolygon == $ent->getMultipolygonGeom());
        $this->assertTrue($geometryCollection == $ent->getGeomcollectionGeom());
        $this->assertTrue($circularString == $ent->getCircularStringGeom());
        $this->assertTrue($compoundCurve == $ent->getCompoundcurveGeom());
        $this->assertTrue($curvePolygon == $ent->getCurvepolygonGeom());
        $this->assertTrue($multiCurve == $ent->getMulticurveGeom());
    }
}