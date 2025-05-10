<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\DbKeywords;

class KeywordsTest extends TestCase
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

    public function testCRUD()
    {
        $mgr = self::$dbHelper->getManager(DbKeywords\Manager::class);

        // insert
        $ent = $mgr->createEntity()
            ->setClass('classVal')
            ->setNull('nullVal')
            ->setInt(1);
        $mgr->save($ent);
        $id = $ent->getId();

        // update
        $ent
            ->setClass('classVal updates')
            ->setNull('nullVal updated')
            ->setInt(2222);
        $mgr->save($ent);

        $this->assertTrue(true);
    }

}