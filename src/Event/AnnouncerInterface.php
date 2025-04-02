<?php

namespace Milanmadar\CoolioORM\Event;

/**
 * An interface for the classes that want to announce their Events.
 * @package Event
 */
interface AnnouncerInterface
{
    /**
     * To subscribe an object to our event (the one specified in the 1st param)
     * @param EntityEventTypeEnum $eventName
     * @param object $subscriber
     * @param string $subscriberMethodName
     * @param mixed $sendBackParams This will be send back to the subscriber
     */
    public function eventSubscribe(EntityEventTypeEnum $eventName, object $subscriber, string $subscriberMethodName, mixed $sendBackParams = null): void;

    /**
     * To unsubscribe an object from our event (the one specified in the 1st param)
     * @param EntityEventTypeEnum $eventName
     * @param object $subscriber
     */
    public function eventUnsubscribe(EntityEventTypeEnum $eventName, object $subscriber): void;

    /**
     * Does this event has any subscribers?
     * @param EntityEventTypeEnum $eventName
     * @return bool
     */
    public function eventHasSubscribers(EntityEventTypeEnum $eventName): bool;

    /**
     * Dispatch our event to the subscribers
     * @param EntityEventTypeEnum $eventName
     * @param object $sender
     * @param mixed $args Optional
     */
    public function eventAnnounce(EntityEventTypeEnum $eventName, object $sender, mixed $args = null): void;

}