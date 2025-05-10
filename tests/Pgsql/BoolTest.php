<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;

class BoolTest extends TestCase
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
        $ent1 = $mgr->findById(1);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);

        $this->assertIsBool($ent1->getFldBool());
        $this->assertTrue($ent1->getFldBool());

        $ent1->setFldBool(false);
        $this->assertIsBool($ent1->getFldBool());
        $this->assertFalse($ent1->getFldBool());
    }

    public function testCreate()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->createEntity();

        $this->assertIsBool($ent1->getFldBool());
        $this->assertTrue($ent1->getFldBool());
    }

}