<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmJsonTest;

class QueryBuilderJsonbTest extends TestCase
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
        self::$dbHelper->resetTo('Pgsql/fixtures/fixjson.sql');
    }

    public function testJsonbKeyExist()
    {
        $mgr = self::$dbHelper->getManager(OrmJsonTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb", "?", "str")
            ->fetchOne();
        $this->assertEquals(1, $res);
    }

    public function testJsonbKeyExistAll()
    {
        $mgr = self::$dbHelper->getManager(OrmJsonTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb", "?&", "str")
            ->fetchOne();
        $this->assertEquals(1, $res);

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb", "?&", ["str", "str_quotes"])
            ->fetchOne();
        $this->assertEquals(1, $res);

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb", "?&", ["str", "str_quotes", "NOT_IN_FIELD"])
            ->fetchOne();
        $this->assertEquals(0, $res);
    }

    public function testJsonbContains()
    {
        $mgr = self::$dbHelper->getManager(OrmJsonTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb", "@>", ["b"=>2])
            ->fetchOne();
        $this->assertEquals(1, $res);
    }

    public function testJsonbContainedIn()
    {
        $mgr = self::$dbHelper->getManager(OrmJsonTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb", "<@", ["a"=>1,"b"=>2,"c"=>3,"not_in_field"=>"x"])
            ->fetchOne();
        $this->assertEquals(1, $res);
    }

    public function testJsonbIntCompare()
    {
        $mgr = self::$dbHelper->getManager(OrmJsonTest\Manager::class);

        // "fld_jsonb->'num_int'" has 10 in it in the db

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb->'num_int'", "<", 11)
            ->fetchOne();
        $this->assertEquals(1, $res, '< failed');

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb->'num_int'", ">", 9)
            ->fetchOne();
        $this->assertEquals(1, $res, '> failed');

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb->'num_int'", "=", 10)
            ->fetchOne();
        $this->assertEquals(1, $res, '= failed');

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb->'num_int'", "<=", 10)
            ->fetchOne();
        $this->assertEquals(1, $res, '<= failed');

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb->'num_int'", ">=", 10)
            ->fetchOne();
        $this->assertEquals(1, $res, '>= failed');
    }

    public function testJsonbStrCompare()
    {
        $mgr = self::$dbHelper->getManager(OrmJsonTest\Manager::class);

        // "fld_jsonb->'str'" has 'lollypop' in it in the db

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb->>'str'", "=", 'lollypop')
            ->fetchOne();
        $this->assertEquals(1, $res, "->> operator failed");

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb->>'str_quotes'", "=", "He's about to say \"hi\"")
            ->fetchOne();
        $this->assertEquals(1, $res, "->> quoted failed");

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb->'str'", "=", 'lollypop')
            ->fetchOne();
        $this->assertEquals(1, $res, "-> operator failed");

        $res = $mgr->createQueryBuilder()
            ->select("count(*)")
            ->andWhereColumn("fld_jsonb->'str_quotes'", "=", "He's about to say \"hi\"")
            ->fetchOne();
        $this->assertEquals(1, $res, "-> quoted failed");
    }
}