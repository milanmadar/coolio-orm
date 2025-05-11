<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;
use tests\Model\OrmOther;

class BulkInsertTest extends TestCase
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
        self::$dbHelper->resetTo('Pgsql/fixtures/fix.sql');
    }

    public function testBulkInsertManager()
    {
        $howMany = 1000;

        $ents = [];

        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        for($i=0; $i<$howMany; ++$i) {
            $ents[] = $mgr->createEntity()->setFldInt($i);
        }

        $mgr->bulkInsert($ents);

        $this->assertEquals($howMany+10, self::$dbHelper->countRows('orm_test'));
    }

    public function testBulkInsertORM()
    {
        $howMany = 1000;

        $ents = [];

        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $mgr2 = self::$dbHelper->getManager(OrmOther\Manager::class);
        for($i=0; $i<$howMany; ++$i) {
            $ents[] = $mgr->createEntity()->setFldInt($i);
            $ents[] = $mgr2->createEntity()->setFldInt($i)->setTitle('t');
        }

        ORM::instance()->bulkInsert($ents);

        $this->assertEquals($howMany+10, self::$dbHelper->countRows('orm_test'));
        $this->assertEquals($howMany+2, self::$dbHelper->countRows('orm_other'));
    }

    public function testBulkInsertORMDifferentColumns()
    {
        $howMany = 1000;

        $ents = [];

        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $mgr2 = self::$dbHelper->getManager(OrmOther\Manager::class);
        for($i=0; $i<$howMany; ++$i) {
            $ents[] = $mgr->createEntity()->setFldInt($i);
            if( $i % 10 == 0 ) {
                $ents[] = $mgr2->createEntity()->setFldInt($i);
            } else {
                $ents[] = $mgr2->createEntity()->setFldInt($i)->setTitle('t');
            }
        }

        ORM::instance()->bulkInsert($ents);

        $this->assertEquals($howMany+10, self::$dbHelper->countRows('orm_test'));
        $this->assertEquals($howMany+2, self::$dbHelper->countRows('orm_other'));
    }

}