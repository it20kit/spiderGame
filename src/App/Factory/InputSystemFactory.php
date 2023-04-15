<?php

declare(strict_types=1);

namespace App\Factory;

use App\Keyboard;
use App\Systems\InputSystem;

class InputSystemFactory implements FactoryInterface
{
    public function build(): object
    {
        return new InputSystem(new Keyboard());
    }
}
