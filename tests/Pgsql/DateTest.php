<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;

class DateTest extends TestCase
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

    public function testGet()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        /** @var OrmTest\Entity $ent1 */
        $ent1 = $mgr->findById(1);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);

        $this->assertInstanceOf('\DateTime', $ent1->getFldDate());
        $this->assertInstanceOf('\DateTime', $ent1->getFldTime());
        $this->assertInstanceOf('\DateTime', $ent1->getFldTimestamp());
        $this->assertInstanceOf('\DateTime', $ent1->getFldTimestamptz());
        $this->assertInstanceOf('\DateTime', $ent1->getFldTimestampMicro());
        $this->assertInstanceOf('\DateTime', $ent1->getFldTimestamptzMicro());

        $this->assertEquals((new \DateTime('2025-05-01'))->getTimestamp(), $ent1->getFldDate()->getTimestamp());
        $this->assertEquals((new \DateTime('01:30:00'))->getTimestamp(), $ent1->getFldTime()->getTimestamp());
        $this->assertEquals((new \DateTime('2025-05-01 14:30:00'))->getTimestamp(), $ent1->getFldTimestamp()->getTimestamp());
        $this->assertEquals((new \DateTime('2025-05-01 14:30:00+02'))->getTimestamp(), $ent1->getFldTimestamptz()->getTimestamp());
        $this->assertEquals((new \DateTime('2025-05-01 14:30:00.123456'))->getTimestamp(), $ent1->getFldTimestampMicro()->getTimestamp());
        $this->assertEquals((new \DateTime('2025-05-01 14:30:00.123456+02'))->getTimestamp(), $ent1->getFldTimestamptzMicro()->getTimestamp());
    }

    public function testSet()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        /** @var OrmTest\Entity $ent1 */
        $ent1 = $mgr->findById(1);
        $this->assertInstanceOf('\tests\Model\OrmTest\Entity', $ent1);

        $dt = new \DateTime('1985-08-14 12:00:00.123456+02');
        $ent1->setFldDate($dt);
        $ent1->setFldTime($dt);
        $ent1->setFldTimestamp($dt);
        $ent1->setFldTimestamptz($dt);
        $ent1->setFldTimestampMicro($dt);
        $ent1->setFldTimestamptzMicro($dt);

        $mgr->save($ent1);

        $mgr->clearRepository(true);
        $ent1 = $mgr->findById(1);

        $this->assertEquals((new \DateTime('1985-08-14'))->getTimestamp(), $ent1->getFldDate()->getTimestamp());
        $this->assertEquals((new \DateTime('12:00:00'))->getTimestamp(), $ent1->getFldTime()->getTimestamp());
        $this->assertEquals((new \DateTime('1985-08-14 12:00:00'))->getTimestamp(), $ent1->getFldTimestamp()->getTimestamp());
        $this->assertEquals((new \DateTime('1985-08-14 12:00:00+02'))->getTimestamp(), $ent1->getFldTimestamptz()->getTimestamp());
        $this->assertEquals((new \DateTime('1985-08-14 12:00:00.123456'))->getTimestamp(), $ent1->getFldTimestampMicro()->getTimestamp());
        $this->assertEquals((new \DateTime('1985-08-14 12:00:00.123456+02'))->getTimestamp(), $ent1->getFldTimestamptzMicro()->getTimestamp());
    }

}