<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;
use tests\Model\OrmOther;

class EntityRepositoryTest extends TestCase
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

    public function testRepoAddDel()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findById(1);

        $repo = $mgr->_getEntityRepository();

        $this->assertEquals(1, $repo->count());
        $this->assertEquals(1, $repo->count($mgr->getDbTable().$mgr->getDbConnUrl()));
        $this->assertEquals(0, $repo->count('non-exist'));

        $ent2 = $mgr->findById(2);

        $this->assertEquals(2, $repo->count());
        $this->assertEquals(2, $repo->count($mgr->getDbTable().$mgr->getDbConnUrl()));
        $this->assertEquals(0, $repo->count('non-exist'));

        $repo->del($ent1, $mgr->getDbTable().$mgr->getDbConnUrl());

        $this->assertEquals(1, $repo->count());
        $this->assertEquals(1, $repo->count($mgr->getDbTable().$mgr->getDbConnUrl()));
        $this->assertEquals(0, $repo->count('non-exist'));

        $repo->add($ent1, $mgr->getDbTable().$mgr->getDbConnUrl());

        $this->assertEquals(2, $repo->count());
        $this->assertEquals(2, $repo->count($mgr->getDbTable().$mgr->getDbConnUrl()));
        $this->assertEquals(0, $repo->count('non-exist'));

        $repo->clear();

        $this->assertEquals(0, $repo->count());
        $this->assertEquals(0, $repo->count($mgr->getDbTable().$mgr->getDbConnUrl()));
        $this->assertEquals(0, $repo->count('non-exist'));
    }

    public function testAddSameException()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findById(1);

        $repo = $mgr->_getEntityRepository();

        try {
            $repo->add($ent1, $mgr->getDbTable().$mgr->getDbConnUrl());
            $this->assertTrue(false);
        } catch(\LogicException $e) {
            $this->assertTrue(true);
        }
    }

    public function testNewEntity()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->createEntity();
        $ent2 = $mgr->createEntity();
        $this->assertFalse($ent1 === $ent2);
    }

    public function testFindById()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findById(1);
        $ent2 = $mgr->findById(1);
        $this->assertTrue($ent1 === $ent2);

        $ent1->setFldInt(999);
        $this->assertTrue($ent1->getFldInt() === $ent2->getFldInt());

        $ent3 = $mgr->findById(2);
        $this->assertTrue($ent3 !== $ent2);
    }

    public function testFindByIdForceDb()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findById(1);
        $ent2 = $mgr->findById(1, true);
        $this->assertFalse($ent1 === $ent2);

        $ent1->setFldInt(999);
        $this->assertFalse($ent1->getFldInt() === $ent2->getFldInt());

        $ent3 = $mgr->findById(2);
        $this->assertTrue($ent3 !== $ent2);

        $ent4 = $mgr->findById(1);
        $this->assertTrue($ent1 === $ent4);

        $ent1->setFldInt(999);
        $this->assertTrue($ent1->getFldInt() === $ent4->getFldInt());
    }

    public function testNewFindById()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->createEntity(['id'=>1]);
        $ent2 = $mgr->findById(1);
        $this->assertTrue($ent1 === $ent2);

        $ent1->setFldInt(999);
        $this->assertTrue($ent1->getFldInt() === $ent2->getFldInt());

        $ent3 = $mgr->createEntity(['id'=>2]);
        $this->assertTrue($ent3 !== $ent2);
    }

    public function testNewEntitySaveFind()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->createEntity();
        $ent1->setFldInt(999);
        $mgr->save($ent1);
        $this->assertGreaterThan(1, $ent1->getId());

        $ent2 = $mgr->findById($ent1->getId());
        $this->assertTrue($ent1 === $ent2);
        $this->assertTrue($ent1->getFldInt() === $ent2->getFldInt());

        $ent3 = $mgr->createEntity();
        $this->assertTrue($ent3 !== $ent2);

        $mgr->save($ent3);
        $this->assertGreaterThan($ent1->getId(), $ent3->getId());
        $this->assertTrue($ent3 !== $ent1);
    }

    public function testFindDifferentSelects1()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findOne("SELECT id, fld_float FROM ".'orm_test'." WHERE id=1");
        $this->assertNull($ent1->getFldInt());

        $ent1->setFldInt(999);

        $ent2 = $mgr->findOne("SELECT id, fld_int FROM ".'orm_test'." WHERE id=1");
        $this->assertTrue($ent1 === $ent2);
        $this->assertTrue($ent1->getFldInt() === $ent2->getFldInt());

        $ent3 = $mgr->findOne("SELECT * FROM ".'orm_test'." WHERE id=1");
        $this->assertTrue($ent1 === $ent3);
        $this->assertTrue($ent1->getFldInt() === $ent3->getFldInt());
    }

    public function testFindDifferentSelects2()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findOne("SELECT id, fld_float FROM ".'orm_test'." WHERE id=1");
        $this->assertNull($ent1->getFldInt());

        $ent2 = $mgr->findOne("SELECT id, fld_int FROM ".'orm_test'." WHERE id=1");
        $this->assertNotNull($ent1->getFldInt());
        $this->assertTrue($ent1 === $ent2);
        $this->assertTrue($ent1->getFldInt() === $ent2->getFldInt());

        $ent3 = $mgr->findOne("SELECT * FROM ".'orm_test'." WHERE id=1");
        $this->assertTrue($ent1 === $ent3);
        $this->assertTrue($ent1->getFldInt() === $ent3->getFldInt());
    }

    public function testSameObjFromResultSet()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $good = false;

        $ent3 = $mgr->findOneWhere("id=3");
        $entArr = $mgr->findManyWhere("1 = 1");
        foreach($entArr as $ent) {
            if($ent->getId() == 3) {
                $this->assertTrue($ent === $ent3);
                $good = true;
                break;
            }
        }

        if(!$good) {
            $this->assertTrue(false);
        }
    }

    public function testIsSingletonRepo()
    {
        $orm = \Milanmadar\CoolioORM\ORM::instance();

        $r1 = $orm->entityManager(OrmTest\Manager::class)->_getEntityRepository();
        $r2 = $orm->entityManager(OrmOther\Manager::class)->_getEntityRepository();
        $this->assertTrue($r1 === $r2);
    }

    public function testRepoFull()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $repo = $mgr->_getEntityRepository();
        $repo->clear();
        $origiMax = $repo->getMaxEntityCount();

        // make sure it has some items, but not full
        $repo->setMaxEntityCount(100000000);
        $allEnts = $mgr->findManyWhere("1=1 ORDER BY id ASC LIMIT 4");

        $cnt = $repo->count();
        $this->assertTrue( $cnt > 3 );
        $this->assertEquals(count($allEnts), $cnt);

        $ent1 = $allEnts[0];

        // resized repo but we are not gonna add new items, so it should remain intact
        $repo->setMaxEntityCount(3);
        $allEnts = $mgr->findManyWhere("1=1 ORDER BY id ASC LIMIT 4");
        $this->assertEquals(4, $repo->count());
        $this->assertTrue($ent1 === $allEnts[0]);

        $ent = $mgr->findById( $ent1->getId() );
        $this->assertTrue($ent === $ent1);
        $this->assertEquals(4, $repo->count());

        // now we will add new ones (5), but repo max is 3, so it should get full with 3, then empty and add 2
        $mgr->findManyWhere("1=1 ORDER BY id DESC LIMIT 5");
        $this->assertEquals(2, $repo->count());

        // set back to what it was
        $repo->setMaxEntityCount( $origiMax );
    }

    public function testChangeId(): void
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $mgr->clearRepository(true);

        $ent = $mgr->findById(1);
        $ent->setId(9999);
        $mgr->save($ent);

        $ent2 = $mgr->findById(1);
        $this->assertNull($ent2);

        $ent3 = $mgr->findById(9999);
        $this->assertNotNull($ent3);
        $this->assertTrue($ent3 === $ent);
    }
}