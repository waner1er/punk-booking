<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Client minimal de l'API Web Slack, authentifié avec le bot token.
 */
class SlackApi
{
    private const BASE_URL = 'https://slack.com/api/';

    /**
     * Ouvre une modale. Le trigger_id n'est valable que 3 secondes : à appeler tout de suite.
     *
     * @param  array<string, mixed>  $view
     */
    public function openView(string $triggerId, array $view): bool
    {
        return $this->call('views.open', [
            'trigger_id' => $triggerId,
            'view' => $view,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function call(string $method, array $payload): bool
    {
        $token = config('services.slack.bot_token');

        if (blank($token)) {
            report(new RuntimeException("Slack {$method} : SLACK_BOT_TOKEN n'est pas configuré."));

            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(3)
                ->asJson()
                ->post(self::BASE_URL.$method, $payload);
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        // L'API Web répond 200 même en cas d'erreur : le statut réel est dans « ok ».
        if ($response->json('ok') !== true) {
            report(new RuntimeException("Slack {$method} : ".$response->json('error', 'erreur inconnue')));

            return false;
        }

        return true;
    }
}
