<?php

namespace App\Enums;

enum DiscordChannel: string
{
    case Contact = 'CONTACT';
    case Log     = 'LOG';

    public function webhookUrl(): string
    {
        $url = env("DISCORD_WEBHOOK_URL_{$this->value}");
        throw_if(empty($url), \RuntimeException::class, "Webhook URL not configured: DISCORD_WEBHOOK_URL_{$this->value}");
        return $url;
    }
}
