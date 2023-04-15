<?php

declare(strict_types=1);
namespace App\Systems;

use App\Events\CheckInputEvent;
use App\Events\InputEvent;
use App\Keyboard;

class InputSystem extends AbstractSystem
{
    private Keyboard $keyboard;

    public function __construct(Keyboard $keyboard)
    {
        $this->keyboard = $keyboard;
    }

    public function getSubscriptions(): array
    {
        return [
            CheckInputEvent::class => function () {
                $this->acceptPlayerInput();
            },
        ];
    }

    public function acceptPlayerInput(): void
    {
        $acceptedData = $this->keyboard->inputPlayer();
        $this->eventPusher->push(new InputEvent($acceptedData));
    }

}