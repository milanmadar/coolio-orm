<?php

namespace Pgsql;

use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\GeoJoinA;
use tests\Model\GeoJoinB;

class GeoJoinTest extends TestCase
{
    private static DbHelper $dbHelper;

    // This method runs once when the test class is loaded
    public static function setUpBeforeClass(): void
    {
        $conn1 = ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB1']);
        self::$dbHelper = new DbHelper( $conn1 );
    }

    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry_join.sql');
    }

    public function testJoinSimple_WithSchema_WhereNotGeo_Order()
    {
        $mgrA = self::$dbHelper->getManager(GeoJoinA\Manager::class);
        $mgrB = self::$dbHelper->getManager(GeoJoinB\Manager::class);

        $tblA = $mgrA->getDefaultDbTable();
        $tblB = $mgrB->getDefaultDbTable();

        $qb = $mgrA->createQueryBuilder()
            ->joinSimple($tblB, $tblA.'.id = '.$tblB.'.a_id')
            ->andWhereColumn($tblA.'.fld_varchar', '=', 'apple')
            ->andWhereColumn($tblB.'.fld_notinother', '=', 20)
            ->orderBy($tblB.".id ASC");

        $ents = $qb->fetchManyEntity();

        $this->assertEquals(1, count($ents));
    }

    public function testJoinSimple_WithSchema_WhereGeo_Order()
    {
        $mgrA = self::$dbHelper->getManager(GeoJoinA\Manager::class);
        $mgrB = self::$dbHelper->getManager(GeoJoinB\Manager::class);

        $point = new Point(3, 4);

        $tblA = $mgrA->getDefaultDbTable();
        $tblB = $mgrB->getDefaultDbTable();

        $qb = $mgrA->createQueryBuilder()
            ->joinSimple($tblB, $tblA.'.id = '.$tblB.'.a_id')
            ->andWhereColumn($tblA.'.fld_varchar', '=', 'apple')
            ->andWhereColumn($tblB.'.fld_notinother', '=', 20)
            ->andWhereColumn($tblA.'.point_geom', '=', $point)
            ->orderBy($tblB.".id ASC");

        $ents = $qb->fetchManyEntity();

        $this->assertEquals(1, count($ents));
    }

    public function testJoinSimple_WithoutSchema_WhereNotGeo_Order()
    {
        $mgrA = self::$dbHelper->getManager(GeoJoinA\Manager::class);
        $mgrB = self::$dbHelper->getManager(GeoJoinB\Manager::class);

        $tblA = $mgrA->getDefaultDbTable();
        $tblAnoschema = substr($tblA, strrpos($tblA, '.') + 1);
        $tblB = $mgrB->getDefaultDbTable();
        $tblBnoschema = substr($tblB, strrpos($tblB, '.') + 1);

        $qb = $mgrA->createQueryBuilder()
            ->joinSimple($tblB, $tblAnoschema.'.id = '.$tblBnoschema.'.a_id')
            ->andWhereColumn($tblAnoschema.'.fld_varchar', '=', 'apple')
            ->andWhereColumn($tblBnoschema.'.fld_notinother', '=', 20)
            ->orderBy($tblBnoschema.".id ASC");

        $ents = $qb->fetchManyEntity();

        $this->assertEquals(1, count($ents));
    }

    public function testJoinSimple_WithoutSchema_WhereGeo_Order()
    {
        $mgrA = self::$dbHelper->getManager(GeoJoinA\Manager::class);
        $mgrB = self::$dbHelper->getManager(GeoJoinB\Manager::class);

        $point = new Point(3, 4);

        $tblA = $mgrA->getDefaultDbTable();
        $tblAnoschema = substr($tblA, strrpos($tblA, '.') + 1);
        $tblB = $mgrB->getDefaultDbTable();
        $tblBnoschema = substr($tblB, strrpos($tblB, '.') + 1);

        $qb = $mgrA->createQueryBuilder()
            ->joinSimple($tblB, $tblAnoschema.'.id = '.$tblBnoschema.'.a_id')
            ->andWhereColumn($tblAnoschema.'.fld_varchar', '=', 'apple')
            ->andWhereColumn($tblBnoschema.'.fld_notinother', '=', 20)
            ->andWhereColumn($tblAnoschema.'.point_geom', '=', $point)
            ->orderBy($tblB.".id ASC");

        $ents = $qb->fetchManyEntity();

        $this->assertEquals(1, count($ents));
    }

    public function testJoinSimple_WhereGeo_WhereNoTable_Order()
    {
        $mgrA = self::$dbHelper->getManager(GeoJoinA\Manager::class);
        $mgrB = self::$dbHelper->getManager(GeoJoinB\Manager::class);

        $point = new Point(3, 4);

        $tblA = $mgrA->getDefaultDbTable();
        $tblB = $mgrB->getDefaultDbTable();

        $qb = $mgrA->createQueryBuilder()
            ->joinSimple($tblB, $tblA.'.id = '.$tblB.'.a_id')
            ->andWhereColumn('fld_varchar', '=', 'apple')
            ->andWhereColumn('fld_notinother', '=', 20)
            ->andWhereColumn('point_geom', '=', $point)
            ->orderBy($tblB.".id ASC");

        $ents = $qb->fetchManyEntity();

        $this->assertEquals(1, count($ents));
    }
}