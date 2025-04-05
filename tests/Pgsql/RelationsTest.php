<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;
use tests\Model\OrmOther;
use tests\Model\OrmThird;

class RelationsTest extends TestCase
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
        self::$dbHelper->resetTo('Pgsql/fixtures/fix.sql');
    }

    public function testIDNewTSetNullO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormTest->setOrmOther(null);

        $this->assertNull($ormTest->getOrmOther());
        $this->assertNull($ormTest->getOrmOtherId());
    }

    public function testIDNewTSetNewO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormOther = $ormOtherMgr->createEntity();
        $ormTest->setOrmOther($ormOther);

        $this->assertTrue($ormOther === $ormTest->getOrmOther());
        $this->assertNull($ormTest->getOrmOtherId());
    }

    public function testIDNewTSetExistingO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormOther = $ormOtherMgr->findById(1);
        // just make sure it was in the db
        $this->assertEquals(1, $ormOther->getId());

        $ormTest->setOrmOther($ormOther);

        $this->assertTrue($ormOther === $ormTest->getOrmOther());
        $this->assertEquals($ormOther->getId(), $ormTest->getOrmOtherId());
    }

    public function testIDExistingEmptyTSetNewO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->findById(1);
        // just make sure it was in the db and has no attached ormOther
        $this->assertEquals(1, $ormTest->getId());
        $this->assertNull($ormTest->getOrmOtherId());
        $this->assertNull($ormTest->getOrmOther());

        $ormOther = $ormOtherMgr->createEntity();
        $ormTest->setOrmOther($ormOther);

        $this->assertTrue($ormOther === $ormTest->getOrmOther());
        $this->assertNull($ormTest->getOrmOtherId());
    }

    public function testIDExistingEmptyTExistingNewO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->findById(1);
        // just make sure it was in the db and has no attached ormOther
        $this->assertEquals(1, $ormTest->getId());
        $this->assertNull($ormTest->getOrmOtherId());
        $this->assertNull($ormTest->getOrmOther());

        $ormOther = $ormOtherMgr->findById(1);
        // just make sure it was in the db
        $this->assertEquals(1, $ormOther->getId());

        $ormTest->setOrmOther($ormOther);

        $this->assertTrue($ormOther === $ormTest->getOrmOther());
        $this->assertEquals($ormOther->getId(), $ormTest->getOrmOtherId());
    }

    public function testIDExistingNotEmptyTSetNewO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->findById(10);
        // just make sure it was in the db and has attached ormOther
        $this->assertEquals(10, $ormTest->getId());
        $this->assertEquals(1, $ormTest->getOrmOtherId());
        $this->assertNotNull($ormTest->getOrmOther());

        $ormOther = $ormOtherMgr->createEntity();
        $ormTest->setOrmOther($ormOther);

        $this->assertTrue($ormOther === $ormTest->getOrmOther());
        $this->assertNull($ormTest->getOrmOtherId());
    }

    public function testIDExistingNotEmptyTSetExistingO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->findById(10);

        // just make sure it was in the db and has attached ormOther
        $this->assertEquals(10, $ormTest->getId());
        $this->assertEquals(1, $ormTest->getOrmOtherId());
        $this->assertNotNull($ormTest->getOrmOther());

        $ormOther = $ormOtherMgr->findById(2);
        // just make sure it was in the db
        $this->assertEquals(2, $ormOther->getId());

        $ormTest->setOrmOther($ormOther);

        $this->assertTrue($ormOther === $ormTest->getOrmOther());
        $this->assertEquals($ormOther->getId(), $ormTest->getOrmOtherId());
    }

    public function testIDNewTSetNullOid()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormTest->setOrmOtherId(null);

        $this->assertNull($ormTest->getOrmOther());
        $this->assertNull($ormTest->getOrmOtherId());
    }

    public function testIDNewTSetNotexistingOid()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormTest->setOrmOtherId(9876);

        $this->assertEquals(9876, $ormTest->getOrmOtherId());

        $ormOther = $ormTest->getOrmOther();
        $this->assertNull($ormOther);

        // the $ormTest->getOrmOther() call made the Id to be null too
        $this->assertNull($ormTest->getOrmOtherId());
    }

    public function testIDNewTSetExistingOid()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormOther = $ormOtherMgr->findById(1);
        // just make sure it was in the db
        $this->assertEquals(1, $ormOther->getId());

        $ormTest->setOrmOtherId($ormOther->getId());

        $this->assertTrue($ormOther === $ormTest->getOrmOther());
        $this->assertEquals($ormOther->getId(), $ormTest->getOrmOtherId());
    }

    public function testIDHas()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->createEntity();

        $ormOther = $ormOtherMgr->findById(1);
        // just make sure it was in the db
        $this->assertEquals(1, $ormOther->getId());

        // doesn't have at first
        $this->assertFalse( $ormTest->hasOrmOther() );

        // now set it,
        $ormTest->setOrmOtherId($ormOther->getId());

        // now it has it
        $this->assertTrue( $ormTest->hasOrmOther() );
    }

    public function testIDManipulate()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->createEntity();

        // SET ONE
        $ormOther1 = $ormOtherMgr->findById(1);
        // just make sure it was in the db
        $this->assertEquals(1, $ormOther1->getId());

        $ormTest->setOrmOtherId($ormOther1->getId());

        $this->assertTrue($ormOther1 === $ormTest->getOrmOther());
        $this->assertEquals($ormOther1->getId(), $ormTest->getOrmOtherId());

        // SET ANOTHER
        $ormOther2 = $ormOtherMgr->findById(2);
        // just make sure it was in the db
        $this->assertEquals(2, $ormOther2->getId());

        $ormTest->setOrmOtherId($ormOther2->getId());

        $this->assertTrue($ormOther2 === $ormTest->getOrmOther());
        $this->assertEquals($ormOther2->getId(), $ormTest->getOrmOtherId());

        // SET BACK ONE BY MODEL
        $ormTest->setOrmOther($ormOther1);
        $this->assertTrue($ormOther1 === $ormTest->getOrmOther());
        $this->assertEquals($ormOther1->getId(), $ormTest->getOrmOtherId());

        // SET NULL BY MODEL
        $ormTest->setOrmOther(null);
        $this->assertNULL($ormTest->getOrmOther());
        $this->assertNULL($ormTest->getOrmOtherId());

        // SET BACK TWO BY MODEL
        $ormTest->setOrmOther($ormOther2);
        $this->assertTrue($ormOther2 === $ormTest->getOrmOther());
        $this->assertEquals($ormOther2->getId(), $ormTest->getOrmOtherId());
    }

    public function testIDSetNewEntityThenSaveIt()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->createEntity();

        $ormOther = $ormOtherMgr->createEntity();
        $ormOther->setFldInt(12);
        $ormOther->setTitle('hali');

        $ormTest->setOrmOther($ormOther);

        $ormOtherMgr->save($ormOther);

        $this->assertEquals($ormOther->getId(), $ormTest->getOrmOther()->getId());
        $this->assertEquals($ormOther->getId(), $ormTest->getOrmOtherId());
    }

    public function testIDSetNewEntityThenSaveIt2()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->createEntity();

        $ormOther = $ormOtherMgr->createEntity();
        $ormOther->setFldInt(12);
        $ormOther->setTitle('hali');

        $ormTest->setOrmOther($ormOther);

        $ormTestMgr->save($ormTest);

        $this->assertNotEmpty($ormOther->getId());
        $this->assertNotEmpty($ormTest->getId());
        $this->assertEquals($ormOther->getId(), $ormTest->getOrmOther()->getId());
        $this->assertEquals($ormOther->getId(), $ormTest->getOrmOtherId());
    }

    public function testIDRelatedDeleted()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormOther = $ormOtherMgr->findById(1);

        $ormTest->setOrmOther($ormOther);

        $ormOtherMgr->delete($ormOther);

        $this->assertNull($ormTest->getOrmOtherId());
    }

    public function testIDRelatedDeleted2()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormOtherMgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormOther = $ormOtherMgr->findById(1);

        $ormTest->setOrmOther($ormOther);

        $ormTestMgr->delete($ormTest);

        $this->assertTrue($ormTest->_isDeleted());
        $this->assertFalse($ormOther->_isDeleted());
    }

    public function testFIELDNewTSetNullO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormTest->setOrmThird(null);

        $this->assertNull($ormTest->getOrmThird());
        $this->assertNull($ormTest->getOrmThirdKey());
    }

    public function testFIELDNewTSetNewO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormThirdMgr = self::$dbHelper->getManager(OrmThird\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormThird = $ormThirdMgr->createEntity();
        $ormTest->setOrmThird($ormThird);

        $this->assertTrue($ormThird === $ormTest->getOrmThird());
        $this->assertNull($ormTest->getOrmThirdKey());
    }

    public function testFIELDNewTSetExistingO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormThirdMgr = self::$dbHelper->getManager(OrmThird\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormThird = $ormThirdMgr->findById(1);
        // just make sure it was in the db
        $this->assertEquals(1, $ormThird->getId());

        $ormTest->setOrmThird($ormThird);

        $this->assertTrue($ormThird === $ormTest->getOrmThird());
        $this->assertEquals($ormThird->getFkToThis(), $ormTest->getOrmThirdKey());
    }

    public function testFIELDExistingEmptyTSetNewO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormThirdMgr = self::$dbHelper->getManager(OrmThird\Manager::class);

        $ormTest = $ormTestMgr->findById(1);
        // just make sure it was in the db and has no attached ormThird
        $this->assertEquals(1, $ormTest->getId());
        $this->assertNull($ormTest->getOrmThirdKey());
        $this->assertNull($ormTest->getOrmThird());

        $ormThird = $ormThirdMgr->createEntity();
        $ormTest->setOrmThird($ormThird);

        $this->assertTrue($ormThird === $ormTest->getOrmThird());
        $this->assertNull($ormTest->getOrmThirdKey());
    }

    public function testFIELDExistingEmptyTExistingNewO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormThirdMgr = self::$dbHelper->getManager(OrmThird\Manager::class);

        $ormTest = $ormTestMgr->findById(1);
        // just make sure it was in the db and has no attached ormThird
        $this->assertEquals(1, $ormTest->getId());
        $this->assertNull($ormTest->getOrmThirdKey());
        $this->assertNull($ormTest->getOrmThird());

        $ormThird = $ormThirdMgr->findById(1);
        // just make sure it was in the db
        $this->assertEquals(1, $ormThird->getId());

        $ormTest->setOrmThird($ormThird);

        $this->assertTrue($ormThird === $ormTest->getOrmThird());
        $this->assertEquals($ormThird->getFkToThis(), $ormTest->getOrmThirdKey());
    }

    public function testFIELDExistingNotEmptyTSetNewO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormThirdMgr = self::$dbHelper->getManager(OrmThird\Manager::class);

        $ormTest = $ormTestMgr->findById(2);
        // just make sure it was in the db and has attached ormThird
        $this->assertEquals(2, $ormTest->getId());
        $this->assertEquals('third hali', $ormTest->getOrmThirdKey());
        $this->assertNotNull($ormTest->getOrmThird());

        $ormThird = $ormThirdMgr->createEntity();
        $ormTest->setOrmThird($ormThird);

        $this->assertTrue($ormThird === $ormTest->getOrmThird());
        $this->assertNull($ormTest->getOrmThirdKey());
    }

    public function testFIELDExistingNotEmptyTSetExistingO()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormThirdMgr = self::$dbHelper->getManager(OrmThird\Manager::class);

        $ormTest = $ormTestMgr->findById(2);

        // just make sure it was in the db and has attached ormThird
        $this->assertEquals(2, $ormTest->getId());
        $this->assertEquals('third hali', $ormTest->getOrmThirdKey());
        $this->assertNotNull($ormTest->getOrmThird());

        $ormThird = $ormThirdMgr->findById(1);
        // just make sure it was in the db
        $this->assertEquals(1, $ormThird->getId());

        $ormTest->setOrmThird($ormThird);

        $this->assertTrue($ormThird === $ormTest->getOrmThird());
        $this->assertEquals($ormThird->getFkToThis(), $ormTest->getOrmThirdKey());
    }

    public function testFIELDNewTSetNullOid()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormTest->setOrmThirdKey(null);

        $this->assertNull($ormTest->getOrmThird());
        $this->assertNull($ormTest->getOrmThirdKey());
    }

    public function testFIELDNewTSetExistingOid()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormThirdMgr = self::$dbHelper->getManager(OrmThird\Manager::class);

        $ormTest = $ormTestMgr->createEntity();
        $ormThird = $ormThirdMgr->findById(1);
        // just make sure it was in the db
        $this->assertEquals(1, $ormThird->getId());

        $ormTest->setOrmThirdKey($ormThird->getFkToThis());

        $this->assertTrue($ormThird === $ormTest->getOrmThird());
        $this->assertEquals($ormThird->getFkToThis(), $ormTest->getOrmThirdKey());
    }

    public function testFIELDHas()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormThirdMgr = self::$dbHelper->getManager(OrmThird\Manager::class);

        $ormTest = $ormTestMgr->createEntity();

        $ormThird = $ormThirdMgr->findById(1);
        // just make sure it was in the db
        $this->assertEquals(1, $ormThird->getId());

        // doesn't have at first
        $this->assertFalse( $ormTest->hasOrmThird() );

        // now set it,
        $ormTest->setOrmThirdKey($ormThird->getFkToThis());

        // now it has it
        $this->assertTrue( $ormTest->hasOrmThird() );
    }

    public function testFIELDManipulate()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormThirdMgr = self::$dbHelper->getManager(OrmThird\Manager::class);

        $ormTest = $ormTestMgr->createEntity();

        // SET ONE
        $ormThird1 = $ormThirdMgr->findById(1);
        // just make sure it was in the db
        $this->assertEquals(1, $ormThird1->getId());

        $ormTest->setOrmThirdKey($ormThird1->getFkToThis());

        $this->assertTrue($ormThird1 === $ormTest->getOrmThird());
        $this->assertEquals($ormThird1->getFkToThis(), $ormTest->getOrmThirdKey());

        // SET ANOTHER
        $ormThird2 = $ormThirdMgr->findById(2);
        // just make sure it was in the db
        $this->assertEquals(2, $ormThird2->getId());

        $ormTest->setOrmThirdKey($ormThird2->getFkToThis());

        $this->assertTrue($ormThird2 === $ormTest->getOrmThird());
        $this->assertEquals($ormThird2->getFkToThis(), $ormTest->getOrmThirdKey());

        // SET BACK ONE BY MODEL
        $ormTest->setOrmThird($ormThird1);
        $this->assertTrue($ormThird1 === $ormTest->getOrmThird());
        $this->assertEquals($ormThird1->getFkToThis(), $ormTest->getOrmThirdKey());

        // SET NULL BY MODEL
        $ormTest->setOrmThird(null);
        $this->assertNULL($ormTest->getOrmThird());
        $this->assertNULL($ormTest->getOrmThirdKey());

        // SET BACK TWO BY MODEL
        $ormTest->setOrmThird($ormThird2);
        $this->assertTrue($ormThird2 === $ormTest->getOrmThird());
        $this->assertEquals($ormThird2->getFkToThis(), $ormTest->getOrmThirdKey());
    }

    public function testFIELDSetNewEntityThenSaveIt()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormThirdMgr = self::$dbHelper->getManager(OrmThird\Manager::class);

        $ormTest = $ormTestMgr->createEntity();

        $ormThird = $ormThirdMgr->createEntity();
        $ormThird->setFkToThis('third new');

        $ormTest->setOrmThird($ormThird);

        $ormThirdMgr->save($ormThird);

        $this->assertEquals($ormThird->getId(), $ormTest->getOrmThird()->getId());
        $this->assertEquals($ormThird->getFkToThis(), $ormTest->getOrmThirdKey());
    }

    public function testFIELDSetNewEntityThenSaveIt2()
    {
        $ormTestMgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $ormThirdMgr = self::$dbHelper->getManager(OrmThird\Manager::class);

        $ormTest = $ormTestMgr->createEntity();

        $ormThird = $ormThirdMgr->createEntity();
        $ormThird->setFkToThis('third new again');

        $ormTest->setOrmThird($ormThird);

        $ormTestMgr->save($ormTest);

        $this->assertNotEmpty($ormThird->getFkToThis());
        $this->assertNotEmpty($ormTest->getId());
        $this->assertEquals($ormThird->getId(), $ormTest->getOrmThird()->getId());
        $this->assertEquals($ormThird->getFkToThis(), $ormTest->getOrmThirdKey());
    }
}