<?php

namespace Pgsql;

use Milanmadar\CoolioORM\ORM;
use Milanmadar\CoolioORM\ORMException\ORMUniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmOther;

class UniqueText extends TestCase
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
        self::$dbHelper->resetTo('Pgsql/fixtures/unique.sql');
    }

    public function testCorrectException()
    {
        $this->expectException(ORMUniqueConstraintViolationException::class);

        $mgr = self::$dbHelper->getManager(OrmOther\Manager::class);

        $ent1 = $mgr->createEntity()->setFldInt(1);
        $mgr->save($ent1);

        $ent2 = $mgr->createEntity()->setFldInt(1);
        $mgr->save($ent2); // throws
    }
}