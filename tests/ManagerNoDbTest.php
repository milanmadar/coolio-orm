<?php

namespace tests;

use Milanmadar\CoolioORM\Event\EntityEventTypeEnum;
use Milanmadar\CoolioORM\ORM;
use PHPUnit\Framework\TestCase;
use tests\Model\OrmTest;

class ManagerNoDbTest extends TestCase
{
    // This method runs before every $this->test*() method runs
    protected function setUp(): void
    {
        ORM::_clearSingleton();
    }

    public function testNewEntityNoDataValues()
    {
        $orm = \Milanmadar\CoolioORM\ORM::instance();
        $mgr = $orm->entityManager(OrmTest\Manager::class);

        $ent = $mgr->createEntity();
        $dataOrigi = $ent->_getData();

        $this->assertFalse( $ent->hasId() );
        $this->assertNull( $ent->getId() );

        $this->assertNull( $ent->getFldInt() );
        $this->assertNull( $ent->getFldFloat() );
        $this->assertFalse( $ent->_didDataChange() );

        $ent->setFldInt(1);
        $this->assertEquals(1, $ent->getFldInt());
        $this->assertNull( $ent->getFldFloat() );
        $this->assertTrue( $ent->_didDataChange() );
        $this->assertTrue( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );

        $ent->setFldInt(2);
        $this->assertEquals(2, $ent->getFldInt());
        $this->assertNull( $ent->getFldFloat() );
        $this->assertTrue( $ent->_didDataChange() );
        $this->assertTrue( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );

        $ent->setFldFloat(3);
        $this->assertEquals(2, $ent->getFldInt());
        $this->assertEquals(3, $ent->getFldFloat());
        $this->assertTrue( $ent->_didDataChange() );
        $this->assertTrue( $ent->_didDataChange('fld_int') );
        $this->assertTrue( $ent->_didDataChange('fld_float') );

        $ent->setFldInt(null);
        $this->assertNull( $ent->getFldInt() );
        $this->assertEquals(3, $ent->getFldFloat());
        $this->assertTrue( $ent->_didDataChange() );
        $this->assertFalse( $ent->_didDataChange('fld_int') );
        $this->assertTrue( $ent->_didDataChange('fld_float') );

        $ent->setFldFloat(null);
        $this->assertNull( $ent->getFldInt() );
        $this->assertNull( $ent->getFldFloat() );
        $this->assertFalse( $ent->_didDataChange() );
        $this->assertFalse( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );

        $ent->setFldInt(2);
        $ent->setFldFloat(3);
        $this->assertEquals(2, $ent->getFldInt());
        $this->assertEquals(3, $ent->getFldFloat());
        $this->assertTrue( $ent->_didDataChange() );
        $this->assertTrue( $ent->_didDataChange('fld_int') );
        $this->assertTrue( $ent->_didDataChange('fld_float') );

        $ent->_rollback();
        $this->assertNull( $ent->getFldInt() );
        $this->assertNull( $ent->getFldFloat() );
        $this->assertFalse( $ent->_didDataChange() );
        $this->assertFalse( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );
        $this->assertEquals( $dataOrigi, $ent->_getData());

        $ent->setFldInt(2);
        $ent->setFldFloat(3);
        $this->assertEquals(2, $ent->getFldInt());
        $this->assertEquals(3, $ent->getFldFloat());
        $this->assertTrue( $ent->_didDataChange() );
        $this->assertTrue( $ent->_didDataChange('fld_int') );
        $this->assertTrue( $ent->_didDataChange('fld_float') );

        $ent->_commit();
        $dataCommited = $ent->_getData();
        $this->assertEquals(2, $ent->getFldInt());
        $this->assertEquals(3, $ent->getFldFloat());
        $this->assertFalse( $ent->_didDataChange() );
        $this->assertFalse( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );

        $ent->_rollback();
        $this->assertEquals(2, $ent->getFldInt());
        $this->assertEquals(3, $ent->getFldFloat());
        $this->assertFalse( $ent->_didDataChange() );
        $this->assertFalse( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );
        $this->assertNotEquals( $dataOrigi, $ent->_getData());
        $this->assertEquals( $dataCommited, $ent->_getData());
    }

    public function testNewEntityWithDataValues()
    {
        $orm = \Milanmadar\CoolioORM\ORM::instance();
        $mgr = $orm->entityManager(OrmTest\Manager::class);

        $ent = $mgr->createEntity(['fld_int'=>1]);
        $dataOrigi = $ent->_getData();

        $this->assertFalse( $ent->hasId() );
        $this->assertNull( $ent->getId() );

        $this->assertEquals(1, $ent->getFldInt() );
        $this->assertNull( $ent->getFldFloat() );
        $this->assertFalse( $ent->_didDataChange() );

        $ent->setFldInt(1);
        $this->assertEquals(1, $ent->getFldInt());
        $this->assertNull( $ent->getFldFloat() );
        $this->assertFalse( $ent->_didDataChange() );
        $this->assertFalse( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );

        $ent->setFldInt(null);
        $this->assertNull( $ent->getFldInt() );
        $this->assertNull( $ent->getFldFloat() );
        $this->assertTrue( $ent->_didDataChange() );
        $this->assertTrue( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );

        $ent->setFldInt(1);
        $this->assertEquals(1, $ent->getFldInt());
        $this->assertNull( $ent->getFldFloat() );
        $this->assertFalse( $ent->_didDataChange() );
        $this->assertFalse( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );

        $ent->setFldInt(2);
        $this->assertEquals(2, $ent->getFldInt());
        $this->assertNull( $ent->getFldFloat() );
        $this->assertTrue( $ent->_didDataChange() );
        $this->assertTrue( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );

        $ent->setFldInt(null);
        $this->assertNull( $ent->getFldInt() );
        $this->assertNull( $ent->getFldFloat() );
        $this->assertTrue( $ent->_didDataChange() );
        $this->assertTrue( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );

        $ent->_rollback();
        $this->assertEquals(1, $ent->getFldInt());
        $this->assertNull( $ent->getFldFloat() );
        $this->assertFalse( $ent->_didDataChange() );
        $this->assertFalse( $ent->_didDataChange('fld_int') );
        $this->assertFalse( $ent->_didDataChange('fld_float') );
        $this->assertEquals( $dataOrigi, $ent->_getData());
    }

    public function testEntityEventDataChanged()
    {
        $orm = \Milanmadar\CoolioORM\ORM::instance();
        $mgr = $orm->entityManager(OrmTest\Manager::class);

        $ent = $mgr->createEntity();
        $sub = new EntityEventSubscriber();
        $sub->subToDataChanges($ent);
        $goodHist = [];

        // trigger DataChange
        $ent->setFldInt(1);
        $goodHist[] = [EntityEventTypeEnum::DATA_CHANGED, 'fld_int', 1, null];
        $this->assertEquals(count($goodHist), count($sub->hist));
        $this->assertEquals($goodHist, $sub->hist);

        // no trigger
        $ent->setFldInt(1);
        $this->assertEquals(count($goodHist), count($sub->hist));
        $this->assertEquals($goodHist, $sub->hist);

        // trigger DataChange
        $ent->setFldInt(2);
        $goodHist[] = [EntityEventTypeEnum::DATA_CHANGED, 'fld_int', 2, 1];
        $this->assertEquals(count($goodHist), count($sub->hist));
        $this->assertEquals($goodHist, $sub->hist);

        // trigger DataChange and IdChange
        $ent->setId(11);
        $goodHist[] = [EntityEventTypeEnum::DATA_CHANGED, 'id', 11, null];
        $goodHist[] = [EntityEventTypeEnum::ID_CHANGED, '_', 11, null];
        $this->assertEquals(count($goodHist), count($sub->hist));
        $this->assertEquals($goodHist, $sub->hist);

        // rollback triggers verything back to original
        $ent->_rollback();
        $goodHist[] = [EntityEventTypeEnum::DATA_CHANGED, 'fld_int', null, 2];
        $goodHist[] = [EntityEventTypeEnum::DATA_CHANGED, 'id', null, 11];
        $goodHist[] = [EntityEventTypeEnum::ID_CHANGED, '_', null, 11];
        $this->assertEquals(count($goodHist), count($sub->hist));
        $this->assertEquals($goodHist, $sub->hist);

        // commit does no events
        $ent->_commit();
        $this->assertEquals(count($goodHist), count($sub->hist));
        $this->assertEquals($goodHist, $sub->hist);

        // rollback after commit also does no events
        $ent->_rollback();
        $this->assertEquals(count($goodHist), count($sub->hist));
        $this->assertEquals($goodHist, $sub->hist);
    }

    public function testEntityEventDestruct()
    {
        $orm = \Milanmadar\CoolioORM\ORM::instance();
        $mgr = $orm->entityManager(OrmTest\Manager::class);

        $ent = $mgr->createEntity();
        $sub = new EntityEventSubscriber();
        $sub->subToDestruct($ent);
        $goodHist = [];

        $splId = spl_object_id($ent);

        $mgr->_getEntityRepository()->del($ent, $mgr->getDbTable().$mgr->getDbConnUrl());
        unset($ent);

        $goodHist[] = [EntityEventTypeEnum::DESTRUCT, '_', null, $splId];
        $this->assertEquals($goodHist, $sub->hist);
    }

}