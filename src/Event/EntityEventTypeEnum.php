<?php

namespace Milanmadar\CoolioORM\Event;

enum EntityEventTypeEnum
{
    case DATA_CHANGED;
    case ID_CHANGED;
    case DELETED;
    /** @Event Arg: array<string, mixed> , The entire new data (the EventDataChanged and EventIdChanged) are also sent 1-by-1 during rollback */
    case ROLLBACK;
    /** @Event Arg: array<string, mixed> , The entire commited data */
    case COMMITTED;
    /** @Event Arg: (no args) */
    case DESTRUCT;
}
