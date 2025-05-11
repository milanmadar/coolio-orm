<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;
use tests\Model\OrmOther;

class TextArrayTest extends TestCase
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
        $this->assertEquals(['foo','bar2','baz'], $ent1->getFldStrArray());

        $new = ['foo','bar2','baz','new'];
        $ent1->setFldStrArray($new);
        $mgr->save($ent1);
        $mgr->clearRepository(true);

        $ent1 = $mgr->findById(2);
        $this->assertEquals($new, $ent1->getFldStrArray());
    }

    public function testGetSetSepcialchars()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        /** @var OrmTest\Entity $ent1 */
        $ent1 = $mgr->findById(2);
        $this->assertEquals(['foo','bar2','baz'], $ent1->getFldStrArray());

        $new = ['foo','bar2','baz','n"ew','n\ew','n\\ew',"n'w",'n,ew'];
        $ent1->setFldStrArray($new);
        $mgr->save($ent1);
        $mgr->clearRepository(true);

        $ent1 = $mgr->findById(2);
        $this->assertEquals($new, $ent1->getFldStrArray());

        $mgr->clearRepository(true);
        $ent1 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_str_array', '@>', $new)
            ->fetchOneEntity();

        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);
    }

    public function testWhere1Elem_Slower()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        /** @var OrmTest\Entity $ent1 */
        $ent1 = $mgr->createQueryBuilder()
            ->andWhere(':VAL = ANY(fld_str_array)')
            ->setParameter('VAL', 'foo')
            ->fetchOneEntity();

        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);
    }

    public function testWhere1Elem_Indexable()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        /** @var OrmTest\Entity $ent1 */
        $ent1 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_str_array', '@>', ['foo'])
            ->fetchOneEntity();

        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);
    }

    public function testWhere2Elems_BothMustBeThere_Indexable()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        /** @var OrmTest\Entity $ent1 */
        $ent1 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_str_array', '@>', ['foo','bar2'])
            ->fetchOneEntity();
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);

        $ent2 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_str_array', '@>', ['noooooooooo','bar2'])
            ->fetchOneEntity();
        $this->assertNull($ent2);
    }

    public function testWhere2Elems_AnyCanBeThere_Indexable()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        /** @var OrmTest\Entity $ent1 */
        $ent1 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_str_array', '&&', ['foo','bar2'])
            ->fetchOneEntity();
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);

        $ent2 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_str_array', '&&', ['noooooooooo','bar2'])
            ->fetchOneEntity();
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent2);
    }

    public function testWhereNull()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_str_array', '@>', null)
            ->fetchOneEntity();
        $this->assertNull($ent1);

        $ent2 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_str_array', '&&', null)
            ->fetchOneEntity();
        $this->assertNull($ent2);
    }

    public function testWhereEmptyArray()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $ent1 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_str_array', '@>', [])
            ->fetchOneEntity();
        $this->assertNull($ent1);

        $ent2 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_str_array', '&&', [])
            ->fetchOneEntity();
        $this->assertNull($ent2);
    }
}