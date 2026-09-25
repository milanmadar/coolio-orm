<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;

class ArrayIntTest extends TestCase
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

    public function testGetSet()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        /** @var OrmTest\Entity $ent1 */
        $ent1 = $mgr->findById(2);
        $this->assertEquals([1, 2], $ent1->getFldIntArray());

        $new = [1,2,3];
        $ent1->setFldIntArray($new);
        $mgr->save($ent1);
        $mgr->clearRepository(true);

        $ent1 = $mgr->findById(2);
        $this->assertEquals($new, $ent1->getFldIntArray());
    }

    public function testWhereEmptyArray()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_int_array', '@>', [])
            ->fetchOneEntity();
        $this->assertNull($ent1);

        $ent2 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_int_array', '&&', [])
            ->fetchOneEntity();
        $this->assertNull($ent2);
    }
}