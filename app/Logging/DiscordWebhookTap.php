<?php

namespace App\Logging;

use Monolog\Level;

class DiscordWebhookTap
{
    public function __invoke($logger): void
    {
        $logger->pushHandler(new DiscordWebhookHandler(Level::Warning));
    }
}
