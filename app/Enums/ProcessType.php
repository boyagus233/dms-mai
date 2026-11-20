<?php

namespace App\Enums;

enum ProcessType: string
{
    case MANAGEMENT_PROCESS = 'MANAGEMENT_PROCESS';
    case PRODUCT_REALIZATION_PROCESS = 'PRODUCT_REALIZATION_PROCESS';
    case SUPPORTING_PROCESS = 'SUPPORTING_PROCESS';

    public function label(): string
    {
        return match($this) {
            self::MANAGEMENT_PROCESS => 'MANAGEMENT PROCESS',
            self::PRODUCT_REALIZATION_PROCESS => 'PRODUCT REALIZATION PROCESS',
            self::SUPPORTING_PROCESS => 'SUPPORTING PROCESS',
        };
    }
}
