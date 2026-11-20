<?php

namespace App\DTOs;

use Brick\Math\BigInteger;

class ResultDTO
{
    public function __construct(
        public bool $status,
        public string $message
    ) {}
}
