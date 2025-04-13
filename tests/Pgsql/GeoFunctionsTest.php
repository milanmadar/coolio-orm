<?php

namespace Pgsql;

use Milanmadar\CoolioORM\Geo\Shape2D3DFactory;
use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use PHPUnit\Framework\TestCase;
use Milanmadar\CoolioORM\ORM;
use Milanmadar\CoolioORM\Geo\GeoFunctions;
use Milanmadar\CoolioORM\Geo\Shape2D\LineString;
use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\Geo\Shape2D\Polygon;
use tests\DbHelper;
use tests\Model\GeoShapeAll;
use tests\Model\GeoShapeZAll;

class GeoFunctionsTest extends TestCase
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
    }

    public function testST_3DIntersects()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometryz.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeZAll\Manager::class);

        $expr = GeoFunctions::ST_3DIntersects(
            new LineStringZ([new PointZ(2, 2, 2), new PointZ(1, 1, 1), new PointZ(0, 0, 0)]),
            'linestringz_geom',
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_Contains()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Contains(
            'polygon_geom',
            new Point(1, 1),
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_ContainsProperly()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_ContainsProperly(
            'polygon_geom',
            new Point(1, 1),
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_CoveredBy()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_CoveredBy(
            new Point(1, 1),
            'polygon_geom',
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_Covers()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Covers(
            'polygon_geom',
            new Point(1, 1),
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->fetchOne()
        ;
        $this->assertTrue($result);
    }

    public function testST_Crosses()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Crosses(
            'polygon_geom',
            new LineString([new Point(1, 1), new Point(10, 1)]),
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_Disjoint()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Disjoint(
            'polygon_geom',
            new LineString([new Point(10, 10), new Point(10, 1)]),
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_Equals()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Equals(
            'linestring_geom',
            new LineString([new Point(5, 1), new Point(3, 3), new Point(0, 0)]),
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_Intersects()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Intersects(
            new LineString([new Point(5, 1), new Point(3, 3), new Point(0, 0)]),
            'linestring_geom',
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_LineCrossingDirection()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_LineCrossingDirection(
            new LineString([new Point(5, 1), new Point(3, 3), new Point(0, 0)]),
            'linestring_geom',
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertEquals($result, GeoFunctions::CROSS_LEFT);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhereColumn($expr, '=', GeoFunctions::CROSS_LEFT)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_OrderingEquals()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_OrderingEquals(
            'linestring_geom',
            new LineString([new Point(0, 0), new Point(3, 3), new Point(5, 1)]),
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_Overlaps()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Overlaps(
            new LineString([new Point(-1, -1), new Point(0, 0), new Point(3, 3), new Point(1, 1)]),
            'linestring_geom',
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_Relate()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Relate(
            new LineString([new Point(-1, -1), new Point(0, 0), new Point(3, 3), new Point(1, 1)]),
            'linestring_geom',
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertEquals('1010F0102', $result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhereColumn($expr, '=', '1010F0102')
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_RelateMatch()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_RelateMatch("'101202FFF'", "'TTTTTTFFF'");

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->fetchOne()
        ;
        $this->assertEquals('1010F0102', $result);
    }

    public function testST_Touches()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Touches(
            'linestring_geom',
            new LineString([new Point(3, 3), new Point(8, 8)]),
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_Within()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Within(
            new Polygon([ new LineString([new Point(1, 1), new Point(2, 1), new Point(2, 2), new Point(1, 1)])]),
            'polygon_geom',
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_3DDWithin()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometryz.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeZAll\Manager::class);

        $expr = GeoFunctions::ST_3DDWithin(
            'pointz_geom',
            new LineStringZ([new PointZ(1, 1, 1), new PointZ(2, 1, 1), new PointZ(2, 2, 1), new PointZ(1, 1, 1)]),
            1.5
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertFalse($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_3DDFullyWithin()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometryz.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeZAll\Manager::class);

        $expr = GeoFunctions::ST_3DDFullyWithin(
            'pointz_geom',
            new LineStringZ([new PointZ(1, 1, 1), new PointZ(2, 1, 1), new PointZ(2, 2, 1), new PointZ(1, 1, 1)]),
            1.5
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertFalse($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_DFullyWithin()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_DFullyWithin(
            'point_geom',
            new LineString([new Point(1, 1, 1), new Point(2, 1, 1), new Point(2, 2, 1), new Point(1, 1, 1)]),
            5
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_DWithin()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_DWithin(
            'point_geom',
            new LineString([new Point(1, 1, 1), new Point(2, 1, 1), new Point(2, 2, 1), new Point(1, 1, 1)]),
            5
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_Distance()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Distance(
            'point_geom',
            new LineString([new Point(1, 1, 1), new Point(2, 1, 1), new Point(2, 2, 1), new Point(1, 1, 1)]),
            false
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertEquals(78594.85295358, $result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhereColumn($expr, '=', 78594.85295358)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_3DDistance()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometryz.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeZAll\Manager::class);

        $expr = GeoFunctions::ST_3DDistance(
            'pointz_geom',
            new LineStringZ([new PointZ(1, 1, 1), new PointZ(2, 1, 1), new PointZ(2, 2, 1), new PointZ(1, 1, 1)])
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertEquals(2.1213203435596424, $result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhereColumn($expr, '=', 2.1213203435596424)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_Length()
    {
        $mgr = self::$dbHelper->getManager(GeoShapeZAll\Manager::class);

        $expr = GeoFunctions::ST_Length(
            new LineString([new Point(0, 0), new Point(1, 0)])
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertEquals(111319.49079327357, $result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhereColumn($expr, '=', 111319.49079327357)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

    public function testST_ShortestLine()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_ShortestLine_asEWKT(
            'point_geom',
            new LineString([new Point(0, 0), new Point(3, 0)]),
            false
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $shortestLine = Shape2D3DFactory::createFromGeoEWKTString($result);
        $expectedLine = new LineString([new Point(1, 2), new Point(1, 0)]);
        $this->assertTrue($shortestLine == $expectedLine);
    }

    public function testST_Perimeter()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr = GeoFunctions::ST_Perimeter(
            'polygon_geom'
        );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertEquals(1774086.6712937024, $result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhereColumn($expr, '=', 1774086.6712937024)
            ->fetchOne();
        $this->assertNotNull($ent);
    }



    public function testST_Buffer()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry.sql');
        $mgr = self::$dbHelper->getManager(GeoShapeAll\Manager::class);

        $expr =
            GeoFunctions::ST_Contains(
                GeoFunctions::ST_Buffer(new Point(1, 1), 2.5),
                'point_geom'
            );

        $result = $mgr->createQueryBuilder()
            ->select($expr)
            ->andWhereColumn('id', '=', 1)
            ->fetchOne()
        ;
        $this->assertTrue($result);

        $ent = $mgr->createQueryBuilder()
            ->select('id')
            ->andWhere($expr)
            ->fetchOne();
        $this->assertNotNull($ent);
    }

}