<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Core\Logger;
use App\Events\Internal\UserSignedInViaJwt;

final class LogUserSignedInViaJwt
{
    public function __construct(
        private readonly Logger $logger
    ) {}

    public function handle(UserSignedInViaJwt $event): void
    {
        $this->logger->info('auth.jwt.login', [
            'user_id' => $event->userId,
            'username' => $event->username,
        ]);
    }
}
