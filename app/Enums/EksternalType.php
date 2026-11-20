<?php

namespace App\Enums;

enum EksternalType: string
{
    case DAIHATSU_TECHNICAL_STANDARD = 'Daihatsu Technical Standard';
    case TOYOTA_ENGINEERING_STANDARD = 'Toyota Engineering Standard';
    case HONDA_ENGINEERING_STANDARD = 'Honda Engineering Standard';
    case MITSHUBISHI_ENGINEERING_STANDARD = 'Mitshubishi Engineering Standard';
    case SUPPLIER_QUALITY_MANUAL = 'Supplier Quality Manual';
}
