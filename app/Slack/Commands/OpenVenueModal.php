<?php

namespace App\Slack\Commands;

use App\Services\SlackApi;
use App\Slack\Modals\VenueCreateModal;
use App\Slack\SlashCommand;
use Symfony\Component\HttpFoundation\Response;

/**
 * /lieu : ouvre la modale de création d'un lieu.
 */
class OpenVenueModal implements SlashCommand
{
    public function __construct(
        private readonly SlackApi $slack,
        private readonly VenueCreateModal $modal,
    ) {}

    public function handle(array $payload): Response
    {
        $opened = $this->slack->openView((string) ($payload['trigger_id'] ?? ''), $this->modal->view());

        if (! $opened) {
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => "Impossible d'ouvrir le formulaire, réessaie dans un instant.",
            ]);
        }

        return response('');
    }
}
