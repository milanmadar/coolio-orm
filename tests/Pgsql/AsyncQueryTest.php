<?php

namespace Pgsql;

use Milanmadar\CoolioORM\AsyncQueries;
use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\DbHelper;
use tests\Model\OrmTest;

class AsyncQueryTest extends TestCase
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

    public function testSpeedAndResultset()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $asyncQueries = new AsyncQueries();

        $nrOfQueries = 3;
        for($i=1; $i<=$nrOfQueries; ++$i)
        {
            $qb = $mgr->createQueryBuilder()
                ->select('pg_sleep('.$i.'), '.$i.' as q')
                ->andWhereColumn('id', '=', 1);

            $asyncQueries->addQuery_fromQueryBuilder('query_'.$i, $qb);
        }

        $start = microtime(true);

        $resultSet = $asyncQueries->fetch();

        $elapsed = microtime(true) - $start;

        $this->assertLessThan($nrOfQueries+1, $elapsed);

        $results = $resultSet->getResultset();
        $this->assertEquals(1, $results['query_1'][0]['q']);
        $this->assertEquals(2, $results['query_2'][0]['q']);
    }

    public function testEntity()
    {
        $mgr = self::$dbHelper->getManager(OrmTest\Manager::class);

        $asyncQueries = new AsyncQueries();

        $nrOfQueries = 4;
        for($id=1; $id<=$nrOfQueries; ++$id)
        {
            $qb = $mgr->createQueryBuilder()
                ->andWhereColumn('id', '=', $id);

            $asyncQueries->addQuery_fromQueryBuilder('query_'.$id, $qb);
        }

        $resultSet = $asyncQueries->fetch();

        $results = $resultSet->getResultset();
        foreach($results as $name => $rows)
        {
            // just to know which 'id' we wanted
            $_ = explode('_', $name);
            $id = (int)$_[1];

            $ent = $mgr->createEntityFromDbData($rows[0]);
            $this->assertEquals($id, $ent->getId());
            $this->assertTrue($ent->getFldTimestamp() instanceof \DateTime);
        }
    }

}