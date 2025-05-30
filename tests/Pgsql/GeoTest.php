<?php

namespace Pgsql;

use Milanmadar\CoolioORM\Geo\Shape2D\CircularString;
use Milanmadar\CoolioORM\Geo\Shape2D\CompoundCurve;
use Milanmadar\CoolioORM\Geo\Shape2D\CurvePolygon;
use Milanmadar\CoolioORM\Geo\Shape2D\GeometryCollection;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiCurve;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiLineString;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiPoint;
use Milanmadar\CoolioORM\Geo\Shape2D\MultiPolygon;
use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\Polygon;
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
        $conn = ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB1']);
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
        $mgr->clearRepository(false);

        /** @var GeoShapeAll\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->select('polygon_geom','circularstring_geom')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Polygon', $ent->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\CircularString', $ent->getCircularStringGeom());
    }

    public function testSelectExcept_AllShapes_FindOne_QueryBuilder_Star()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        /** @var GeoShapeAll\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->selectExcept(['geomcollection_geom','polygon_geom'])
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Point', $ent->getPointGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\LineString', $ent->getLinestringGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiPoint', $ent->getMultipointGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiLineString', $ent->getMultilinestringGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiPolygon', $ent->getMultipolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\CircularString', $ent->getCircularStringGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\CompoundCurve', $ent->getCompoundcurveGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\CurvePolygon', $ent->getCurvepolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\MultiCurve', $ent->getMulticurveGeom());
    }

    public function testSelectAllShapes_FindOne_QueryBuilder_NoSELECT()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        /** @var GeoShapeAll\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Polygon', $ent->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\CircularString', $ent->getCircularStringGeom());
    }

    public function testSelectAllShapes_FindOne_noQueryBuilder()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        /** @var GeoShapeAll\Entity $ent */
        $ent = $mgr->findById(1);

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Polygon', $ent->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\CircularString', $ent->getCircularStringGeom());
    }

    public function testSelectAllShapes_FindMany_QueryBuilder_ExplicitSELECTlist()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        /** @var GeoShapeAll\Entity[] $ents */
        $ents = $mgr->createQueryBuilder()
            ->select('polygon_geom','circularstring_geom')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchManyEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Polygon', $ents[0]->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\CircularString', $ents[0]->getCircularStringGeom());
    }

    public function testSelectAllShapes_FindMany_QueryBuilder_Star()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        /** @var GeoShapeAll\Entity[] $ents */
        $ents = $mgr->createQueryBuilder()
            ->select('*')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchManyEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Polygon', $ents[0]->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\CircularString', $ents[0]->getCircularStringGeom());
    }

    public function testSelectAllShapes_FindMany_QueryBuilder_NoSELECT()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        /** @var GeoShapeAll\Entity[] $ents */
        $ents = $mgr->createQueryBuilder()
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchManyEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Polygon', $ents[0]->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\CircularString', $ents[0]->getCircularStringGeom());
    }

    public function testgetSQLNamedParameters()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        //$expect = "SELECT id, ST_AsGeoJSON(point_geom) AS point_geom, ST_SRID(point_geom) AS point_geom_srid, ST_AsGeoJSON(linestring_geom) AS linestring_geom, ST_SRID(linestring_geom) AS linestring_geom_srid, ST_AsGeoJSON(polygon_geom) AS polygon_geom, ST_SRID(polygon_geom) AS polygon_geom_srid, ST_AsGeoJSON(multipoint_geom) AS multipoint_geom, ST_SRID(multipoint_geom) AS multipoint_geom_srid, ST_AsGeoJSON(multilinestring_geom) AS multilinestring_geom, ST_SRID(multilinestring_geom) AS multilinestring_geom_srid, ST_AsGeoJSON(multipolygon_geom) AS multipolygon_geom, ST_SRID(multipolygon_geom) AS multipolygon_geom_srid, ST_AsGeoJSON(geomcollection_geom) AS geomcollection_geom, ST_SRID(geomcollection_geom) AS geomcollection_geom_srid, ST_AsEWKT(circularstring_geom) AS circularstring_geom, ST_AsEWKT(compoundcurve_geom) AS compoundcurve_geom, ST_AsEWKT(curvedpolygon_geom) AS curvedpolygon_geom, ST_AsEWKT(multicurve_geom) AS multicurve_geom FROM public.geometry_test WHERE 1=1 LIMIT 1";
        $expect = "SELECT id, ST_AsEWKT(point_geom) AS point_geom, ST_AsEWKT(linestring_geom) AS linestring_geom, ST_AsEWKT(polygon_geom) AS polygon_geom, ST_AsEWKT(multipoint_geom) AS multipoint_geom, ST_AsEWKT(multilinestring_geom) AS multilinestring_geom, ST_AsEWKT(multipolygon_geom) AS multipolygon_geom, ST_AsEWKT(geomcollection_geom) AS geomcollection_geom, ST_AsEWKT(circularstring_geom) AS circularstring_geom, ST_AsEWKT(compoundcurve_geom) AS compoundcurve_geom, ST_AsEWKT(curvedpolygon_geom) AS curvedpolygon_geom, ST_AsEWKT(multicurve_geom) AS multicurve_geom FROM public.geometry_test WHERE 1=1 LIMIT 1";
        $sql = $mgr->createQueryBuilder()
            ->andWhere('1=1')
            ->limit(0, 1)
            ->getSQLNamedParameters()
        ;

        $this->assertEquals($expect, $sql);
    }

    public function testSelectAllShapes_FindMany_noQueryBuilder()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        /** @var GeoShapeAll\Entity[] $ents */
        $ents = $mgr->findManyWhere("1=1");

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Polygon', $ents[0]->getPolygonGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\CircularString', $ents[0]->getCircularStringGeom());
    }

    public function testInsert_asObjects()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        $oCnt = self::$dbHelper->countRows('geometry_test');

        //
        // Create All Shapes
        //
        $point = new Point(6, 7, 4326);
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
            ->setGeom('point_geom', $point)
            ->setGeom('linestring_geom', $lineString)
            ->setGeom('polygon_geom', $polygon)
            ->setGeom('multipoint_geom', $multiPoint)
            ->setGeom('multilinestring_geom', $multiLineString)
            ->setGeom('multipolygon_geom', $multiPolygon)
            ->setGeom('geomcollection_geom', $geometryCollection)
            ->setGeom('circularstring_geom', $circularString)
            ->setGeom('compoundcurve_geom', $compoundCurve)
            ->setGeom('curvedpolygon_geom', $curvePolygon)
            ->setGeom('multicurve_geom', $multiCurve)
            ->executeStatement()
        ;
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geometry_test'));

        //
        // Select All Shapes
        //
        $mgr->_getEntityRepository()->clear();
        $ent = $mgr->findById(2);

        $this->assertEquals($multiPolygon->toEWKT(), $ent->getMultipolygonGeom()->toEWKT());

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

    public function testInsert_asEntity()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        $oCnt = self::$dbHelper->countRows('geometry_test');

        //
        // Create All Shapes
        //
        $point = new Point(6, 7, 4326);
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

        $newEnt = $mgr->createEntity()
            ->setPointGeom($point)
            ->setLinestringGeom($lineString)
            ->setPolygonGeom($polygon)
            ->setMultipointGeom($multiPoint)
            ->setMultilinestringGeom($multiLineString)
            ->setMultipolygonGeom($multiPolygon)
            ->setGeomcollectionGeom($geometryCollection)
            ->setCircularstringGeom($circularString)
            ->setCompoundcurveGeom($compoundCurve)
            ->setCurvepolygonGeom($curvePolygon)
            ->setMulticurveGeom($multiCurve)
        ;

        //
        // Insert All Shapes
        //
        $mgr->save($newEnt);
        $mgr->_getEntityRepository()->clear();
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

    public function testUpdate_asObjects()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        $oCnt = self::$dbHelper->countRows('geometry_test');

        //
        // Create All Shapes
        //
        $point = new Point(6, 7, 4326);
        $lineString = new LineString([
            new Point(11, 1), new Point(2, 2), new Point(3, 3), new Point(44, 44)
        ], 4326);
        $multiPoint = new MultiPoint([
            new Point(11, 1), new Point(2, 2), new Point(3, 3), new Point(4, 4)
        ], 4326);
        $polygon = new Polygon([
            new LineString([new Point(22, 0), new Point(0, 5), new Point(5, 5), new Point(5, 0), new Point(22, 0)]),
            new LineString([new Point(22, 1), new Point(1, 2), new Point(2, 2), new Point(2, 1), new Point(22, 1)])
        ], 4326);
        $multiLineString = new MultiLineString([
            new LineString([new Point(33, 1), new Point(2, 2), new Point(3, 3)], 4326),
            new LineString([new Point(33, 4), new Point(5, 5)], 4326),
            new LineString([new Point(33, 6), new Point(7, 7), new Point(8, 8)], 4326)
        ], 4326);
        $multiPolygon = new MultiPolygon([
            new Polygon([
                new LineString([new Point(44, 0), new Point(0, 5), new Point(5, 5), new Point(5, 0), new Point(44, 0),]),
                new LineString([new Point(44, 1), new Point(1, 2), new Point(2, 2), new Point(2, 1), new Point(44, 1),])
            ], 4326),
            new Polygon([
                new LineString([new Point(55, 8), new Point(0, 5), new Point(5, 5), new Point(5, 0), new Point(55, 8),]),
                new LineString([new Point(55, 9), new Point(1, 2), new Point(2, 2), new Point(2, 1), new Point(55, 9),])
            ], 4326)
        ], 4326);
        $compoundCurve = new CompoundCurve([
            new LineString([new Point(66, 0), new Point(66, 1)]),
            new CircularString([new Point(66, 1), new Point(4, 2), new Point(66, 65)]),
            new LineString([new Point(66, 65), new Point(6, 0)]),
        ], 4326);
        $circularString = new CircularString([
            new Point(77, 0), new Point(4, 0), new Point(4, 4), new Point(0, 4), new Point(0, 0)
        ], 4326);
        $curvePolygon = new CurvePolygon([
            new CircularString([new Point(88, 0, 4326), new Point(6, 0, 4326), new Point(6, 6, 4326), new Point(0, 6, 4326), new Point(88, 0, 4326)], 4326),
            new LineString([new Point(88, 2, 4326), new Point(3, 2, 4326), new Point(3, 3, 4326), new Point(2, 3, 4326), new Point(88, 2, 4326)], 4326),
            new CircularString([new Point(88, 1, 4326), new Point(2, 1, 4326), new Point(2, 2, 4326), new Point(1, 2, 4326), new Point(88, 1, 4326)], 4326)
        ], 4326);
        $multiCurve = new MultiCurve([
            new CircularString([new Point(99, 0, 4326), new Point(1, 2, 4326), new Point(2, 0, 4326)], 4326),
            new LineString([new Point(99, 3, 4326), new Point(4, 4, 4326), new Point(5, 5, 4326)], 4326)
        ], 4326);
        $geometryCollection = new GeometryCollection([
            new Point(12, 1, 4326),
            new LineString([new Point(12, 2, 4326), new Point(3, 3, 4326), new Point(4, 4, 4326)], 4326),
            new Polygon([
                new LineString([new Point(12, 0, 4326), new Point(0, 5, 4326), new Point(5, 5, 4326), new Point(5, 0, 4326), new Point(12, 0, 4326),], 4326),
                new LineString([new Point(12, 1, 4326), new Point(1, 2, 4326), new Point(2, 2, 4326), new Point(2, 1, 4326), new Point(12, 1, 4326),], 4326)
            ], 4326)
        ], 4326);

        // first get what we had
        $ent1 = $mgr->findById(1);

        //
        // Insert All Shapes
        //
        $mgr->createQueryBuilder()
            ->update()
            ->setGeom('point_geom', $point)
            ->setGeom('linestring_geom', $lineString)
            ->setGeom('polygon_geom', $polygon)
            ->setGeom('multipoint_geom', $multiPoint)
            ->setGeom('multilinestring_geom', $multiLineString)
            ->setGeom('multipolygon_geom', $multiPolygon)
            ->setGeom('geomcollection_geom', $geometryCollection)
            ->setGeom('circularstring_geom', $circularString)
            ->setGeom('compoundcurve_geom', $compoundCurve)
            ->setGeom('curvedpolygon_geom', $curvePolygon)
            ->setGeom('multicurve_geom', $multiCurve)
            ->andWhereColumn('id', '=', 1)
            ->executeStatement()
        ;
        $this->assertEquals($oCnt, self::$dbHelper->countRows('geometry_test'));

        //
        // Select All Shapes
        //
        $mgr->_getEntityRepository()->clear();
        $ent2 = $mgr->findById(1);

        $this->assertFalse($ent1 === $ent2);

        $this->assertFalse($ent1->getPointGeom() == $ent2->getPointGeom());
        $this->assertFalse($ent1->getLinestringGeom() == $ent2->getLinestringGeom());
        $this->assertFalse($ent1->getPolygonGeom() == $ent2->getPolygonGeom());
        $this->assertFalse($ent1->getMultipointGeom() == $ent2->getMultipointGeom());
        $this->assertFalse($ent1->getMultilinestringGeom() == $ent2->getMultilinestringGeom());
        $this->assertFalse($ent1->getMultipolygonGeom() == $ent2->getMultipolygonGeom());
        $this->assertFalse($ent1->getGeomcollectionGeom() == $ent2->getGeomcollectionGeom());
        $this->assertFalse($ent1->getCircularStringGeom() == $ent2->getCircularStringGeom());
        $this->assertFalse($ent1->getCompoundcurveGeom() == $ent2->getCompoundcurveGeom());
        $this->assertFalse($ent1->getCurvepolygonGeom() == $ent2->getCurvepolygonGeom());
        $this->assertFalse($ent1->getMulticurveGeom() == $ent2->getMulticurveGeom());
    }

    public function testUpdate_asEntity()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        $oCnt = self::$dbHelper->countRows('geometry_test');

        //
        // Create All Shapes
        //
        $point = new Point(123, 7, 4326);
        $lineString = new LineString([
            new Point(-1, 1), new Point(2, 2), new Point(3, 3), new Point(44, 44)
        ], 4326);
        $multiPoint = new MultiPoint([
            new Point(-1, 1), new Point(2, 2), new Point(3, 3), new Point(4, 4)
        ], 4326);
        $polygon = new Polygon([
            new LineString([new Point(-1, 0), new Point(0, 5), new Point(5, 5), new Point(5, 0), new Point(-1, 0)]),
            new LineString([new Point(-1, 10), new Point(1, 2), new Point(2, 2), new Point(2, 1), new Point(-1, 10)])
        ], 4326);
        $multiLineString = new MultiLineString([
            new LineString([new Point(-1, 1), new Point(2, 2), new Point(3, 3)], 4326),
            new LineString([new Point(-1, 4), new Point(5, 5)], 4326),
            new LineString([new Point(-1, 6), new Point(7, 7), new Point(8, 8)], 4326)
        ], 4326);
        $multiPolygon = new MultiPolygon([
            new Polygon([
                new LineString([new Point(-1, 0), new Point(0, 5), new Point(5, 5), new Point(5, 0), new Point(-1, 0),]),
                new LineString([new Point(-1, 1), new Point(1, 2), new Point(2, 2), new Point(2, 1), new Point(-1, 1),])
            ], 4326),
            new Polygon([
                new LineString([new Point(-1, 8), new Point(0, 5), new Point(5, 5), new Point(5, 0), new Point(-1, 8),]),
                new LineString([new Point(-1, 9), new Point(1, 2), new Point(2, 2), new Point(2, 1), new Point(-1, 9),])
            ], 4326)
        ], 4326);
        $compoundCurve = new CompoundCurve([
            new LineString([new Point(-1, 0), new Point(-1, 1)]),
            new CircularString([new Point(-1, 1), new Point(4, 2), new Point(-1, 65)]),
            new LineString([new Point(-1, 65), new Point(6, 0)]),
        ], 4326);
        $circularString = new CircularString([
            new Point(-1, 0), new Point(4, 0), new Point(4, 4), new Point(0, 4), new Point(0, 0)
        ], 4326);
        $curvePolygon = new CurvePolygon([
            new CircularString([new Point(88, 0, 4326), new Point(6, 0, 4326), new Point(6, 6, 4326), new Point(0, 6, 4326), new Point(88, 0, 4326)], 4326),
            new LineString([new Point(88, 2, 4326), new Point(3, 2, 4326), new Point(3, 3, 4326), new Point(2, 3, 4326), new Point(88, 2, 4326)], 4326),
            new CircularString([new Point(-1, 1, 4326), new Point(2, 1, 4326), new Point(2, 2, 4326), new Point(1, 2, 4326), new Point(-1, 1, 4326)], 4326)
        ], 4326);
        $multiCurve = new MultiCurve([
            new CircularString([new Point(-1, 0, 4326), new Point(1, 2, 4326), new Point(2, 0, 4326)], 4326),
            new LineString([new Point(-1, 3, 4326), new Point(4, 4, 4326), new Point(5, 5, 4326)], 4326)
        ], 4326);
        $geometryCollection = new GeometryCollection([
            new Point(-1, 1, 4326),
            new LineString([new Point(-1, 2, 4326), new Point(3, 3, 4326), new Point(4, 4, 4326)], 4326),
            new Polygon([
                new LineString([new Point(-1, 0, 4326), new Point(0, 5, 4326), new Point(5, 5, 4326), new Point(5, 0, 4326), new Point(-1, 0, 4326),], 4326),
                new LineString([new Point(-1, 1, 4326), new Point(1, 2, 4326), new Point(2, 2, 4326), new Point(2, 1, 4326), new Point(-1, 1, 4326),], 4326)
            ], 4326)
        ], 4326);

        // first get what we had
        $ent1 = $mgr->findById(1);

        // make sure we've changed something
        $this->assertFalse($ent1->getPointGeom() == $point);
        $this->assertFalse($ent1->getLinestringGeom() == $lineString);
        $this->assertFalse($ent1->getPolygonGeom() == $polygon);

        //
        // Insert All Shapes
        //
        $ent1->setPointGeom($point)
            ->setLinestringGeom($lineString)
            ->setPolygonGeom($polygon)
            ->setMultipointGeom($multiPoint)
            ->setMultilinestringGeom($multiLineString)
            ->setMultipolygonGeom($multiPolygon)
            ->setGeomcollectionGeom($geometryCollection)
            ->setCircularStringGeom($circularString)
            ->setCompoundcurveGeom($compoundCurve)
            ->setCurvepolygonGeom($curvePolygon)
            ->setMulticurveGeom($multiCurve)
        ;
        $mgr->save($ent1);
        $this->assertEquals($oCnt, self::$dbHelper->countRows('geometry_test'));

        //
        // Select All Shapes
        //
        $mgr->_getEntityRepository()->clear();
        $ent2 = $mgr->findById(1);

        $this->assertFalse($ent1 === $ent2);
        $this->assertEquals($ent1->getId(), $ent2->getId());

        $this->assertTrue($ent1->getPointGeom() == $ent2->getPointGeom());
        $this->assertTrue($ent1->getLinestringGeom() == $ent2->getLinestringGeom());
        $this->assertTrue($ent1->getPolygonGeom() == $ent2->getPolygonGeom());
        $this->assertTrue($ent1->getMultipointGeom() == $ent2->getMultipointGeom());
        $this->assertTrue($ent1->getMultilinestringGeom() == $ent2->getMultilinestringGeom());
        $this->assertTrue($ent1->getMultipolygonGeom() == $ent2->getMultipolygonGeom());
        $this->assertTrue($ent1->getGeomcollectionGeom() == $ent2->getGeomcollectionGeom());
        $this->assertTrue($ent1->getCircularStringGeom() == $ent2->getCircularStringGeom());
        $this->assertTrue($ent1->getCompoundcurveGeom() == $ent2->getCompoundcurveGeom());
        $this->assertTrue($ent1->getCurvepolygonGeom() == $ent2->getCurvepolygonGeom());
        $this->assertTrue($ent1->getMulticurveGeom() == $ent2->getMulticurveGeom());
    }

    public function testQueryBuilder_andWhereColumn()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        $point = new Point(1, 2);

        /** @var GeoShapeAll\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->selectExcept(['multipoint_geom'])
            ->andWhereColumn('point_geom', '=', $point)
            ->fetchOneEntity();

        $this->assertEquals(1, $ent->getId());
        $this->assertTrue($point == $ent->getPointGeom());
    }

    public function testQueryBuilder_orWhereColumn()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);
        $mgr->clearRepository(false);

        $point = new Point(1, 2);

        /** @var GeoShapeAll\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->selectExcept(['multipoint_geom'])
            ->andWhereColumn('point_geom', '=', $point)
            ->orWhereColumn('id', '=', 9999)
            ->fetchOneEntity();

        $this->assertEquals(1, $ent->getId());
        $this->assertTrue($point == $ent->getPointGeom());
    }
}