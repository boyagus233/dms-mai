<?php

namespace App\Enums;

enum GroupProcess: string
{
    case M = 'M';
    case C = 'C';
    case S = 'S';

    public function label(): string
    {
        return match($this) {
            self::M => 'Management',
            self::C => 'Product Realization',
            self::S => 'Supporting',
        };
    }
}
