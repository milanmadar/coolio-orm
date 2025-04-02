<?php

namespace tests;

use Milanmadar\CoolioORM\Entity;
use Milanmadar\CoolioORM\Event\EntityEventTypeEnum;

class EntityEventSubscriber
{
    /** @var array<array <string, string, mixed, mixed>> Events history */
    public array $hist;

    public function __construct()
    {
        $this->hist = [];
    }

    public function subToDataChanges(Entity $ent): self
    {
        $ent->eventSubscribe(EntityEventTypeEnum::DATA_CHANGED, $this, 'onDataChanged');
        $ent->eventSubscribe(EntityEventTypeEnum::ID_CHANGED, $this, 'onIdChanged');
        return $this;
    }

    public function subToDestruct(Entity $ent): self
    {
        $ent->eventSubscribe(EntityEventTypeEnum::DESTRUCT, $this, 'onDestruct');
        return $this;
    }

    /**
     * @param Entity $ent
     * @param array<string, mixed, mixed> $arg
     */
    public function onDataChanged(Entity $ent, array $arg): void
    {
        $this->hist[] = [EntityEventTypeEnum::DATA_CHANGED, $arg[0], $arg[1], $arg[2]];
    }

    /**
     * @param Entity $ent
     * @param array<int|null, int|null> $arg
     */
    public function onIdChanged(Entity $ent, array $arg): void
    {
        $this->hist[] = [EntityEventTypeEnum::ID_CHANGED, '_', $arg[0], $arg[1]];
    }

    /**
     * @param Entity $ent
     */
    public function onDestruct(Entity $ent): void
    {
        $this->hist[] = [EntityEventTypeEnum::DESTRUCT, '_', $ent->getId(), spl_object_id($ent)];
    }

}