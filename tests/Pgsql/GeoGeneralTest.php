<?php

namespace Pgsql;

use Milanmadar\CoolioORM\Geo\ShapeZ\LineStringZ;
use Milanmadar\CoolioORM\Geo\ShapeZ\PointZ;
use PHPUnit\Framework\TestCase;
use Milanmadar\CoolioORM\ORM;
use Milanmadar\CoolioORM\Geo\GeoFunctions;
use tests\DbHelper;
use tests\Model\GeometryzGeneral;

class GeoGeneralTest extends TestCase
{
    const REGIONAL_SRID = 32633;

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

    public function testReadGeneralPointInDifferentSRIDs()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry_general.sql');
        $mgr = self::$dbHelper->getManager(GeometryzGeneral\Manager::class);

        $geomWgs84    = new PointZ(
            12.6939,
            47.0744,
            3798,
            4326
        );
        $geomRegional = new PointZ(
            324924.3050776483,
            5216012.548242352,
            3798,
            self::REGIONAL_SRID
        );

        $pointEnt = $mgr->findById(1);

        $this->assertEquals(self::REGIONAL_SRID, $pointEnt->getSridRegional());

        $this->assertInstanceOf(PointZ::class, $pointEnt->getGeomWgs());
        $this->assertTrue($geomWgs84->equals( $pointEnt->getGeomWgs() ));
        $this->assertEquals(4326, $pointEnt->getGeomWgs()->getSrid());

        $this->assertInstanceOf(PointZ::class, $pointEnt->getGeomRegional());
        $this->assertTrue($geomRegional->equals( $pointEnt->getGeomRegional() ));
        $this->assertEquals(self::REGIONAL_SRID, $pointEnt->getGeomRegional()->getSrid());
    }

    public function testSmartInsert()
    {
        self::$dbHelper->resetTo('Pgsql/fixtures/geometry_general.sql');
        $mgr = self::$dbHelper->getManager(GeometryzGeneral\Manager::class);

        // this is the same as id=2 in geometry_general.sql fixation
        $lineWgs = new LineStringZ([
            new PointZ(12.6939, 47.0744, 3798, 4326),
            new PointZ(12.6980, 47.0800, 3825, 4326),
            new PointZ(12.7020, 47.0840, 3848, 4326),
        ], 4326);

        $sql = "
            WITH parsed_geom AS (
                SELECT ".
                    GeoFunctions::ST_Transform(
                        GeoFunctions::ST_GeomFromEWKT_geom($lineWgs),
                        self::REGIONAL_SRID
                    ).
                " as g
            )
            INSERT INTO ".$mgr->getDbTable()." (
                geom_wgs,
                geom_regional,
                srid_regional,
                length_meters,
                elevation_meters
            )
            SELECT
                ".GeoFunctions::ST_GeomFromEWKT_geom($lineWgs).",
                g,
                ".self::REGIONAL_SRID.",
                ".GeoFunctions::ST_3DLength('g').",
                ".GeoFunctions::ST_ZMax('g')." - ".GeoFunctions::ST_ZMin('g')."
            FROM parsed_geom;
        ";

        $mgr->getDb()->executeStatement($sql);

        // see if we got what we wanted
        $lineEntExpected = $mgr->findById(2);
        /** @var LineStringZ $lineGeomWgsExpected */
        $lineGeomWgsExpected = $lineEntExpected->getGeomWgs();
        /** @var LineStringZ $lineGeomRegionalExpected */
        $lineGeomRegionalExpected = $lineEntExpected->getGeomRegional();

        $lineEntNew = $mgr->findById(3);
        /** @var LineStringZ $lineGeomWgsNew */
        $lineGeomWgsNew = $lineEntNew->getGeomWgs();
        /** @var LineStringZ $lineGeomRegionalNew */
        $lineGeomRegionalNew = $lineEntNew->getGeomRegional();

        $this->assertEquals($lineEntExpected->getSridRegional(), $lineEntNew->getSridRegional());

        $this->assertInstanceOf(LineStringZ::class, $lineGeomWgsNew);
        $this->assertTrue($lineGeomWgsExpected->getPoints()[0]->equals( $lineGeomWgsNew->getPoints()[0] ));
        $this->assertTrue($lineGeomWgsExpected->getPoints()[1]->equals( $lineGeomWgsNew->getPoints()[1] ));
        $this->assertTrue($lineGeomWgsExpected->getPoints()[2]->equals( $lineGeomWgsNew->getPoints()[2] ));
        $this->assertEquals(4326, $lineGeomWgsNew->getSrid());

        $this->assertInstanceOf(LineStringZ::class, $lineGeomRegionalNew);
        $this->assertTrue($lineGeomRegionalExpected->getPoints()[0]->equals( $lineGeomRegionalNew->getPoints()[0] ));
        $this->assertTrue($lineGeomRegionalExpected->getPoints()[1]->equals( $lineGeomRegionalNew->getPoints()[1] ));
        $this->assertTrue($lineGeomRegionalExpected->getPoints()[2]->equals( $lineGeomRegionalNew->getPoints()[2] ));
        $this->assertEquals(self::REGIONAL_SRID, $lineGeomRegionalNew->getSrid());
    }

}