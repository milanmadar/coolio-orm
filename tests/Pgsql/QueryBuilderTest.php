<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use Milanmadar\CoolioORM\ORMException;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;

class QueryBuilderTest extends TestCase
{
    private static DbHelper $dbHelper;
    private static int $oRowCnt;

    // This method runs once when the test class is loaded
    public static function setUpBeforeClass(): void
    {
        $conn1 = ORM::instance()->getDoctrineConnectionByUrl($_ENV['DB_POSTGRES_DB1']);
        self::$dbHelper = new DbHelper( $conn1 );
    }

    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
        self::$dbHelper->resetTo('Pgsql/fixtures/fix.sql');
        if(!isset(self::$oRowCnt)) {
            self::$oRowCnt = self::$dbHelper->countRows('orm_test');
        }
    }

    public function testValueOneEntity()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent = $mgr->createQueryBuilder()
            ->where('id=1')
            ->fetchOneEntity();
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent);
        $this->assertEquals(1, $ent->getId());

        $ent = $mgr->createQueryBuilder()
            ->where('id=11111')
            ->fetchOneEntity();
        $this->assertNull($ent);
    }

    public function testValueManyEntity()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->where('id>1')
            ->fetchManyEntity();
        $this->assertIsArray($res);
        $this->assertNotEmpty($res);
        $this->assertGreaterThan(1, count($res));
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $res[0]);
    }

    public function testQuestionMarkOneEntity()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent = $mgr->createQueryBuilder()
            ->where('id=?')->setParameter(0, 1)
            ->fetchOneEntity();
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent);
        $this->assertEquals(1, $ent->getId());

        $ent = $mgr->createQueryBuilder()
            ->where('id=?')->setParameter(0, 11111)
            ->fetchOneEntity();
        $this->assertNull($ent);
    }

    public function testQuestionMarks2OneEntity()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent = $mgr->createQueryBuilder()
            ->where('id=?')->setParameter(0, 1)
            ->andWhere('fld_medium_int=?')->setParameter(1, 4)
            ->fetchOneEntity();
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent);
        $this->assertEquals(1, $ent->getId());

        $ent = $mgr->createQueryBuilder()
            ->where('id=?')->setParameter(0, 1)
            ->andWhere('fld_medium_int=?')->setParameter(1, 11111)
            ->fetchOneEntity();
        $this->assertNull($ent);
    }

    public function testQuestionMarks2OneEntitySetAllParams()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent = $mgr->createQueryBuilder()
            ->where('id=?')
            ->andWhere('fld_medium_int=?')
            ->setParameters([1, 4])
            ->fetchOneEntity();
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent);
        $this->assertEquals(1, $ent->getId());

        $ent = $mgr->createQueryBuilder()
            ->where('id=?')
            ->andWhere('fld_medium_int=?')
            ->setParameters([1, 11111])
            ->fetchOneEntity();
        $this->assertNull($ent);
    }

    public function testQuestionMarkManyEntity()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->where('id>?')->setParameter(0, 1)
            ->fetchManyEntity();
        $this->assertIsArray($res);
        $this->assertNotEmpty($res);
        $this->assertGreaterThan(1, count($res));
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $res[0]);
    }

    public function testNamedParamOneEntity()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent = $mgr->createQueryBuilder()
            ->where('id=:ID')->setParameter('ID', 1)
            ->fetchOneEntity();
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent);
        $this->assertEquals(1, $ent->getId());

        $ent = $mgr->createQueryBuilder()
            ->where('id=:ID')->setParameter('ID', 11111)
            ->fetchOneEntity();
        $this->assertNull($ent);
    }

    public function testNamedParams2OneEntity()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent = $mgr->createQueryBuilder()
            ->where('id=:ID')->setParameter('ID', 1)
            ->andWhere('fld_medium_int=:NUM')->setParameter('NUM', 4)
            ->fetchOneEntity();
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent);
        $this->assertEquals(1, $ent->getId());

        $ent = $mgr->createQueryBuilder()
            ->where('id=:ID')->setParameter('ID', 11111)
            ->andWhere('fld_medium_int=:NUM')->setParameter('NUM', 11111)
            ->fetchOneEntity();
        $this->assertNull($ent);
    }

    public function testNamedParams2OneEntitySetAllParams()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent = $mgr->createQueryBuilder()
            ->where('id=:ID')
            ->andWhere('fld_medium_int=:NUM')
            ->setParameters(['ID'=>1, 'NUM'=>4])
            ->fetchOneEntity();
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent);
        $this->assertEquals(1, $ent->getId());

        $ent = $mgr->createQueryBuilder()
            ->where('id=:ID')
            ->andWhere('fld_medium_int=:NUM')
            ->setParameters(['ID'=>1, 'NUM'=>11111])
            ->fetchOneEntity();
        $this->assertNull($ent);
    }

    public function testNamedParamManyEntity()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->where('id>:ID')->setParameter('ID', 1)
            ->fetchManyEntity();
        $this->assertIsArray($res);
        $this->assertNotEmpty($res);
        $this->assertGreaterThan(1, count($res));
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $res[0]);
    }

    public function testNamedAndQuestionMarkParamOneEntity()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        try {
            $mgr->createQueryBuilder()
                ->where('id=:ID1')->setParameter('ID1', 1)
                ->orWhere('fld_medium_int=?')->setParameter(0, 4)
                ->fetchOneEntity();

            $this->fail('Expected ORMException');
        }
        catch (ORMException) {
            $this->assertEquals(1, 2-1);
        }
    }

    public function testDoctrineExecuteQuery()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->where('id=?')->setParameter(0, 1)
            ->executeQuery();

        $this->assertTrue($res instanceof \Doctrine\DBAL\Result);

        $row = $res->fetchAssociative();
        $this->assertIsArray($row);
        $this->assertNotEmpty($row);

        $row = $res->fetchAssociative();
        $this->assertTrue($row === false);
    }

    public function testDoctrineFetchAssociative()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $blr = $mgr->createQueryBuilder();

        $blr->where('id=?')->setParameter(0, 1);
        $row = $blr->fetchAssociative();
        $this->assertIsArray($row);
        $this->assertNotEmpty($row);

        $blr->where('id=?')->setParameter(0, 99999);
        $row = $blr->fetchAssociative();
        $this->assertTrue($row === false);
    }

    public function testDelete()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $mgr->createQueryBuilder()
            ->delete()
            ->where('id=:Val')->setParameter('Val', 1)
            ->executeStatement();

        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt-1, $rowCnt);
    }

    public function testDeleteLimit()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        try {
            $mgr->createQueryBuilder()
                ->delete()
                ->orderBy('id', 'desc')
                ->limit(0, 3)
                ->executeStatement();
            $this->fail('Expected ORMException');
        }
        catch (ORMException) {
            $this->assertEquals(1, 2-1);
        }
    }

    public function testDeleteLimitOrder()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        try {
            $mgr->createQueryBuilder()
                ->delete()
                ->where('id<=:Val')->setParameter('Val', 11111)
                ->limit(0, 5)
                ->orderBy('id', 'DESC')
                ->executeStatement();
            $this->fail('Expected ORMException');
        }
        catch (ORMException) {
            $this->assertEquals(1, 2-1);
        }
    }

    public function testUpdate()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $mgr->createQueryBuilder()
            ->update()
            ->set('fld_int', '?')->setParameter(0, 8642)
            ->where('id=1')
            ->executeStatement();

        $row = $mgr->createQueryBuilder()
            ->where('id=1')
            ->fetchAssociative();

        $this->assertEquals(8642, $row['fld_int']);
    }

    public function testUpdateNameparam()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $mgr->createQueryBuilder()
            ->update()
            ->set('fld_int', ':Val')->setParameter('Val', 8642)
            ->where('id = :Id')->setParameter('Id', 1)
            ->executeStatement();

        $row = $mgr->createQueryBuilder()
            ->where('id=1')
            ->fetchAssociative();

        $this->assertEquals(8642, $row['fld_int']);
    }

    public function testUpdateLimit()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        try {
            $mgr->createQueryBuilder()
                ->update()
                ->set('fld_int', 1000)
                ->orderBy('id', 'desc')
                ->limit(0, 3)
                ->executeStatement();
            $this->fail('Expected ORMException');
        }
        catch (ORMException) {
            $this->assertEquals(1, 2-1);
        }
    }

    public function testInsert()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $mgr->createQueryBuilder()
            ->insert()
            ->setValue('fld_float', '?')->setParameter(0, 3.14)
            ->setValue('fld_varchar', '?')->setParameter(1, "ok'k")
            ->executeStatement();

        $newId = $mgr->createQueryBuilder()->lastInsertId();

        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt+1, $rowCnt);

        $row = $mgr->createQueryBuilder()
            ->where('id=?')->setParameter(0, $newId)
            ->fetchAssociative();

        $this->assertEquals("ok'k", $row['fld_varchar']);
    }

    public function testOrWhere()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $mgr->createQueryBuilder()
            ->delete()
            ->where('id=1')->orWhere('id=2')
            ->executeStatement();

        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt-2, $rowCnt);
    }

    public function testAndWhere()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $mgr->createQueryBuilder()
            ->delete()
            ->where('id=1')->andWhere('id=2')
            ->executeStatement();

        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt, $rowCnt);
    }

    public function testLimitOrder()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->where('id>0')
            ->limit(1, 5)
            ->addOrderBy('id', 'desc')
            ->fetchAllAssociative();

        $this->assertEquals(5, count($res));
        $this->assertEquals(9, $res[0]['id']);
        $this->assertEquals(5, $res[4]['id']);
    }

    public function testLimitNoOffset()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->where('id>0')
            ->limit(0, 5)
            ->addOrderBy('id', 'desc')
            ->fetchAllAssociative();

        $this->assertEquals(5, count($res));
        $this->assertEquals(10, $res[0]['id']);
        $this->assertEquals(6, $res[4]['id']);
    }

    public function testPage()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->where('id>0')
            ->page(2, 3)
            ->addOrderBy('id', 'asc')
            ->fetchAllAssociative();

        $this->assertEquals(3, count($res));
        $this->assertEquals(4, $res[0]['id']);
        $this->assertEquals(6, $res[2]['id']);
    }

    public function testOrderBy()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->where('id>0')
            ->orderBy('id', 'asc')
            ->fetchAllAssociative();
        $this->assertEquals(1, $res[0]['id']);
    }

    public function testGetSQL()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $sql = $mgr->createQueryBuilder()
            ->delete()
            ->where('id=1')->andWhere('id=2 AND id=?')->setParameter(0, 4)
            ->getSQL();
        $this->assertEquals('DELETE FROM orm_test WHERE (id=1) AND (id=2 AND id=?)', $sql);

        $sql = $mgr->createQueryBuilder()
            ->delete()
            ->where('id=:Id1')->andWhere('id=:Id2 AND id=:Id3 OR something IN (:IntArray) OR other IN (:StringArray)')
            ->setParameters([
                'Id1'=>1,
                'Id2'=>2,
                'Id3'=>'stringVal',
                'IntArray'=>[1,2,3],
                'StringArray'=>['a','b','c']
            ])
            ->getSQLNamedParameters();
        $this->assertEquals("DELETE FROM orm_test WHERE (id=1) AND (id=2 AND id='stringVal' OR something IN (1,2,3) OR other IN ('a','b','c'))", $sql);
    }

}