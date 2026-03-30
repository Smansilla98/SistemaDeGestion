<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Core\Logger;
use App\Events\Internal\UserRegisteredViaJwt;

final class LogUserRegisteredViaJwt
{
    public function __construct(
        private readonly Logger $logger
    ) {}

    public function handle(UserRegisteredViaJwt $event): void
    {
        $this->logger->info('auth.jwt.register', [
            'user_id' => $event->userId,
            'username' => $event->username,
        ]);
    }
}
