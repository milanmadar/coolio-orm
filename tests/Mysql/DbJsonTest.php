<?php

namespace Mysql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;

class DbJsonTest extends TestCase
{
    private static DbHelper $dbHelper;
    private static int $oRowCnt;

    private function json(): array
    {
        return [
            'num'=>123,
            'str'=>'halika',
            'arr_num'=>[1,2,3],
            'arr_str'=>['one','two','three'],
            'arr_arr'=>[
                'key_str'=>'bye',
                'key_num'=>456,
                'key_arr_str'=>['one','two','three'],
                'key_arr_arr'=>[
                    'deep_1'=>789
                ],
            ]
        ];
    }

    // This method runs once when the test class is loaded
    public static function setUpBeforeClass(): void
    {
        $conn = ORM::instance()->getDbByUrl($_ENV['DB_MYSQL_DB1']);
        self::$dbHelper = new DbHelper( $conn );
    }

    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
        self::$dbHelper->resetTo('Mysql/fixtures/fix.sql');
        if(!isset(self::$oRowCnt)) {
            self::$oRowCnt = self::$dbHelper->countRows('orm_test');
        }
    }

    public function testInsert()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $json = $this->json();
        $ent = $mgr->createEntity();

        $ent->setFldJson($json);
        $this->assertEquals($json, $ent->getFldJson());

        $rowCnt = self::$dbHelper->countRows('orm_test');
        $mgr->save($ent);
        $this->assertEquals($ent->getId(), $rowCnt+1);

        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt+1, $rowCnt);

        $mgr->clearRepository(true);

        $ent3 = $mgr->findById($ent->getId());
        $this->assertTrue($ent !== $ent3);

        $this->assertEquals($json, $ent->getFldJson());
        $this->assertEquals($ent->getFldJson(), $ent3->getFldJson());
    }

    public function testUpdate()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $json = $this->json();
        $ent = $mgr->createEntity();

        $ent->setFldJson($json);

        $mgr->save($ent);

        $mgr->clearRepository(true);

        $jsonUpd = $json;
        $jsonUpd['abc'] = 98765;
        $ent->setFldJson($jsonUpd);
        $mgr->save($ent);

        $ent2 = $mgr->findById($ent->getId());
        $this->assertTrue($ent !== $ent2);

        $this->assertEquals($jsonUpd, $ent->getFldJson());
        $this->assertEquals($ent->getFldJson(), $ent2->getFldJson());
    }

    public function testSelectAsNew()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $json = $this->json();
        $ent = $mgr->createEntity();

        $ent->setFldJson($json);

        $mgr->save($ent);

        $mgr->clearRepository(true);
        $entNew = $mgr->findOneWhere("fld_json->'$.str'='halika'");
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $entNew);
        $this->assertTrue($ent !== $entNew);

        $mgr->clearRepository(true);
        $entNew = $mgr->findOneWhere("fld_json->'$.str'=?", [$json['str']]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $entNew);
        $this->assertTrue($ent !== $entNew);

        $mgr->clearRepository(true);
        $entNew = $mgr->findOneWhere("fld_json->'$.str'=:Val", ['Val'=>$json['str']]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $entNew);
        $this->assertTrue($ent !== $entNew);

        $mgr->clearRepository(true);
        $entNew = $mgr->findOneWhere("fld_json->'$.str'=:Val", ['Val'=>'nope']);
        $this->assertNull($entNew);
    }

    public function testSelectUseRepo()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $json = $this->json();
        $ent = $mgr->createEntity();

        $ent->setFldJson($json);

        $mgr->save($ent);

        $entNew = $mgr->findOneWhere("fld_json->'$.str'='halika'");
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $entNew);
        $this->assertTrue($ent === $entNew);

        $entNew = $mgr->findOneWhere("fld_json->'$.str'=?", [$json['str']]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $entNew);
        $this->assertTrue($ent === $entNew);

        $entNew = $mgr->findOneWhere("fld_json->'$.str'=:Val", ['Val'=>$json['str']]);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $entNew);
        $this->assertTrue($ent === $entNew);

        $entNew = $mgr->findOneWhere("fld_json->'$.str'=:Val", ['Val'=>'nope']);
        $this->assertNull($entNew);
    }

    public function testSimpleArrayInJsonField()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $arr = ['asd','efg','hij'];
        $ent = $mgr->createEntity();

        $ent->setFldJson($arr);
        $this->assertEquals($arr, $ent->getFldJson());

        $mgr->save($ent);

        $rowCnt = self::$dbHelper->countRows('orm_test');
        $this->assertEquals(self::$oRowCnt+1, $rowCnt);

        $mgr->clearRepository(true);

        $ent2 = $mgr->findById($ent->getId());
        $this->assertTrue($ent !== $ent2);

        $this->assertEquals($arr, $ent->getFldJson());
        $this->assertEquals($ent->getFldJson(), $ent2->getFldJson());
    }
}