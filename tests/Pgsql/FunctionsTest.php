<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use Milanmadar\CoolioORM\Geo\Shape2D\Point;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;

class FunctionsTest extends TestCase
{
    private static DbHelper $dbHelper;

    // This method runs once when the test class is loaded
    public static function setUpBeforeClass(): void
    {
        $conn = ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB1']);
        self::$dbHelper = new DbHelper( $conn );
    }

    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
        self::$dbHelper->resetTo('Pgsql/fixtures/functions.sql');
    }

    public function testCallReturnTable()
    {
        $params = [
            1,
            'abc',
            true,
            2.5,
            new Point(3, 4)
        ];

        $res = ORM::instance()->callFunction($_ENV['DB_POSTGRES_DB1'], 'orm_test_function', $params);

        $this->assertEquals([
            'out_int' => 2,
            'out_text' => 'abcOK',
            'out_bool' => false,
            'out_float' => 5.0,
            'out_geom_point' => 'SRID=4326;POINT(4 5)'
        ], $res);
    }

    public function testCallReturnInt()
    {
        $params = [
            1,
            'abc',
            true,
            2.5,
            new Point(3, 4)
        ];

        $res = ORM::instance()->callFunction($_ENV['DB_POSTGRES_DB1'], 'orm_test_function_retInt', $params);

        $this->assertEquals(100, $res);
    }

}