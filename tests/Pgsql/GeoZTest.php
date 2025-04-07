<?php

namespace Pgsql;

use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
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

        //
        // Insert All Shapes
        //
        $mgr->createQueryBuilder()
            ->insert()
            ->setValue('pointz_geom', $pointZ)
            ->setValue('linestringz_geom', $lineStringZ)
            ->executeStatement()
        ;
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geometryz_test'));

        //
        // Select All Shapes
        //
        $ent = $mgr->findById(2);

        $this->assertTrue($pointZ == $ent->getPointZGeom());
        $this->assertTrue($lineStringZ == $ent->getLinestringZGeom());
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

        $newEnt = $mgr->createEntity()
            ->setPointZGeom($pointZ)
            ->setLinestringZGeom($lineStringZ)
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
    }
}