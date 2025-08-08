<?php

declare(strict_types=1);

namespace AltchaOrg\Altcha;

class ServerSignatureVerification
{
    public function __construct(
        public readonly bool $verified,
        public readonly ?ServerSignatureVerificationData $data,
    ) {
    }
}
