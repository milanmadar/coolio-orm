<?php

namespace Mysql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;

class SelectInArrayTest extends TestCase
{
    private static DbHelper $dbHelper;

    // This method runs once when the test class is loaded
    public static function setUpBeforeClass(): void
    {
        $conn = ORM::instance()->getDoctrineConnectionByUrl($_ENV['DB_MYSQL_DB1']);
        self::$dbHelper = new DbHelper( $conn );
    }

    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
        self::$dbHelper->resetTo('Mysql/fixtures/fix.sql');
    }

    public function testInArrayFromDbInt()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->getDb()
            ->executeQuery(
                'select * from orm_test where id IN (?)',
                array(array(1, 2, 3, 4, 5, 6)),
                array(\Doctrine\DBAL\ArrayParameterType::INTEGER)
            );
        $this->assertInstanceOf('\Doctrine\DBAL\Result', $res);
        $this->assertEquals(6, $res->rowCount());
    }

    public function testInArrayFromDbString()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->getDb()
            ->executeQuery(
                'select * from ' . 'orm_test' . ' where fld_varchar IN (?)',
                array(array('a','b','a varchar 8','c')),
                array(\Doctrine\DBAL\ArrayParameterType::STRING)
            );
        $this->assertInstanceOf('\Doctrine\DBAL\Result', $res);
        $this->assertEquals(1, $res->rowCount());
    }

    public function testInArrayFromQueryBuilderInt()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        // Questionmark parameters can't have arrays. Use named parameters
//        $res = $mgr->createQueryBuilder()
//            ->where('id IN (?)')->setParameter(0, array(1, 2, 3, 4, 5, 6))
//            ->fetchAllAssociative();
//        $this->assertEquals(6, count($res));
//
//        $res = $mgr->createQueryBuilder()
//            ->where('id IN (?)')->setParameter(0, array(9991, 9992))
//            ->fetchAllAssociative();
//        $this->assertEquals(0, count($res));

        $res = $mgr->createQueryBuilder()
            ->where('id IN (:Val)')->setParameter('Val', array(1, 2, 3, 4, 5, 6))
            ->fetchAllAssociative();
        $this->assertEquals(6, count($res));

        $res = $mgr->createQueryBuilder()
            ->where('id IN (:Val)')->setParameter('Val', array(9991, 9992))
            ->fetchAllAssociative();
        $this->assertEquals(0, count($res));

        $res = $mgr->createQueryBuilder()
            ->where('id IN (:Val)')->setParameters([
                'Val' => array(1, 2, 3, 4, 5, 6)
            ])
            ->fetchAllAssociative();

        $res = $mgr->createQueryBuilder()
            ->where('id IN (:Val)')->setParameters([
                'Val' => array(1, 2, 3, 4, 5, 6)
            ])
            ->fetchAssociative();
        $this->assertIsNotBool($res);
        $this->assertNotEmpty($res);
    }

    public function testWherecolumnInArrayFromQueryBuilderInt()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        // Questionmark parameters can't have arrays. Use named parameters
//        $res = $mgr->createQueryBuilder()
//            ->where('id IN (?)')->setParameter(0, array(1, 2, 3, 4, 5, 6))
//            ->fetchAllAssociative();
//        $this->assertEquals(6, count($res));
//
//        $res = $mgr->createQueryBuilder()
//            ->where('id IN (?)')->setParameter(0, array(9991, 9992))
//            ->fetchAllAssociative();
//        $this->assertEquals(0, count($res));

        $res = $mgr->createQueryBuilder()
            ->whereColumn('id', 'IN', array(1, 2, 3, 4, 5, 6))
            ->fetchAllAssociative();
        $this->assertEquals(6, count($res));

        $res = $mgr->createQueryBuilder()
            ->whereColumn('id', '=', array(9991, 9992))
            ->fetchAllAssociative();
        $this->assertEquals(0, count($res));
    }

    public function testInArrayFromQueryBuilderString()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->where('fld_varchar IN (:Val)')->setParameter('Val', array('a','b','a varchar 8','c'))
            ->fetchAllAssociative();
        $this->assertEquals(1, count($res));

        $res = $mgr->createQueryBuilder()
            ->where('fld_varchar IN (:Val)')->setParameter('Val', array('a','b','c'))
            ->fetchAllAssociative();
        $this->assertEquals(0, count($res));
    }

    public function testWherecolumnInArrayFromQueryBuilderString()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $res = $mgr->createQueryBuilder()
            ->whereColumn('fld_varchar', 'in', array('a','b','a varchar 8','c'))
            ->fetchAllAssociative();
        $this->assertEquals(1, count($res));

        $res = $mgr->createQueryBuilder()

            ->whereColumn('fld_varchar', 'in', array('a','b','c'))
            ->fetchAllAssociative();
        $this->assertEquals(0, count($res));
    }

    public function testInArrayFromMgrFindOneInt()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent = $mgr->findOneWhere('id IN (:Val)', ['Val'=>array(1, 2, 3, 4, 5, 6)]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent);

        $ent = $mgr->findOneWhere('id IN (:Val)', ['Val'=>array(9991, 9992)]);
        $this->assertNull($ent);
    }

    public function testInArrayFromMgrFindOneString()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent = $mgr->findOneWhere('fld_varchar IN (:Val)', ['Val'=>array('a','b','a varchar 8','c')]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent);

        $ent = $mgr->findOneWhere('fld_varchar IN (:Val)', ['Val'=>array('a','b','c')]);
        $this->assertNull($ent);

        try {
            $mgr->findOneWhere('fld_varchar IN (?)', [array('a','b','a varchar 8','c')]);
            $this->assertTrue(false);
        } catch(\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
    }

}