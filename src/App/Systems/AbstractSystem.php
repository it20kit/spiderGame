<?php

declare(strict_types=1);

namespace App\Systems;

use App\EventBus;
use App\EventPusher;
use App\Events\Event;

abstract class AbstractSystem
{
    protected array $events;

    protected EventPusher $eventPusher;

    /**
     * @return array<string, callable>
     */
    abstract public function getSubscriptions(): array;

    public function setEventPusher(EventPusher $eventPusher): void
    {
        $this->eventPusher = $eventPusher;
    }

}