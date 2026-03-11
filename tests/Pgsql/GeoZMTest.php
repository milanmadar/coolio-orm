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
use Milanmadar\CoolioORM\Geo\ShapeZM\CurvePolygonZM;
use Milanmadar\CoolioORM\Geo\ShapeZM\MultiCurveZM;
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
                'curvepolygonzm_geom',
                'multicurvezm_geom'
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
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZM\CurvePolygonZM', $ent->getCurvepolygonZMGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZM\MultiCurveZM', $ent->getMulticurveZMGeom());
    }

    public function testInsert_asObjects()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeZMAll\Manager::class);

        $oCnt = self::$dbHelper->countRows('geometryzm_test');

        //
        // Create All Shapes
        //
        $pointZM = new PointZM(6, 7, 8,99, 4326);
        $lineStringZM = new LineStringZM([
            new PointZM(1, 1, 1,99), new PointZM(2, 2, 2,99), new PointZM(3, 3, 3,99), new PointZM(4, 4, 4,99)
        ], 4326);
        $multiPointZM = new MultiPointZM([
            new PointZM(1, 1, 1,99), new PointZM(2, 2, 2,99), new PointZM(3, 3, 3,99), new PointZM(4, 4, 4,99)
        ], 4326);
        $polygonZM = new PolygonZM([
            new LineStringZM([new PointZM(0, 0, 1,99), new PointZM(0, 5, 2,99), new PointZM(5, 5, 3,99), new PointZM(5, 0, 4,99), new PointZM(0, 0, 1,99)]),
            new LineStringZM([new PointZM(1, 1, 1,99), new PointZM(1, 2, 2,99), new PointZM(2, 2,3,99), new PointZM(2, 1, 4,99), new PointZM(1, 1, 1,99)])
        ], 4326);
        $multiLineStringZM = new MultiLineStringZM([
            new LineStringZM([new PointZM(1, 1, 1,99), new PointZM(2, 2, 2,99), new PointZM(3, 3, 3,99)], 4326),
            new LineStringZM([new PointZM(4, 4, 0,99), new PointZM(5, 5, 0,99)], 4326),
            new LineStringZM([new PointZM(6, 6, 2,99), new PointZM(7, 7, 3,99), new PointZM(8, 8, 4,99)], 4326)
        ], 4326);
        $multiPolygonZM = new MultiPolygonZM([
            new PolygonZM([
                new LineStringZM([new PointZM(0, 0, 2.5,90), new PointZM(0, 5, 1,91), new PointZM(5, 5, 4,92), new PointZM(5, 0, 4,93), new PointZM(0, 0, 2.5,94),]),
                new LineStringZM([new PointZM(1, 1, 2.5,95), new PointZM(1, 2, 1,96), new PointZM(2, 2, 4,97), new PointZM(2, 1, 4,98), new PointZM(1, 1, 2.5,99),])
            ], 4326),
            new PolygonZM([
                new LineStringZM([new PointZM(8, 8, 2.5,80), new PointZM(0, 5, 1,81), new PointZM(5, 5, 4,82), new PointZM(5, 0, 4,83), new PointZM(8, 8, 2.5,84),]),
                new LineStringZM([new PointZM(9, 9, 2.5,85), new PointZM(1, 2, 1,86), new PointZM(2, 2, 4,87), new PointZM(2, 1, 4,88), new PointZM(9, 9, 2.5,89),])
            ], 4326)
        ], 4326);
        $geometryCollectionZM = new GeometryCollectionZM([
            new PointZM(1, 1, 5,99, 4326),
            new LineStringZM([new PointZM(2, 2, 5,99, 4326), new PointZM(3, 3, 5,99, 4326), new PointZM(4, 4, 5,99, 4326)], 4326),
            new PolygonZM([
                new LineStringZM([new PointZM(0, 0, 5,99, 4326), new PointZM(0, 5, 5,99, 4326), new PointZM(5, 5, 5,99, 4326), new PointZM(5, 0, 5,99, 4326), new PointZM(0, 0, 5,99, 4326),], 4326),
                new LineStringZM([new PointZM(1, 1, 5,99, 4326), new PointZM(1, 2, 5,99, 4326), new PointZM(2, 2, 5,99, 4326), new PointZM(2, 1, 5,99, 4326), new PointZM(1, 1, 5,99, 4326),], 4326)
            ], 4326)
        ], 4326);
        $circularStringZM = new CircularStringZM([
            new PointZM(0, 0, 9,99), new PointZM(4, 0, 3,99), new PointZM(4, 4, 3,99), new PointZM(0, 4, 3,99), new PointZM(0, 0, 9,99)
        ], 4326);
        $compoundCurveZM = new CompoundCurveZM([
            new LineStringZM([new PointZM(2, 0, 0,99), new PointZM(3, 1, 0,99)]),
            new CircularStringZM([new PointZM(3, 1, 0,99), new PointZM(4, 2, 0,99), new PointZM(5, 1, 0,99)]),
            new LineStringZM([new PointZM(5, 1, 0,99), new PointZM(6, 0, 0,99)]),
        ], 4326);
        $curvePolygonZM = new CurvePolygonZM([
            new CircularStringZM([new PointZM(0, 0, 2.3,99, 4326), new PointZM(6, 0, 2.3,99, 4326), new PointZM(6, 6, 2.3,99, 4326), new PointZM(0, 6, 2.3,99, 4326), new PointZM(0, 0, 2.3,99, 4326)], 4326),
            new LineStringZM([new PointZM(2, 2, 2.3,99, 4326), new PointZM(3, 2, 2.3,99, 4326), new PointZM(3, 3, 2.3,99, 4326), new PointZM(2, 3, 2.3,99, 4326), new PointZM(2, 2, 2.3,99, 4326)], 4326),
            new CircularStringZM([new PointZM(1, 1, 2.3,99, 4326), new PointZM(2, 1, 2.3,99, 4326), new PointZM(2, 2, 2.3,99, 4326), new PointZM(1, 2, 2.3,99, 4326), new PointZM(1, 1, 2.3,99, 4326)], 4326)
        ], 4326);
        $multiCurveZM = new MultiCurveZM([
            new CircularStringZM([new PointZM(0, 0, 8,99, 4326), new PointZM(1, 2, 8,99, 4326), new PointZM(2, 0, 8,99, 4326)], 4326),
            new LineStringZM([new PointZM(3, 3, 8,99, 4326), new PointZM(4, 4, 8,99, 4326), new PointZM(5, 5, 8,99, 4326)], 4326)
        ], 4326);

        //
        // Insert All Shapes
        //
        $mgr->createQueryBuilder()
            ->insert()
            ->setGeom('pointzm_geom', $pointZM)
            ->setGeom('linestringzm_geom', $lineStringZM)
            ->setGeom('polygonzm_geom', $polygonZM)
            ->setGeom('multipointzm_geom', $multiPointZM)
            ->setGeom('multilinestringzm_geom', $multiLineStringZM)
            ->setGeom('multipolygonzm_geom', $multiPolygonZM)
            ->setGeom('geomcollectionzm_geom', $geometryCollectionZM)
            ->setGeom('circularstringzm_geom', $circularStringZM)
            ->setGeom('compoundcurvezm_geom', $compoundCurveZM)
            ->setGeom('curvepolygonzm_geom', $curvePolygonZM)
            ->setGeom('multicurvezm_geom', $multiCurveZM)
            ->executeStatement()
        ;
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geometryzm_test'));

        //
        // Select All Shapes
        //
        $mgr->_getEntityRepository()->clear();
        $ent = $mgr->findById(2);
$_AAA = $ent->getCurvepolygonZMGeom();
        $this->assertTrue($pointZM == $ent->getPointZMGeom());
        $this->assertTrue($lineStringZM == $ent->getLinestringZMGeom());
        $this->assertTrue($polygonZM == $ent->getPolygonZMGeom());
        $this->assertTrue($multiPointZM == $ent->getMultipointZMGeom());
        $this->assertTrue($multiLineStringZM == $ent->getMultilinestringZMGeom());
        $this->assertTrue($multiPolygonZM == $ent->getMultipolygonZMGeom());
        $this->assertTrue($geometryCollectionZM == $ent->getGeomcollectionZMGeom());
        $this->assertTrue($circularStringZM == $ent->getCircularstringZMGeom());
        $this->assertTrue($compoundCurveZM == $ent->getCompoundcurveZMGeom());
        $this->assertTrue($curvePolygonZM == $ent->getCurvepolygonZMGeom());
        $this->assertTrue($multiCurveZM == $ent->getMulticurveZMGeom());
    }

    public function testInsert_asEntity()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeZMAll\Manager::class);

        $oCnt = self::$dbHelper->countRows('geometryzm_test');

        //
        // Create All Shapes
        //
        $pointZM = new PointZM(6, 7, 8, 100,4326);
        $lineStringZM = new LineStringZM([
            new PointZM(1, 1, 1, 100), new PointZM(2, 2, 2, 100), new PointZM(3, 3, 3, 100), new PointZM(4, 4, 4, 100)
        ], 4326);
        $multiPointZM = new MultiPointZM([
            new PointZM(1, 1, 1, 100), new PointZM(2, 2, 2, 100), new PointZM(3, 3, 3, 100), new PointZM(4, 4, 4, 100)
        ], 4326);
        $polygonZM = new PolygonZM([
            new LineStringZM([
                new PointZM(0, 0, 1, 100), new PointZM(0, 5, 2, 100), new PointZM(5, 5, 3, 100), new PointZM(5, 0, 4, 100), new PointZM(0, 0, 1, 100)
            ]),
            new LineStringZM([
                new PointZM(1, 1, 1, 100), new PointZM(1, 2, 2, 100), new PointZM(2, 2, 3, 100), new PointZM(2, 1, 4, 100), new PointZM(1, 1, 1, 100)
            ])
        ], 4326);
        $multiLineStringZM = new MultiLineStringZM([
            new LineStringZM([
                new PointZM(1, 1, 1, 100), new PointZM(2, 2, 2, 100), new PointZM(3, 3, 3, 100)
            ], 4326),
            new LineStringZM([
                new PointZM(4, 4, 0, 100), new PointZM(5, 5, 0, 100)
            ], 4326),
            new LineStringZM([
                new PointZM(6, 6, 2, 100), new PointZM(7, 7, 3, 100), new PointZM(8, 8, 4, 100)
            ], 4326)
        ], 4326);
        $multiPolygonZM = new MultiPolygonZM([
            new PolygonZM([
                new LineStringZM([
                    new PointZM(0, 0, 2.5, 100), new PointZM(0, 5, 1, 100), new PointZM(5, 5, 4, 100), new PointZM(5, 0, 4, 100), new PointZM(0, 0, 2.5, 100)
                ]),
                new LineStringZM([
                    new PointZM(1, 1, 2.5, 100), new PointZM(1, 2, 1, 100), new PointZM(2, 2, 4, 100), new PointZM(2, 1, 4, 100), new PointZM(1, 1, 2.5, 100)
                ])
            ], 4326),
            new PolygonZM([
                new LineStringZM([
                    new PointZM(8, 8, 2.5, 100), new PointZM(0, 5, 1, 100), new PointZM(5, 5, 4, 100), new PointZM(5, 0, 4, 100), new PointZM(8, 8, 2.5, 100)
                ]),
                new LineStringZM([
                    new PointZM(9, 9, 2.5, 100), new PointZM(1, 2, 1, 100), new PointZM(2, 2, 4, 100), new PointZM(2, 1, 4, 100), new PointZM(9, 9, 2.5, 100)
                ])
            ], 4326)
        ], 4326);
        $geometryCollectionZM = new GeometryCollectionZM([
            new PointZM(1, 1, 5, 100),
            new LineStringZM([
                new PointZM(2, 2, 5, 100), new PointZM(3, 3, 5, 100), new PointZM(4, 4, 5, 100)
            ], 4326),
            new PolygonZM([
                new LineStringZM([
                    new PointZM(0, 0, 5, 100), new PointZM(0, 5, 5, 100), new PointZM(5, 5, 5, 100), new PointZM(5, 0, 5, 100), new PointZM(0, 0, 5, 100)
                ], 4326),
                new LineStringZM([
                    new PointZM(1, 1, 5, 100), new PointZM(1, 2, 5, 100), new PointZM(2, 2, 5, 100), new PointZM(2, 1, 5, 100), new PointZM(1, 1, 5, 100)
                ], 4326)
            ], 4326)
        ], 4326);
        $circularStringZM = new CircularStringZM([
            new PointZM(0, 0, 9, 100), new PointZM(4, 0, 3, 100), new PointZM(4, 4, 3, 100), new PointZM(0, 4, 3, 100), new PointZM(0, 0, 9, 100)
        ], 4326);
        $compoundCurveZM = new CompoundCurveZM([
            new LineStringZM([new PointZM(2, 0, 0, 100), new PointZM(3, 1, 0, 100)]),
            new CircularStringZM([new PointZM(3, 1, 0, 100), new PointZM(4, 2, 0, 100), new PointZM(5, 1, 0, 100)]),
            new LineStringZM([new PointZM(5, 1, 0, 100), new PointZM(6, 0, 0, 100)])
        ], 4326);
        $curvePolygonZM = new CurvePolygonZM([
            new CircularStringZM([
                new PointZM(0, 0, 2.3, 100), new PointZM(6, 0, 2.3, 100), new PointZM(6, 6, 2.3, 100), new PointZM(0, 6, 2.3, 100), new PointZM(0, 0, 2.3, 100)
            ], 4326),
            new LineStringZM([
                new PointZM(2, 2, 2.3, 100), new PointZM(3, 2, 2.3, 100), new PointZM(3, 3, 2.3, 100), new PointZM(2, 3, 2.3, 100), new PointZM(2, 2, 2.3, 100)
            ], 4326),
            new CircularStringZM([
                new PointZM(1, 1, 2.3, 100), new PointZM(2, 1, 2.3, 100), new PointZM(2, 2, 2.3, 100), new PointZM(1, 2, 2.3, 100), new PointZM(1, 1, 2.3, 100)
            ], 4326)
        ], 4326);
        $multiCurveZM = new MultiCurveZM([
            new CircularStringZM([
                new PointZM(0, 0, 8, 100), new PointZM(1, 2, 8, 100), new PointZM(2, 0, 8, 100)
            ], 4326),
            new LineStringZM([
                new PointZM(3, 3, 8, 100), new PointZM(4, 4, 8, 100), new PointZM(5, 5, 8, 100)
            ], 4326)
        ], 4326);

        $newEnt = $mgr->createEntity()
            ->setPointZMGeom($pointZM)
            ->setLinestringZMGeom($lineStringZM)
            ->setPolygonZMGeom($polygonZM)
            ->setMultipointZMGeom($multiPointZM)
            ->setMultilinestringZMGeom($multiLineStringZM)
            ->setMultipolygonZMGeom($multiPolygonZM)
            ->setGeomcollectionZMGeom($geometryCollectionZM)
            ->setCircularstringZMGeom($circularStringZM)
            ->setCompoundcurvezMGeom($compoundCurveZM)
            ->setCurvepolygonZMGeom($curvePolygonZM)
            ->setMulticurvezMGeom($multiCurveZM)
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

        $this->assertTrue($pointZM == $ent->getPointZMGeom());
        $this->assertTrue($lineStringZM == $ent->getLinestringZMGeom());
        $this->assertTrue($polygonZM == $ent->getPolygonZMGeom());
        $this->assertTrue($multiPointZM == $ent->getMultipointZMGeom());
        $this->assertTrue($multiLineStringZM == $ent->getMultilinestringZMGeom());
        $this->assertTrue($multiPolygonZM == $ent->getMultipolygonZMGeom());
        $this->assertTrue($geometryCollectionZM == $ent->getGeomcollectionZMGeom());
        $this->assertTrue($circularStringZM == $ent->getCircularstringZMGeom());
        $this->assertTrue($compoundCurveZM == $ent->getCompoundcurveZMGeom());
        $this->assertTrue($curvePolygonZM == $ent->getCurvepolygonZMGeom());
        $this->assertTrue($multiCurveZM == $ent->getMulticurveZMGeom());
    }
}