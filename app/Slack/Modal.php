<?php

namespace App\Slack;

use Symfony\Component\HttpFoundation\Response;

/**
 * Une modale Slack, enregistrée par son callback_id dans SlackController::MODALS.
 */
interface Modal
{
    /**
     * Vue Block Kit passée à views.open.
     *
     * @return array<string, mixed>
     */
    public function view(): array;

    /**
     * Traite le view_submission. Réponse vide 200 pour fermer la modale,
     * ou `response_action: errors` pour afficher des erreurs dans la modale.
     *
     * @param  array<string, mixed>  $payload
     */
    public function submit(array $payload): Response;
}
