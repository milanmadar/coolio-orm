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
    private static DbHelper $dbHelper2;

    // This method runs once when the test class is loaded
    public static function setUpBeforeClass(): void
    {
        $conn1 = ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB1']);
        self::$dbHelper = new DbHelper( $conn1 );

        $conn2 = ORM::instance()->getDbByUrl($_ENV['DB_POSTGRES_DB2']);
        self::$dbHelper2 = new DbHelper( $conn2 );
    }

    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
        self::$dbHelper->resetTo('Pgsql/fixtures/fix.sql');
        self::$dbHelper2->resetTo('Pgsql/fixtures/fix_async2.sql');
    }

    public function testSpeedAndResultsetDiffDBs()
    {
        $orm = \Milanmadar\CoolioORM\ORM::instance();

        /** @var OrmTest\Manager $mgr1 */
        $mgr1 = $orm->entityManager(OrmTest\Manager::class, $orm->getDbByUrl($_ENV['DB_POSTGRES_DB1']));
        /** @var OrmTest\Manager $mgr2 */
        $mgr2 = $orm->entityManager(OrmTest\Manager::class, $orm->getDbByUrl($_ENV['DB_POSTGRES_DB2']));

        $asyncQueries = new AsyncQueries();

        $qb = $mgr1->createQueryBuilder()
            ->select('fld_char')
            ->andWhereColumn('id', '=', 1);
        $asyncQueries->addQuery_fromQueryBuilder('q1', $qb);

        $qb = $mgr2->createQueryBuilder()
            ->select('fld_char')
            ->andWhereColumn('id', 'in', [1, 2])
            ->orderBy('id', 'asc');
        $asyncQueries->addQuery_fromQueryBuilder('q2', $qb);

        $resultSet = $asyncQueries->fetch();

        $results = $resultSet->getResultset();
        $this->assertEquals('fgeabdhc', $results['q1'][0]['fld_char']);
        $this->assertEquals('aSYNC   ', $results['q2'][0]['fld_char']);
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