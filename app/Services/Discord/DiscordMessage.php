<?php

namespace App\Services\Discord;

class DiscordMessage
{
    public string  $content   = '';
    public ?string $username  = null;
    public ?string $avatarUrl = null;

    /** @var array<int, array{path: string, name: string}> */
    public array $files = [];

    public function toPayload(): array
    {
        $payload = ['content' => $this->content];

        if ($this->username !== null) {
            $payload['username'] = $this->username;
        }

        if ($this->avatarUrl !== null) {
            $payload['avatar_url'] = $this->avatarUrl;
        }

        return $payload;
    }
}
