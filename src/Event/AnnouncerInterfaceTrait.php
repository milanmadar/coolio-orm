<?php

namespace Milanmadar\CoolioORM\Event;

trait AnnouncerInterfaceTrait
{
    /**
     * @var array<string, array<int, array{object, string, mixed}>>
     */
    protected array $eventSubscribers;

    public function eventSubscribe(EntityEventTypeEnum $eventName, object $subscriber, string $subscriberMethodName, mixed $sendBackParams = null): void
    {
        $splId = spl_object_id($subscriber);
        if(!isset($this->eventSubscribers)) {
            $this->eventSubscribers = [$eventName->name => [$splId => [$subscriber, $subscriberMethodName, $sendBackParams] ] ];
        } elseif(!isset($this->eventSubscribers[$eventName->name])) {
            $this->eventSubscribers[$eventName->name] = [$splId => [$subscriber, $subscriberMethodName, $sendBackParams] ];
        } else {
            $this->eventSubscribers[$eventName->name][$splId] = [$subscriber, $subscriberMethodName, $sendBackParams];
        }
    }

    public function eventUnsubscribe(EntityEventTypeEnum $eventName, object $subscriber): void
    {
        if(isset($this->eventSubscribers[$eventName->name])) {
            $splId = spl_object_id($subscriber);
            unset($this->eventSubscribers[$eventName->name][$splId]);
        }
    }

    public function eventHasSubscribers(EntityEventTypeEnum $eventName): bool
    {
        return !empty($this->eventSubscribers[$eventName->name]);
    }

    public function eventAnnounce(EntityEventTypeEnum $eventName, object $sender, mixed $args = null): void
    {
        if(isset($this->eventSubscribers[$eventName->name])) {
            foreach($this->eventSubscribers[$eventName->name] as $arr) {
                $subscriber = $arr[0];
                $subscriberMethodName = $arr[1];
                if(isset($arr[2])) {
                    $subscriber->$subscriberMethodName($sender, $args, $arr[2]);
                } else {
                    $subscriber->$subscriberMethodName($sender, $args);
                }
            }
        }
    }

}