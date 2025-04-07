<?php

namespace Pgsql;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\PolygonZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\MultiPointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\MultiLineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\MultiPolygonZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\GeometryCollectionZ;
use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\GeoShapeZAll;

class GeoZTest extends TestCase
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
        self::$dbHelper->resetTo('Pgsql/fixtures/geometryz.sql');
    }

    public function testSelectAllShapes_FindOne_QueryBuilder_ExplicitSELECTlist()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeZAll\Manager::class);

        /** @var GeoShapeZAll\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->select('pointz_geom', 'linestringz_geom')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZ\PointZ', $ent->getPointZGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ', $ent->getLinestringZGeom());
    }

    public function testInsert_asObjects()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeZAll\Manager::class);

        $oCnt = self::$dbHelper->countRows('geometryz_test');

        //
        // Create All Shapes
        //
        $pointZ = new PointZ(6, 7, 8,4326);
        $lineStringZ = new LineStringZ([
            new PointZ(1, 1, 1), new PointZ(2, 2, 2), new PointZ(3, 3, 3), new PointZ(4, 4, 4)
        ], 4326);
        $multiPointZ = new MultiPointZ([
            new PointZ(1, 1, 1), new PointZ(2, 2, 2), new PointZ(3, 3, 3), new PointZ(4, 4, 4)
        ], 4326);
        $polygonZ = new PolygonZ([
            new LineStringZ([new PointZ(0, 0, 1), new PointZ(0, 5, 2), new PointZ(5, 5, 3), new PointZ(5, 0, 4), new PointZ(0, 0, 1)]),
            new LineStringZ([new PointZ(1, 1, 1), new PointZ(1, 2, 2), new PointZ(2, 2,3 ), new PointZ(2, 1, 4), new PointZ(1, 1, 1)])
        ], 4326);
        $multiLineStringZ = new MultiLineStringZ([
            new LineStringZ([new PointZ(1, 1, 1), new PointZ(2, 2, 2), new PointZ(3, 3, 3)], 4326),
            new LineStringZ([new PointZ(4, 4, 0), new PointZ(5, 5, 0)], 4326),
            new LineStringZ([new PointZ(6, 6, 2), new PointZ(7, 7, 3), new PointZ(8, 8, 4)], 4326)
        ], 4326);
        $multiPolygonZ = new MultiPolygonZ([
            new PolygonZ([
                new LineStringZ([new PointZ(0, 0, 2.5), new PointZ(0, 5, 1), new PointZ(5, 5, 4), new PointZ(5, 0, 4), new PointZ(0, 0, 2.5),]),
                new LineStringZ([new PointZ(1, 1, 2.5), new PointZ(1, 2, 1), new PointZ(2, 2, 4), new PointZ(2, 1, 4), new PointZ(1, 1, 2.5),])
            ], 4326),
            new PolygonZ([
                new LineStringZ([new PointZ(8, 8, 2.5), new PointZ(0, 5, 1), new PointZ(5, 5, 4), new PointZ(5, 0, 4), new PointZ(8, 8, 2.5),]),
                new LineStringZ([new PointZ(9, 9, 2.5), new PointZ(1, 2, 1), new PointZ(2, 2, 4), new PointZ(2, 1, 4), new PointZ(9, 9, 2.5),])
            ], 4326)
        ], 4326);
        $geometryCollectionZ = new GeometryCollectionZ([
            new PointZ(1, 1, 5, 4326),
            new LineStringZ([new PointZ(2, 2, 5, 4326), new PointZ(3, 3, 5, 4326), new PointZ(4, 4, 5, 4326)], 4326),
            new PolygonZ([
                new LineStringZ([new PointZ(0, 0, 5, 4326), new PointZ(0, 5, 5, 4326), new PointZ(5, 5, 5, 4326), new PointZ(5, 0, 5, 4326), new PointZ(0, 0, 5, 4326),], 4326),
                new LineStringZ([new PointZ(1, 1, 5, 4326), new PointZ(1, 2, 5, 4326), new PointZ(2, 2, 5, 4326), new PointZ(2, 1, 5, 4326), new PointZ(1, 1, 5, 4326),], 4326)
            ], 4326)
        ], 4326);

        //
        // Insert All Shapes
        //
        $mgr->createQueryBuilder()
            ->insert()
            ->setValue('pointz_geom', $pointZ)
            ->setValue('linestringz_geom', $lineStringZ)
            ->setValue('polygonz_geom', $polygonZ)
            ->setValue('multipointz_geom', $multiPointZ)
            ->setValue('multilinestringz_geom', $multiLineStringZ)
            ->setValue('multipolygonz_geom', $multiPolygonZ)
            ->setValue('geomcollectionz_geom', $geometryCollectionZ)
            ->executeStatement()
        ;
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geometryz_test'));

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
    }

    public function testInsert_asEntity()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeZAll\Manager::class);

        $oCnt = self::$dbHelper->countRows('geometryz_test');

        //
        // Create All Shapes
        //
        $pointZ = new PointZ(6, 7, 8,4326);
        $lineStringZ = new LineStringZ([
            new PointZ(1, 1, 1), new PointZ(2, 2, 2), new PointZ(3, 3, 3), new PointZ(4, 4, 4)
        ], 4326);
        $multiPointZ = new MultiPointZ([
            new PointZ(1, 1, 1), new PointZ(2, 2, 2), new PointZ(3, 3, 3), new PointZ(4, 4, 4)
        ], 4326);
        $polygonZ = new PolygonZ([
            new LineStringZ([new PointZ(0, 0, 1), new PointZ(0, 5, 2), new PointZ(5, 5, 3), new PointZ(5, 0, 4), new PointZ(0, 0, 1)]),
            new LineStringZ([new PointZ(1, 1, 1), new PointZ(1, 2, 2), new PointZ(2, 2,3 ), new PointZ(2, 1, 4), new PointZ(1, 1, 1)])
        ], 4326);
        $multiLineStringZ = new MultiLineStringZ([
            new LineStringZ([new PointZ(1, 1, 1), new PointZ(2, 2, 2), new PointZ(3, 3, 3)], 4326),
            new LineStringZ([new PointZ(4, 4, 0), new PointZ(5, 5, 0)], 4326),
            new LineStringZ([new PointZ(6, 6, 2), new PointZ(7, 7, 3), new PointZ(8, 8, 4)], 4326)
        ], 4326);
        $multiPolygonZ = new MultiPolygonZ([
            new PolygonZ([
                new LineStringZ([new PointZ(0, 0, 2.5), new PointZ(0, 5, 1), new PointZ(5, 5, 4), new PointZ(5, 0, 4), new PointZ(0, 0, 2.5),]),
                new LineStringZ([new PointZ(1, 1, 2.5), new PointZ(1, 2, 1), new PointZ(2, 2, 4), new PointZ(2, 1, 4), new PointZ(1, 1, 2.5),])
            ], 4326),
            new PolygonZ([
                new LineStringZ([new PointZ(8, 8, 2.5), new PointZ(0, 5, 1), new PointZ(5, 5, 4), new PointZ(5, 0, 4), new PointZ(8, 8, 2.5),]),
                new LineStringZ([new PointZ(9, 9, 2.5), new PointZ(1, 2, 1), new PointZ(2, 2, 4), new PointZ(2, 1, 4), new PointZ(9, 9, 2.5),])
            ], 4326)
        ], 4326);
        $geometryCollectionZ = new GeometryCollectionZ([
            new PointZ(1, 1, 5, 4326),
            new LineStringZ([new PointZ(2, 2, 5, 4326), new PointZ(3, 3, 5, 4326), new PointZ(4, 4, 5, 4326)], 4326),
            new PolygonZ([
                new LineStringZ([new PointZ(0, 0, 5, 4326), new PointZ(0, 5, 5, 4326), new PointZ(5, 5, 5, 4326), new PointZ(5, 0, 5, 4326), new PointZ(0, 0, 5, 4326),], 4326),
                new LineStringZ([new PointZ(1, 1, 5, 4326), new PointZ(1, 2, 5, 4326), new PointZ(2, 2, 5, 4326), new PointZ(2, 1, 5, 4326), new PointZ(1, 1, 5, 4326),], 4326)
            ], 4326)
        ], 4326);

        $newEnt = $mgr->createEntity()
            ->setPointZGeom($pointZ)
            ->setLinestringZGeom($lineStringZ)
            ->setPolygonZGeom($polygonZ)
            ->setMultipointZGeom($multiPointZ)
            ->setMultilinestringZGeom($multiLineStringZ)
            ->setMultipolygonZGeom($multiPolygonZ)
            ->setGeomcollectionZGeom($geometryCollectionZ)
        ;

        //
        // Insert All Shapes
        //
        $mgr->save($newEnt);
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geometryz_test'));

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
    }
}