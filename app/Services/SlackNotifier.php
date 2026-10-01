<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Envoie des messages dans le canal de booking via l'Incoming Webhook Slack.
 */
class SlackNotifier
{
    public function isConfigured(): bool
    {
        return filled(config('services.slack.booking_webhook'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks  Blocs Block Kit (le texte sert alors de repli pour les notifications)
     */
    public function send(string $text, array $blocks = []): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $payload = ['text' => $text];

        if ($blocks !== []) {
            $payload['blocks'] = $blocks;
        }

        try {
            Http::timeout(5)
                ->post(config('services.slack.booking_webhook'), $payload)
                ->throw();
        } catch (Throwable $e) {
            // Slack indisponible ne doit jamais faire échouer une action métier.
            report($e);
        }
    }

    /** Échappe les caractères réservés du format mrkdwn de Slack. */
    public static function escape(string $text): string
    {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $text);
    }
}
