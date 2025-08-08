<?php

declare(strict_types=1);

namespace AltchaOrg\Altcha;

use AltchaOrg\Altcha\Hasher\Algorithm;

/**
 * @phpstan-type ChallengeParams array<string, null|scalar>
 */
class BaseChallengeOptions
{
    public const DEFAULT_MAX_NUMBER = 1000000;

    public readonly string $salt;

    /**
     * Options for creation of a new challenge.
     *
     * @see ChallengeOptions for options with sane defaults.
     *
     * @param ChallengeParams $params
     */
    public function __construct(
        public readonly Algorithm $algorithm,
        public readonly int $maxNumber,
        public readonly ?\DateTimeInterface $expires,
        string $salt,
        public readonly int $number,
        public readonly array $params,
    ) {
        if ($expires) {
            $params['expires'] = $expires->getTimestamp();
        }

        if (!empty($params)) {
            $salt .= '?' . http_build_query($params);
        }

        $this->salt = $salt;
    }
}
