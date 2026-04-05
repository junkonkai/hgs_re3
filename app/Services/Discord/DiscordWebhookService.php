<?php

namespace App\Services\Discord;

use App\Enums\DiscordChannel;
use Illuminate\Support\Facades\Http;

class DiscordWebhookService
{
    private DiscordMessage $message;
    private ?DiscordChannel $channel = null;

    public function __construct()
    {
        $this->message = new DiscordMessage();
    }

    public function to(DiscordChannel $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    public function username(string $name): static
    {
        $this->message->username = $name;
        return $this;
    }

    public function avatarUrl(string $url): static
    {
        $this->message->avatarUrl = $url;
        return $this;
    }

    /**
     * @param string $filePath  送信するファイルの絶対パス
     * @param string $fileName  Discord上に表示されるファイル名
     */
    public function attach(string $filePath, string $fileName): static
    {
        $this->message->files[] = ['path' => $filePath, 'name' => $fileName];
        return $this;
    }

    public function send(string $content): void
    {
        throw_if($this->channel === null, \LogicException::class, 'to() でチャンネルを指定してください。');

        $this->message->content = $content;

        $url = $this->channel->webhookUrl();

        if (empty($this->message->files)) {
            $this->sendJson($url);
        } else {
            $this->sendMultipart($url);
        }
    }

    private function sendJson(string $url): void
    {
        $response = Http::post($url, $this->message->toPayload());
        $this->throwIfFailed($response);
    }

    private function sendMultipart(string $url): void
    {
        $request = Http::asMultipart();

        foreach ($this->message->files as $index => $file) {
            $request = $request->attach(
                "files[{$index}]",
                file_get_contents($file['path']),
                $file['name']
            );
        }

        $response = $request->post($url, [
            [
                'name'     => 'payload_json',
                'contents' => json_encode($this->message->toPayload()),
            ],
        ]);

        $this->throwIfFailed($response);
    }

    private function throwIfFailed(\Illuminate\Http\Client\Response $response): void
    {
        if ($response->failed()) {
            throw new \RuntimeException(
                "Discord Webhook 送信失敗: HTTP {$response->status()} - {$response->body()}"
            );
        }
    }
}
