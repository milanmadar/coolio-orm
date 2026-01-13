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

        $this->assertInstanceOf('\DateTimeInterface', $ent1->getFldDate());
        $this->assertInstanceOf('\DateTimeInterface', $ent1->getFldTime());
        $this->assertInstanceOf('\DateTimeInterface', $ent1->getFldTimestamp());
        $this->assertInstanceOf('\DateTimeInterface', $ent1->getFldTimestamptz());
        $this->assertInstanceOf('\DateTimeInterface', $ent1->getFldTimestampMicro());
        $this->assertInstanceOf('\DateTimeInterface', $ent1->getFldTimestamptzMicro());

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

        $this->assertEquals($dt->format('Y-m-d H:i:s'), $ent1->getFldDate()->format('Y-m-d H:i:s'));
        $this->assertEquals($dt->getTimestamp(), $ent1->getFldTime()->getTimestamp());
        $this->assertEquals($dt->getTimestamp(), $ent1->getFldTimestamp()->getTimestamp());
        $this->assertEquals($dt->getTimestamp(), $ent1->getFldTimestamptz()->getTimestamp());
        $this->assertEquals($dt->getTimestamp(), $ent1->getFldTimestampMicro()->getTimestamp());
        $this->assertEquals($dt->getTimestamp(), $ent1->getFldTimestamptzMicro()->getTimestamp());

        // changing this should not change the one inside the entity
        $dt->modify('+1 day');

        $this->assertNotEquals($dt->format('Y-m-d H:i:s'), $ent1->getFldDate()->format('Y-m-d H:i:s'));
        $this->assertNotEquals($dt->getTimestamp(), $ent1->getFldTime()->getTimestamp());
        $this->assertNotEquals($dt->getTimestamp(), $ent1->getFldTimestamp()->getTimestamp());
        $this->assertNotEquals($dt->getTimestamp(), $ent1->getFldTimestamptz()->getTimestamp());
        $this->assertNotEquals($dt->getTimestamp(), $ent1->getFldTimestampMicro()->getTimestamp());
        $this->assertNotEquals($dt->getTimestamp(), $ent1->getFldTimestamptzMicro()->getTimestamp());

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

    public function testFind()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $dt = new \DateTime('2023-11-10T13:25:37.117659Z');

        $ent = $mgr->createEntity()
            ->setFldTimestampMicro($dt)
            ->setFldTimestamptzMicro($dt);
        $mgr->save($ent);

        $ent2 = $mgr->createQueryBuilder()
            ->andWhereColumn('fld_timestamp_micro', '=', $dt->format('Y-m-d H:i:s.u'))
            ->andWhereColumn('fld_timestamptz_micro', '=', $dt->format('Y-m-d H:i:s.u'))
            ->fetchOneEntity();
        $this->assertNotNull($ent2);

        $this->assertEquals(
            $ent->getFldTimestampMicro()->format('Y-m-d H:i:s.u'),
            $ent2->getFldTimestampMicro()->format('Y-m-d H:i:s.u')
        );
        $this->assertEquals(
            $ent->getFldTimestamptzMicro()->format('Y-m-d H:i:s.u'),
            $ent2->getFldTimestamptzMicro()->format('Y-m-d H:i:s.u')
        );
    }
}