<?php

namespace App\Slack;

use Symfony\Component\HttpFoundation\Response;

/**
 * Une slash command Slack (/lieu, /date…), enregistrée dans SlackController::COMMANDS.
 */
interface SlashCommand
{
    /**
     * Doit répondre en moins de 3 secondes.
     *
     * @param  array<string, mixed>  $payload  Champs envoyés par Slack (command, text, user_id, trigger_id…)
     */
    public function handle(array $payload): Response;
}
