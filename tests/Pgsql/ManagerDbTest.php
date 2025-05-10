<?php

namespace Pgsql;

use Doctrine\DBAL\Tools\DsnParser;
use Milanmadar\CoolioORM\ORM;
use Milanmadar\CoolioORM\Event\EntityEventTypeEnum;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;
use tests\Model\OrmOther;
use tests\EntityEventSubscriber;

class ManagerDbTest extends TestCase
{
    private static DbHelper $dbHelper;
    private static DbHelper $dbHelperChange; // test changing databases
    private static int $oRowCnt;

    // This method runs once when the test class is loaded
    public static function setUpBeforeClass(): void
    {
        $conn1 = ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB1']);
        self::$dbHelper = new DbHelper($conn1);

        $conn2 = ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB2']);
        self::$dbHelperChange = new DbHelper($conn2);
    }

    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
        self::$dbHelper->resetTo('Pgsql/fixtures/fix.sql');
        self::$dbHelperChange->resetTo('Pgsql/fixtures/fix.sql');
        if (!isset(self::$oRowCnt)) {
            self::$oRowCnt = self::$dbHelper->countRows('orm_test');
        }
    }

    public function testFindById()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findById(1);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);

        $ent2 = $mgr->findById(99999);
        $this->assertNull($ent2);
    }

    public function testFindOneWhere()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findOneWhere('id=?', [1]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);

        $ent2 = $mgr->findOneWhere('id=:id', ['id' => 2]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);

        $ent3 = $mgr->findOneWhere('id=3');
        $this->assertNotNull($ent3);

        $ent4 = $mgr->findOneWhere('id=?', [99999]);
        $this->assertNull($ent4);

        $ent5 = $mgr->findOneWhere('id=:id', ['id' => 99999]);
        $this->assertNull($ent5);

        $ent6 = $mgr->findOneWhere('id=99999');
        $this->assertNull($ent6);
    }

    public function testFindOne()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findOne("SELECT * FROM orm_test WHERE id=?", [1]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);

        $ent2 = $mgr->findOne("SELECT * FROM orm_test WHERE id=:id", ['id' => 2]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent2);

        $ent3 = $mgr->findOne("SELECT * FROM orm_test WHERE id=3");
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent3);

        $ent4 = $mgr->findOne("SELECT * FROM orm_test WHERE id=?", [99999]);
        $this->assertNull($ent4);

        $ent5 = $mgr->findOne("SELECT * FROM orm_test WHERE id=:id", ['id' => 99999]);
        $this->assertNull($ent5);

        $ent6 = $mgr->findOne("SELECT * FROM orm_test WHERE id=99999");
        $this->assertNull($ent6);
    }

    public function testFindManyWhere()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res1 = $mgr->findManyWhere('id>? LIMIT 100', [1]);
        $this->assertIsArray($res1);
        $this->assertGreaterThan(1, count($res1));
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $res1[0]);

        $res2 = $mgr->findManyWhere('id>:id', ['id' => 2]);
        $this->assertIsArray($res2);
        $this->assertGreaterThan(1, count($res2));
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $res2[0]);

        $res3 = $mgr->findManyWhere('id>3');
        $this->assertIsArray($res3);
        $this->assertGreaterThan(1, count($res3));
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $res3[0]);

        $res4 = $mgr->findManyWhere('id>?', [99999]);
        $this->assertIsArray($res4);
        $this->assertEquals(0, count($res4));

        $res5 = $mgr->findManyWhere('id>:id', ['id' => 99999]);
        $this->assertIsArray($res5);
        $this->assertEquals(0, count($res5));

        $res6 = $mgr->findManyWhere('id>99999');
        $this->assertIsArray($res6);
        $this->assertEquals(0, count($res6));
    }

    public function testFindMany()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res1 = $mgr->findMany("SELECT * FROM orm_test WHERE id>?", [1]);
        $this->assertIsArray($res1);
        $this->assertGreaterThan(1, count($res1));

        $res2 = $mgr->findMany("SELECT * FROM orm_test WHERE id>:id", ['id' => 2]);
        $this->assertIsArray($res2);
        $this->assertGreaterThan(1, count($res2));

        $res3 = $mgr->findMany("SELECT * FROM orm_test WHERE id>3");
        $this->assertIsArray($res3);
        $this->assertGreaterThan(1, count($res3));

        $res4 = $mgr->findMany("SELECT * FROM orm_test WHERE id>?", [99999]);
        $this->assertIsArray($res4);
        $this->assertEquals(0, count($res4));

        $res5 = $mgr->findMany("SELECT * FROM orm_test WHERE id>:id", ['id' => 99999]);
        $this->assertIsArray($res5);
        $this->assertEquals(0, count($res5));

        $res6 = $mgr->findMany("SELECT * FROM orm_test WHERE id>99999");
        $this->assertIsArray($res6);
        $this->assertEquals(0, count($res6));
    }

    public function testEntityInsert()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent = $mgr->createEntity();
        $sub = new EntityEventSubscriber();
        $sub->subToDataChanges($ent);
        $goodHist = [];

        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt, $rowCnt);
        $mgr->save($ent);
        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt + 1, $rowCnt);

        // Id got set?
        $this->assertEquals(self::$oRowCnt + 1, $ent->getId());

        // Did it trigger DataChange and IdChange?
        $goodHist[] = [EntityEventTypeEnum::DATA_CHANGED, 'id', self::$oRowCnt + 1, null];
        $goodHist[] = [EntityEventTypeEnum::ID_CHANGED, '_', self::$oRowCnt + 1, null];
        $this->assertEquals(count($goodHist), count($sub->hist));
        $this->assertEquals($goodHist, $sub->hist);
    }

    public function testEntityUpdate()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent = $mgr->createEntity();
        $sub = new EntityEventSubscriber();
        $sub->subToDataChanges($ent);
        $goodHist = [];

        // insert (already tested above)
        $mgr->save($ent);
        // events
        $goodHist[] = [EntityEventTypeEnum::DATA_CHANGED, 'id', self::$oRowCnt + 1, null];
        $goodHist[] = [EntityEventTypeEnum::ID_CHANGED, '_', self::$oRowCnt + 1, null];

        // save without change does nothing
        $mgr->save($ent);
        // php data
        $this->assertEquals(self::$oRowCnt + 1, $ent->getId());
        // db
        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt + 1, $rowCnt);
        // events
        $this->assertEquals($goodHist, $sub->hist);

        // saving with a change triggers event, but not new db row
        $ent->setFldInt(321);
        // save
        $mgr->save($ent);
        // php data
        $this->assertEquals(self::$oRowCnt + 1, $ent->getId());
        // db
        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt + 1, $rowCnt);
        // events
        $goodHist[] = [EntityEventTypeEnum::DATA_CHANGED, 'fld_int', 321, null];
        $this->assertEquals($goodHist, $sub->hist);
    }

    public function testDeleteThenGetAccess()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findById(1);
        $mgr->delete($ent1);

        $ent1->getFldInt();
        $this->assertTrue(true);
    }

    public function testDeleteThenSetAccess()
    {
        $this->expectException(\LogicException::class);

        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findById(1);
        $mgr->delete($ent1);

        $ent1->setFldInt(123456);
    }

    public function testDelete()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findById(1);
        $this->assertNotNull($ent1);
        $this->assertFalse($ent1->_isDeleted());

        $mgr->delete($ent1);
        $this->assertNotNull($ent1);
        $this->assertTrue($ent1->_isDeleted());

        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt - 1, $rowCnt);

        $ent2 = $mgr->findById(1);
        $this->assertNotNull($ent1);
        $this->assertNull($ent2);

        $ent3 = $mgr->findById(2);
        $this->assertNotNull($ent3);
        $this->assertNotNull($ent1);
        $this->assertNull($ent2);
    }

    public function testForceInsertOnNextSave()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->createEntity(['id' => 98765]);
        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt, $rowCnt);

        $mgr->save($ent1);
        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt, $rowCnt);

        $ent1->_setForceInsertOnNextSave(true);
        $mgr->save($ent1);
        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt + 1, $rowCnt);
    }

    public function testForceInsertOnNextSave2()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findById(1);
        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt, $rowCnt);

        $mgr->save($ent1);
        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt, $rowCnt);

        $mgr->delete($ent1);
        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt - 1, $rowCnt);

        // re-create
        $ent2 = $mgr->createEntity(['id' => 1]);
        $ent2->_setForceInsertOnNextSave(true);
        $mgr->save($ent2);
        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt, $rowCnt);
    }

    public function testDeleteCreate()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findById(1);
        $this->assertNotNull($ent1);
        $this->assertFalse($ent1->_isDeleted());

        $mgr->delete($ent1);
        $this->assertNotNull($ent1);
        $this->assertTrue($ent1->_isDeleted());

        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt - 1, $rowCnt);

        // it was deleted, so $ent2 is null
        $ent2 = $mgr->findById(1);
        $this->assertNull($ent2);

        // re-create it (via a new variable)
        $ent3 = $mgr->createEntity(['id' => 1]);
        $this->assertNotNull($ent3);
        $mgr->save($ent3);

        // after re-saving, $ent2 exists when DB-fetching again
        $ent2 = $mgr->findById(1);
        $this->assertNotNull($ent2);

        // non of them are _deleted()
        $this->assertTrue($ent2 === $ent3);
        $this->assertFalse($ent2->_isDeleted());
        $this->assertFalse($ent3->_isDeleted());

        // MAYBE the following should happen too,
        //$this->assertTrue($ent1 === $ent3); // IMPOSSIBLE
        //$this->assertFalse($ent1->_isDeleted()); // COULD BE POSSIBLE
    }

    public function testSelectedEntityNoDefaults1()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $sql = "SELECT id, fld_int FROM orm_test WHERE id =1";
        $ent = $mgr->findOne($sql);
        $entData = $ent->_getData();
        $expData = ['id' => 1, 'fld_int' => 1];
        $this->assertEquals($expData, $entData);
    }

//    public function testNonOfTheDataIsForThisEntity()
//    {
//        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);
//
//        try {
//            $sql = "SELECT id as ttt FROM orm_test LIMIT 1";
//            $ent = $mgr->findOne($sql);
//            $this->assertTrue(false);
//        } catch (\LogicException $e) {
//            $this->assertTrue(true);
//        }
//    }

//    public function testOnlyAddEntityData()
//    {
//        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);
//
//        $sql = "SELECT id, fld_int, unix_timestamp() FROM orm_test WHERE id = 1";
//        $ent = $mgr->findOne($sql);
//        $this->assertEquals(['id'=>1,'fld_int'=>1], $ent->_getData());
//    }

    public function testSelectPartialThenFullThenPartial()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        // select id only
        $sql = "SELECT id FROM orm_test WHERE id = 1";
        $ent1 = $mgr->findOne($sql);
        $this->assertEquals(['id' => 1], $ent1->_getData());

        // now select all fields
        $sql = "SELECT * FROM orm_test WHERE id = 1";
        $ent2 = $mgr->findOne($sql);
        $this->assertGreaterThan(2, count($ent2->_getData()));

        $this->assertTrue($ent1 === $ent2);

        // now only the id again
        $sql = "SELECT id FROM orm_test WHERE id = 1";
        $ent3 = $mgr->findOne($sql);
        $this->assertGreaterThan(2, count($ent3->_getData()));

        $this->assertTrue($ent2 === $ent3);
    }

    public function testChangeDb()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        // the original db has rows now
        $rowCnt = $mgr->createQueryBuilder()
            ->select('count(*)')
            ->fetchOne();
        $this->assertGreaterThan(0, $rowCnt);

        // empty the original db table
        $mgr->getDb()->executeQuery('truncate orm_test');

        // the original db has no rows now
        $rowCnt = $mgr->createQueryBuilder()
            ->select('count(*)')
            ->fetchOne();
        $this->assertEquals(0, $rowCnt);

        // create a new db and set it to the Manager
        $connectionParams = (new DsnParser())->parse($_ENV['DB_POSTGRES_DB2']);
        $newDb = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
        $mgr->setDb($newDb);

        // the new db still has rows
        $rowCnt = $mgr->createQueryBuilder()
            ->fetchOne("select count(*) from orm_test");
        $this->assertGreaterThan(0, $rowCnt);
    }

    public function testTwoMgrsForSameEntityWithDiffDbs()
    {
        $orm = \Milanmadar\CoolioORM\ORM::instance();

        /** @var OrmTest\Manager $mgr1 */
        $mgr1 = $orm->entityManager(OrmTest\Manager::class, $orm->getDbByUrl($_ENV['DB_POSTGRES_DB1']));
        /** @var OrmTest\Manager $mgr2 */
        $mgr2 = $orm->entityManager(OrmTest\Manager::class, $orm->getDbByUrl($_ENV['DB_POSTGRES_DB2']));

        // select them
        $ent1_1 = $mgr1->findOneWhere("id=?", [1]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1_1);

        $ent2_1 = $mgr2->findOne("SELECT * FROM orm_test WHERE id=?", [1]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent2_1);

        $this->assertFalse($ent1_1 === $ent2_1);

        // delete one of them
        $mgr1->delete($ent1_1);

        // again select them
        $ent1_2 = $mgr1->findOne("SELECT * FROM orm_test WHERE id=?", [1]);
        $this->assertNull($ent1_2);

        $ent2_2 = $mgr2->findOne("SELECT * FROM orm_test WHERE id=?", [1]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent2_2);

        // delete others
        $mgr1->createQueryBuilder()->delete()->where('id=2')->executeStatement();

        // again select those others
        $ent1_3 = $mgr1->findOne("SELECT * FROM orm_test WHERE id=?", [2]);
        $this->assertNull($ent1_3);

        $ent2_3 = $mgr2->findOne("SELECT * FROM orm_test WHERE id=?", [2]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent2_3);

        // save one to the other
        $mgr1->save($ent2_3->_setForceInsertOnNextSave(true));

        // again select those (without clearing repos)
        $ent1_4 = $mgr1->findOne("SELECT * FROM orm_test WHERE id=?", [2]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1_4);

        $ent2_4 = $mgr2->findOne("SELECT * FROM orm_test WHERE id=?", [2]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent2_4);

        // again select those (with clear repos)
        $mgr1->clearRepository(true);

        $ent1_5 = $mgr1->findOne("SELECT * FROM orm_test WHERE id=?", [2]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1_5);

        $ent2_5 = $mgr2->findOne("SELECT * FROM orm_test WHERE id=?", [2]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent2_5);
    }

    public function testEntitySerialization()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);
        $mgr2 = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ent1 = $mgr->findById(10);
        // just make sure it was in the db and has attached ormOther
        $this->assertEquals(10, $ent1->getId());
        $this->assertEquals(1, $ent1->getOrmOtherId());
        $this->assertNotNull($ent1->getOrmOther());
        $otherId = $ent1->getOrmOther()->getId();

        $ent1Serial = serialize($ent1);

        $ent2 = unserialize($ent1Serial);
        $this->assertEquals($otherId, $ent2->getOrmOther()->getId());
    }

    public function testUpdateToNull()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $mgr->clearRepository(false);
        $ent1 = $mgr->findById(10);
        $this->assertNotNull($ent1->getFldChar());
        $this->assertNotNull($ent1->getFldFloat());
        $this->assertNotNull($ent1->getFldVarchar());

        $ent1->setFldChar(null);
        $ent1->setFldFloat(null);
        $mgr->save($ent1);

        $mgr->clearRepository(false);
        $ent2 = $mgr->findById(10);
        $this->assertFalse($ent1 === $ent2);
        $this->assertNull($ent2->getFldChar());
        $this->assertNull($ent2->getFldFloat());
        $this->assertNotNull($ent2->getFldVarchar());
    }

    public function testInsertNull()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->createEntity()
            ->setFldChar(null)
            ->setFldFloat(null);
        $mgr->save($ent1);

        $mgr->clearRepository(false);
        $ent2 = $mgr->findById($ent1->getId());
        $this->assertFalse($ent1 === $ent2);
        $this->assertNull($ent2->getFldChar());
        $this->assertNull($ent2->getFldFloat());
        $this->assertNotNull($ent2->getFldVarchar());
    }

    public function testInsertBadQuery()
    {
        $this->expectException(\Milanmadar\CoolioORM\ORMException::class);

        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $mgr->createQueryBuilder()
            ->insert()
            ->setValue('no_such_field', ':A')->setParameter('A', 123)
            ->executeStatement();
    }

    public function testUpdateBadQuery()
    {
        $this->expectException(\Milanmadar\CoolioORM\ORMException::class);

        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $mgr->createQueryBuilder()
            ->update()
            ->set('no_such_field', ':A')->setParameter('A', 123)
            ->executeStatement();
    }

    public function testOrmSave()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->findById(1);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);

        $ent1->setFldInt(3254);
        ORM::instance()->save($ent1);
        $mgr->clearRepository(true);

        $ent1 = $mgr->findById(1);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);
        $this->assertEquals(3254, $ent1->getFldInt());
    }
}