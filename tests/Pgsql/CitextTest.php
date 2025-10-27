<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\CitextTest as CiTextTestModel;

class CitextTest extends TestCase
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
        self::$dbHelper->resetTo('Pgsql/fixtures/citext.sql');
    }

    public function testGetById()
    {
        $mgr = self::$dbHelper->getManager(CiTextTestModel\Manager::class);

        /** @var CiTextTestModel\Entity $ent1 */
        $ent1 = $mgr->findById(1);
        $this->assertInstanceOf('\tests\Model\CiTextTest\Entity', $ent1);

        $this->assertEquals('Case Insensitive Text', $ent1->getCitxtCol());
    }

    public function testGetByCitext()
    {
        $mgr = self::$dbHelper->getManager(CiTextTestModel\Manager::class);

        /** @var CiTextTestModel\Entity $ent1 */
        $ent1 = $mgr->findByField('citxt_col', 'case inSENSITIVE text', true);
        $this->assertInstanceOf('\tests\Model\CiTextTest\Entity', $ent1);

        $this->assertEquals('Case Insensitive Text', $ent1->getCitxtCol());
    }

    public function testSet()
    {
        $mgr = self::$dbHelper->getManager(CiTextTestModel\Manager::class);

        /** @var CiTextTestModel\Entity $ent1 */
        $ent1 = $mgr->findById(1);
        $this->assertInstanceOf('\tests\Model\CiTextTest\Entity', $ent1);

        $ent1->setCitxtCol('NEW value');

        $mgr->save($ent1);

        $mgr->clearRepository(true);
        $ent1 = $mgr->findById(1);

        $this->assertEquals('NEW value', $ent1->getCitxtCol());
    }
}