<?php

namespace Pgsql;

use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\Geography;

class GeographyTest extends TestCase
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
        $mgr = self::$dbHelper->getManager(Geography\Manager::class);
        $mgr->clearRepository(false);

        /** @var Geography\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->select('point_geom','linestring_geom')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchOneEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Point', $ent->getPointGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\LineString', $ent->getLinestringGeom());
    }

    public function testSelectAllShapes_FindMany_QueryBuilder_Star()
    {
        $mgr = self::$dbHelper->getManager(Geography\Manager::class);
        $mgr->clearRepository(false);

        /** @var Geography\Entity[] $ents */
        $ents = $mgr->createQueryBuilder()
            ->select('*')
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchManyEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Point', $ents[0]->getPointGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\LineString', $ents[0]->getLinestringGeom());
    }

    public function testSelectAllShapes_FindMany_QueryBuilder_NoSELECT()
    {
        $mgr = self::$dbHelper->getManager(Geography\Manager::class);
        $mgr->clearRepository(false);

        /** @var Geography\Entity[] $ents */
        $ents = $mgr->createQueryBuilder()
            ->andWhere('1=1')
            ->limit(0, 1)
            ->fetchManyEntity()
        ;

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Point', $ents[0]->getPointGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\LineString', $ents[0]->getLinestringGeom());
    }

    public function testgetSQLNamedParameters()
    {
        $mgr = self::$dbHelper->getManager(Geography\Manager::class);
        $mgr->clearRepository(false);

        //$expect = "SELECT id, ST_AsGeoJSON(point_geom) AS point_geom, ST_SRID(point_geom) AS point_geom_srid, ST_AsGeoJSON(linestring_geom) AS linestring_geom, ST_SRID(linestring_geom) AS linestring_geom_srid, ST_AsGeoJSON(polygon_geom) AS polygon_geom, ST_SRID(polygon_geom) AS polygon_geom_srid, ST_AsGeoJSON(multipoint_geom) AS multipoint_geom, ST_SRID(multipoint_geom) AS multipoint_geom_srid, ST_AsGeoJSON(multilinestring_geom) AS multilinestring_geom, ST_SRID(multilinestring_geom) AS multilinestring_geom_srid, ST_AsGeoJSON(multipolygon_geom) AS multipolygon_geom, ST_SRID(multipolygon_geom) AS multipolygon_geom_srid, ST_AsGeoJSON(geomcollection_geom) AS geomcollection_geom, ST_SRID(geomcollection_geom) AS geomcollection_geom_srid, ST_AsEWKT(circularstring_geom) AS circularstring_geom, ST_AsEWKT(compoundcurve_geom) AS compoundcurve_geom, ST_AsEWKT(curvedpolygon_geom) AS curvedpolygon_geom, ST_AsEWKT(multicurve_geom) AS multicurve_geom FROM public.geometry_test WHERE 1=1 LIMIT 1";
        $expect = "SELECT geography_test.id, ST_AsEWKT(geography_test.point_geom) AS point_geom, ST_AsEWKT(geography_test.linestring_geom) AS linestring_geom FROM public.geography_test WHERE 1=1 LIMIT 1";
        $sql = $mgr->createQueryBuilder()
            ->andWhere('1=1')
            ->limit(0, 1)
            ->getSQLNamedParameters()
        ;

        $this->assertEquals($expect, $sql);
    }

    public function testSelectAllShapes_FindMany_noQueryBuilder()
    {
        $mgr = self::$dbHelper->getManager(Geography\Manager::class);
        $mgr->clearRepository(false);

        /** @var Geography\Entity[] $ents */
        $ents = $mgr->findManyWhere("1=1");

        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\Point', $ents[0]->getPointGeom());
        $this->assertInstanceOf('\Milanmadar\CoolioORM\Geo\Shape2D\LineString', $ents[0]->getLinestringGeom());
    }

    public function testInsert_asObjects()
    {
        $mgr = self::$dbHelper->getManager(Geography\Manager::class);
        $mgr->clearRepository(false);

        $oCnt = self::$dbHelper->countRows('geography_test');

        //
        // Create All Shapes
        //
        $point = new Point(6, 7, 4326);
        $lineString = new LineString([
            new Point(1, 1), new Point(2, 2), new Point(3, 3), new Point(4, 4)
        ], 4326);

        //
        // Insert All Shapes
        //
        $mgr->createQueryBuilder()
            ->insert()
            ->setGeom('point_geom', $point)
            ->setGeom('linestring_geom', $lineString)
            ->executeStatement()
        ;
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geography_test'));

        //
        // Select All Shapes
        //
        $mgr->_getEntityRepository()->clear();
        $ent = $mgr->findById(2);

        $this->assertEquals($point->toEWKT(), $ent->getPointGeom()->toEWKT());

        $this->assertTrue($point == $ent->getPointGeom());
        $this->assertTrue($lineString == $ent->getLinestringGeom());
    }

    public function testInsert_asEntity()
    {
        $mgr = self::$dbHelper->getManager(Geography\Manager::class);
        $mgr->clearRepository(false);

        $oCnt = self::$dbHelper->countRows('geography_test');

        //
        // Create All Shapes
        //
        $point = new Point(6, 7, 4326);
        $lineString = new LineString([
            new Point(1, 1), new Point(2, 2), new Point(3, 3), new Point(4, 4)
        ], 4326);

        $newEnt = $mgr->createEntity()
            ->setPointGeom($point)
            ->setLinestringGeom($lineString)
        ;

        //
        // Insert All Shapes
        //
        $mgr->save($newEnt);
        $mgr->_getEntityRepository()->clear();
        $this->assertEquals($oCnt+1, self::$dbHelper->countRows('geography_test'));

        //
        // Select All Shapes
        //
        $ent = $mgr->findById(2);

        $this->assertTrue($point == $ent->getPointGeom());
        $this->assertTrue($lineString == $ent->getLinestringGeom());
    }

    public function testUpdate_asObjects()
    {
        $mgr = self::$dbHelper->getManager(Geography\Manager::class);
        $mgr->clearRepository(false);

        $oCnt = self::$dbHelper->countRows('geography_test');

        //
        // Create All Shapes
        //
        $point = new Point(6, 7, 4326);
        $lineString = new LineString([
            new Point(11, 1), new Point(2, 2), new Point(3, 3), new Point(44, 44)
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
            ->andWhereColumn('id', '=', 1)
            ->executeStatement()
        ;
        $this->assertEquals($oCnt, self::$dbHelper->countRows('geography_test'));

        //
        // Select All Shapes
        //
        $mgr->_getEntityRepository()->clear();
        $ent2 = $mgr->findById(1);

        $this->assertFalse($ent1 === $ent2);

        $this->assertFalse($ent1->getPointGeom() == $ent2->getPointGeom());
        $this->assertFalse($ent1->getLinestringGeom() == $ent2->getLinestringGeom());
    }

    public function testUpdate_asEntity()
    {
        $mgr = self::$dbHelper->getManager(Geography\Manager::class);
        $mgr->clearRepository(false);

        $oCnt = self::$dbHelper->countRows('geography_test');

        //
        // Create All Shapes
        //
        $point = new Point(123, 7, 4326);
        $lineString = new LineString([
            new Point(-1, 1), new Point(2, 2), new Point(3, 3), new Point(44, 44)
        ], 4326);

        // first get what we had
        $ent1 = $mgr->findById(1);

        // make sure we've changed something
        $this->assertFalse($ent1->getPointGeom() == $point);
        $this->assertFalse($ent1->getLinestringGeom() == $lineString);

        //
        // Insert All Shapes
        //
        $ent1->setPointGeom($point)
            ->setLinestringGeom($lineString)
        ;
        $mgr->save($ent1);
        $this->assertEquals($oCnt, self::$dbHelper->countRows('geography_test'));

        //
        // Select All Shapes
        //
        $mgr->_getEntityRepository()->clear();
        $ent2 = $mgr->findById(1);

        $this->assertFalse($ent1 === $ent2);
        $this->assertEquals($ent1->getId(), $ent2->getId());

        $this->assertTrue($ent1->getPointGeom() == $ent2->getPointGeom());
        $this->assertTrue($ent1->getLinestringGeom() == $ent2->getLinestringGeom());
    }

    public function testQueryBuilder_andWhereColumn()
    {
        $mgr = self::$dbHelper->getManager(Geography\Manager::class);
        $mgr->clearRepository(false);

        $point = new Point(1, 2);

        /** @var Geography\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->selectExcept(['linestring_geom'])
            ->andWhereColumn('point_geom', '=', $point)
            ->fetchOneEntity();

        $this->assertEquals(1, $ent->getId());
        $this->assertTrue($point == $ent->getPointGeom());
    }

    public function testQueryBuilder_orWhereColumn()
    {
        $mgr = self::$dbHelper->getManager(Geography\Manager::class);
        $mgr->clearRepository(false);

        $point = new Point(1, 2);

        /** @var Geography\Entity $ent */
        $ent = $mgr->createQueryBuilder()
            ->selectExcept(['linestring_geom'])
            ->andWhereColumn('point_geom', '=', $point)
            ->orWhereColumn('id', '=', 9999)
            ->fetchOneEntity();

        $this->assertEquals(1, $ent->getId());
        $this->assertTrue($point == $ent->getPointGeom());
    }
}