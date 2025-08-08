<?php

declare(strict_types=1);

namespace AltchaOrg\Altcha;

class Solution
{
    public function __construct(
        public readonly int $number,
        public readonly float $took,
    ) {
    }
}
