<?php

namespace Pgsql;

use Milanmadar\CoolioORM\Geo\ShapeZM\PointZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\PolygonZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\MultiPointZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\MultiLineStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\MultiPolygonZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\GeometryCollectionZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\CircularStringZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\CompoundCurveZM;
//use Milanmadar\CoolioORM\Geo\ShapeZM\CurvePolygonZM;
//use Milanmadar\CoolioORM\Geo\ShapeZM\MultiCurveZM;
use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\GeoShapeZMAll;

class GeoZMTest extends TestCase
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
        self::$dbHelper->resetTo('Pgsql/fixtures/geometryzm.sql');
    }

    public function testSelectAllShapes_FindOne_QueryBuilder_ExplicitSELECTlist()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeZMAll\Manager::class);

        /** @var GeoShapeZMAll\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->select(
                'pointzm_geom',
                'linestringzm_geom',
                'polygonzm_geom',
                'multipointzm_geom',
                'multilinestringzm_geom',
                'multipolygonzm_geom',
                'geomcollectionzm_geom',
                'circularstringzm_geom',
                'compoundcurvezm_geom',
                //'curvepolygonzm_geom', 'multicurvezm_geom'
            )
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZM\PointZM', $ent->getPointZMGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZM\LineStringZM', $ent->getLinestringZMGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZM\PolygonZM', $ent->getPolygonZMGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZM\MultiPointZM', $ent->getMultiPointZMGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZM\MultiLineStringZM', $ent->getMultiLineStringZMGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZM\MultiPolygonZM', $ent->getMultipolygonZMGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZM\GeometryCollectionZM', $ent->getGeomcollectionZMGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZM\CircularStringZM', $ent->getCircularStringZMGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZM\CompoundCurveZM', $ent->getCompoundcurveZMGeom());
    }

    public function testInsert_asObjects()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeZMAll\Manager::class);

        $oCnt = self::$dbHelper->countRows('geometryzm_test');

        //
        // Create All Shapes
        //
        $pointZ = new PointZM(6, 7, 8,4326);
        $lineStringZ = new LineStringZM([
            new PointZM(1, 1, 1), new PointZM(2, 2, 2), new PointZM(3, 3, 3), new PointZM(4, 4, 4)
        ], 4326);
        $multiPointZ = new MultiPointZM([
            new PointZM(1, 1, 1), new PointZM(2, 2, 2), new PointZM(3, 3, 3), new PointZM(4, 4, 4)
        ], 4326);
        $polygonZ = new PolygonZM([
            new LineStringZM([new PointZM(0, 0, 1), new PointZM(0, 5, 2), new PointZM(5, 5, 3), new PointZM(5, 0, 4), new PointZM(0, 0, 1)]),
            new LineStringZM([new PointZM(1, 1, 1), new PointZM(1, 2, 2), new PointZM(2, 2,3 ), new PointZM(2, 1, 4), new PointZM(1, 1, 1)])
        ], 4326);
        $multiLineStringZ = new MultiLineStringZM([
            new LineStringZM([new PointZM(1, 1, 1), new PointZM(2, 2, 2), new PointZM(3, 3, 3)], 4326),
            new LineStringZM([new PointZM(4, 4, 0), new PointZM(5, 5, 0)], 4326),
            new LineStringZM([new PointZM(6, 6, 2), new PointZM(7, 7, 3), new PointZM(8, 8, 4)], 4326)
        ], 4326);
        $multiPolygonZ = new MultiPolygonZM([
            new PolygonZM([
                new LineStringZM([new PointZM(0, 0, 2.5), new PointZM(0, 5, 1), new PointZM(5, 5, 4), new PointZM(5, 0, 4), new PointZM(0, 0, 2.5),]),
                new LineStringZM([new PointZM(1, 1, 2.5), new PointZM(1, 2, 1), new PointZM(2, 2, 4), new PointZM(2, 1, 4), new PointZM(1, 1, 2.5),])
            ], 4326),
            new PolygonZM([
                new LineStringZM([new PointZM(8, 8, 2.5), new PointZM(0, 5, 1), new PointZM(5, 5, 4), new PointZM(5, 0, 4), new PointZM(8, 8, 2.5),]),
                new LineStringZM([new PointZM(9, 9, 2.5), new PointZM(1, 2, 1), new PointZM(2, 2, 4), new PointZM(2, 1, 4), new PointZM(9, 9, 2.5),])
            ], 4326)
        ], 4326);
        $geometryCollectionZ = new GeometryCollectionZM([
            new PointZM(1, 1, 5, 4326),
            new LineStringZM([new PointZM(2, 2, 5, 4326), new PointZM(3, 3, 5, 4326), new PointZM(4, 4, 5, 4326)], 4326),
            new PolygonZM([
                new LineStringZM([new PointZM(0, 0, 5, 4326), new PointZM(0, 5, 5, 4326), new PointZM(5, 5, 5, 4326), new PointZM(5, 0, 5, 4326), new PointZM(0, 0, 5, 4326),], 4326),
                new LineStringZM([new PointZM(1, 1, 5, 4326), new PointZM(1, 2, 5, 4326), new PointZM(2, 2, 5, 4326), new PointZM(2, 1, 5, 4326), new PointZM(1, 1, 5, 4326),], 4326)
            ], 4326)
        ], 4326);
        $circularStringZ = new CircularStringZM([
            new PointZM(0, 0, 9), new PointZM(4, 0, 3), new PointZM(4, 4, 3), new PointZM(0, 4, 3), new PointZM(0, 0, 9)
        ], 4326);
        $compoundCurveZ = new CompoundCurveZM([
            new LineStringZM([new PointZM(2, 0, 0), new PointZM(3, 1, 0)]),
            new CircularStringZM([new PointZM(3, 1, 0), new PointZM(4, 2, 0), new PointZM(5, 1, 0)]),
            new LineStringZM([new PointZM(5, 1, 0), new PointZM(6, 0, 0)]),
        ], 4326);
        $curvePolygonZ = new CurvePolygonZM([
            new CircularStringZM([new PointZM(0, 0, 2.3, 4326), new PointZM(6, 0, 2.3, 4326), new PointZM(6, 6, 2.3, 4326), new PointZM(0, 6, 2.3, 4326), new PointZM(0, 0, 2.3, 4326)], 4326),
            new LineStringZM([new PointZM(2, 2, 2.3, 4326), new PointZM(3, 2, 2.3, 4326), new PointZM(3, 3, 2.3, 4326), new PointZM(2, 3, 2.3, 4326), new PointZM(2, 2, 2.3, 4326)], 4326),
            new CircularStringZM([new PointZM(1, 1, 2.3, 4326), new PointZM(2, 1, 2.3, 4326), new PointZM(2, 2, 2.3, 4326), new PointZM(1, 2, 2.3, 4326), new PointZM(1, 1, 2.3, 4326)], 4326)
        ], 4326);
        $multiCurveZ = new MultiCurveZM([
            new CircularStringZM([new PointZM(0, 0, 8, 4326), new PointZM(1, 2, 8, 4326), new PointZM(2, 0, 8, 4326)], 4326),
            new LineStringZM([new PointZM(3, 3, 8, 4326), new PointZM(4, 4, 8, 4326), new PointZM(5, 5, 8, 4326)], 4326)
        ], 4326);

        //
        // Insert All Shapes
        //
        $mgr->createQueryBuilder()
            ->insert()
            ->setGeom('pointzm_geom', $pointZ)
            ->setGeom('linestringzm_geom', $lineStringZ)
            ->setGeom('polygonzm_geom', $polygonZ)
            ->setGeom('multipointzm_geom', $multiPointZ)
            ->setGeom('multilinestringzm_geom', $multiLineStringZ)
            ->setGeom('multipolygonzm_geom', $multiPolygonZ)
            ->setGeom('geomcollectionzm_geom', $geometryCollectionZ)
            ->setGeom('circularstringzm_geom', $circularStringZ)
            ->setGeom('compoundcurvezm_geom', $compoundCurveZ)
            ->setGeom('curvedpolygonzm_geom', $curvePolygonZ)
            ->setGeom('multicurvezm_geom', $multiCurveZ)
            ->executeStatement()
        ;
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geometryzm_test'));

        //
        // Select All Shapes
        //
        $mgr->_getEntityRepository()->clear();
        $ent = $mgr->findById(2);

        $this->assertTrue($pointZ == $ent->getPointZGeom());
        $this->assertTrue($lineStringZ == $ent->getLinestringZGeom());
        $this->assertTrue($polygonZ == $ent->getPolygonZGeom());
        $this->assertTrue($multiPointZ == $ent->getMultipointZGeom());
        $this->assertTrue($multiLineStringZ == $ent->getMultilinestringZGeom());
        $this->assertTrue($multiPolygonZ == $ent->getMultipolygonZGeom());
        $this->assertTrue($geometryCollectionZ == $ent->getGeomcollectionZGeom());
        $this->assertTrue($circularStringZ == $ent->getCircularstringZGeom());
        $this->assertTrue($compoundCurveZ == $ent->getCompoundcurvezGeom());
        $this->assertTrue($curvePolygonZ == $ent->getCurvepolygonzGeom());
        $this->assertTrue($multiCurveZ == $ent->getMulticurvezGeom());
    }

    public function testInsert_asEntity()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeZMAll\Manager::class);

        $oCnt = self::$dbHelper->countRows('geometryzm_test');

        //
        // Create All Shapes
        //
        $pointZ = new PointZM(6, 7, 8,4326);
        $lineStringZ = new LineStringZM([
            new PointZM(1, 1, 1), new PointZM(2, 2, 2), new PointZM(3, 3, 3), new PointZM(4, 4, 4)
        ], 4326);
        $multiPointZ = new MultiPointZM([
            new PointZM(1, 1, 1), new PointZM(2, 2, 2), new PointZM(3, 3, 3), new PointZM(4, 4, 4)
        ], 4326);
        $polygonZ = new PolygonZM([
            new LineStringZM([new PointZM(0, 0, 1), new PointZM(0, 5, 2), new PointZM(5, 5, 3), new PointZM(5, 0, 4), new PointZM(0, 0, 1)]),
            new LineStringZM([new PointZM(1, 1, 1), new PointZM(1, 2, 2), new PointZM(2, 2,3 ), new PointZM(2, 1, 4), new PointZM(1, 1, 1)])
        ], 4326);
        $multiLineStringZ = new MultiLineStringZM([
            new LineStringZM([new PointZM(1, 1, 1), new PointZM(2, 2, 2), new PointZM(3, 3, 3)], 4326),
            new LineStringZM([new PointZM(4, 4, 0), new PointZM(5, 5, 0)], 4326),
            new LineStringZM([new PointZM(6, 6, 2), new PointZM(7, 7, 3), new PointZM(8, 8, 4)], 4326)
        ], 4326);
        $multiPolygonZ = new MultiPolygonZM([
            new PolygonZM([
                new LineStringZM([new PointZM(0, 0, 2.5), new PointZM(0, 5, 1), new PointZM(5, 5, 4), new PointZM(5, 0, 4), new PointZM(0, 0, 2.5),]),
                new LineStringZM([new PointZM(1, 1, 2.5), new PointZM(1, 2, 1), new PointZM(2, 2, 4), new PointZM(2, 1, 4), new PointZM(1, 1, 2.5),])
            ], 4326),
            new PolygonZM([
                new LineStringZM([new PointZM(8, 8, 2.5), new PointZM(0, 5, 1), new PointZM(5, 5, 4), new PointZM(5, 0, 4), new PointZM(8, 8, 2.5),]),
                new LineStringZM([new PointZM(9, 9, 2.5), new PointZM(1, 2, 1), new PointZM(2, 2, 4), new PointZM(2, 1, 4), new PointZM(9, 9, 2.5),])
            ], 4326)
        ], 4326);
        $geometryCollectionZ = new GeometryCollectionZM([
            new PointZM(1, 1, 5, 4326),
            new LineStringZM([new PointZM(2, 2, 5, 4326), new PointZM(3, 3, 5, 4326), new PointZM(4, 4, 5, 4326)], 4326),
            new PolygonZM([
                new LineStringZM([new PointZM(0, 0, 5, 4326), new PointZM(0, 5, 5, 4326), new PointZM(5, 5, 5, 4326), new PointZM(5, 0, 5, 4326), new PointZM(0, 0, 5, 4326),], 4326),
                new LineStringZM([new PointZM(1, 1, 5, 4326), new PointZM(1, 2, 5, 4326), new PointZM(2, 2, 5, 4326), new PointZM(2, 1, 5, 4326), new PointZM(1, 1, 5, 4326),], 4326)
            ], 4326)
        ], 4326);
        $circularStringZ = new CircularStringZM([
            new PointZM(0, 0, 9), new PointZM(4, 0, 3), new PointZM(4, 4, 3), new PointZM(0, 4, 3), new PointZM(0, 0, 9)
        ], 4326);
        $compoundCurveZ = new CompoundCurveZM([
            new LineStringZM([new PointZM(2, 0, 0), new PointZM(3, 1, 0)]),
            new CircularStringZM([new PointZM(3, 1, 0), new PointZM(4, 2, 0), new PointZM(5, 1, 0)]),
            new LineStringZM([new PointZM(5, 1, 0), new PointZM(6, 0, 0)]),
        ], 4326);
        $curvePolygonZ = new CurvePolygonZM([
            new CircularStringZM([new PointZM(0, 0, 2.3, 4326), new PointZM(6, 0, 2.3, 4326), new PointZM(6, 6, 2.3, 4326), new PointZM(0, 6, 2.3, 4326), new PointZM(0, 0, 2.3, 4326)], 4326),
            new LineStringZM([new PointZM(2, 2, 2.3, 4326), new PointZM(3, 2, 2.3, 4326), new PointZM(3, 3, 2.3, 4326), new PointZM(2, 3, 2.3, 4326), new PointZM(2, 2, 2.3, 4326)], 4326),
            new CircularStringZM([new PointZM(1, 1, 2.3, 4326), new PointZM(2, 1, 2.3, 4326), new PointZM(2, 2, 2.3, 4326), new PointZM(1, 2, 2.3, 4326), new PointZM(1, 1, 2.3, 4326)], 4326)
        ], 4326);
        $multiCurveZ = new MultiCurveZM([
            new CircularStringZM([new PointZM(0, 0, 8, 4326), new PointZM(1, 2, 8, 4326), new PointZM(2, 0, 8, 4326)], 4326),
            new LineStringZM([new PointZM(3, 3, 8, 4326), new PointZM(4, 4, 8, 4326), new PointZM(5, 5, 8, 4326)], 4326)
        ], 4326);

        $newEnt = $mgr->createEntity()
            ->setPointZGeom($pointZ)
            ->setLinestringZGeom($lineStringZ)
            ->setPolygonZGeom($polygonZ)
            ->setMultipointZGeom($multiPointZ)
            ->setMultilinestringZGeom($multiLineStringZ)
            ->setMultipolygonZGeom($multiPolygonZ)
            ->setGeomcollectionZGeom($geometryCollectionZ)
            ->setCircularstringZGeom($circularStringZ)
            ->setCompoundcurvezGeom($compoundCurveZ)
            ->setCurvepolygonZGeom($curvePolygonZ)
            ->setMulticurvezGeom($multiCurveZ)
        ;

        //
        // Insert All Shapes
        //
        $mgr->save($newEnt);
        $mgr->_getEntityRepository()->clear();
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geometryzm_test'));

        //
        // Select All Shapes
        //
        $ent = $mgr->findById(2);

        $this->assertTrue($pointZ == $ent->getPointZGeom());
        $this->assertTrue($lineStringZ == $ent->getLinestringZGeom());
        $this->assertTrue($polygonZ == $ent->getPolygonZGeom());
        $this->assertTrue($multiPointZ == $ent->getMultipointZGeom());
        $this->assertTrue($multiLineStringZ == $ent->getMultilinestringZGeom());
        $this->assertTrue($multiPolygonZ == $ent->getMultipolygonZGeom());
        $this->assertTrue($geometryCollectionZ == $ent->getGeomcollectionZGeom());
        $this->assertTrue($circularStringZ == $ent->getCircularstringZGeom());
        $this->assertTrue($compoundCurveZ == $ent->getCompoundcurvezGeom());
        $this->assertTrue($curvePolygonZ == $ent->getCurvepolygonzGeom());
        $this->assertTrue($multiCurveZ == $ent->getMulticurvezGeom());
    }
}