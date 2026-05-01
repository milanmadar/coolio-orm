<?php

namespace Milanmadar\CoolioORM\Event;

trait AnnouncerInterfaceTrait
{
    /**
     * @var array<string, array<int, array{0: \WeakReference<object>, 1: string, 2: mixed}>>
     */
    protected array $eventSubscribers = [];

    /**
     * @param EntityEventTypeEnum $eventName
     * @param object $subscriber
     * @param string $subscriberMethodName
     * @param mixed|null $sendBackParams
     * @return void
     */
    public function eventSubscribe(EntityEventTypeEnum $eventName, object $subscriber, string $subscriberMethodName, mixed $sendBackParams = null): void
    {
        $splId = spl_object_id($subscriber);

        // store a WeakReference so we don't block Garbage Collection
        $this->eventSubscribers[$eventName->name][$splId] = [
            \WeakReference::create($subscriber),
            $subscriberMethodName,
            $sendBackParams
        ];
    }

    /**
     * @param EntityEventTypeEnum $eventName
     * @param object $subscriber
     * @return void
     */
    public function eventUnsubscribe(EntityEventTypeEnum $eventName, object $subscriber): void
    {
        if(isset($this->eventSubscribers[$eventName->name])) {
            $splId = spl_object_id($subscriber);
            unset($this->eventSubscribers[$eventName->name][$splId]);
        }
    }

    /**
     * @param EntityEventTypeEnum $eventName
     * @return bool
     */
    public function eventHasSubscribers(EntityEventTypeEnum $eventName): bool
    {
        return !empty($this->eventSubscribers[$eventName->name]);
    }

    /**
     * @param EntityEventTypeEnum $eventName
     * @param object $sender
     * @param mixed|null $args
     * @return void
     */
    public function eventAnnounce(EntityEventTypeEnum $eventName, object $sender, mixed $args = null): void
    {
        if (empty($this->eventSubscribers[$eventName->name])) {
            return;
        }

        foreach ($this->eventSubscribers[$eventName->name] as $splId => $arr) {
            $subscriber = $arr[0]->get(); // resolve the WeakReference

            // subscriber was deleted by PHP's Garbage Collector, so we clean up the entry
            if ($subscriber === null) {
                unset($this->eventSubscribers[$eventName->name][$splId]);
                continue;
            }

            $method = $arr[1];
            if (isset($arr[2])) {
                $subscriber->$method($sender, $args, $arr[2]);
            } else {
                $subscriber->$method($sender, $args);
            }
        }
    }

    /**
     * Useful for clearing state during Manager::clearRepository() or __clone()
     */
    public function eventClearSubscribers(): void
    {
        $this->eventSubscribers = [];
    }
}