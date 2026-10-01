<?php

namespace App\Http\Controllers;

use App\Slack\Commands\OpenVenueModal;
use App\Slack\Modal;
use App\Slack\Modals\VenueCreateModal;
use App\Slack\SlashCommand;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Point d'entrée des requêtes Slack (signature vérifiée par VerifySlackSignature).
 * Pour ajouter une commande ou une modale : une classe + une ligne dans la table correspondante.
 */
class SlackController extends Controller
{
    /** @var array<string, class-string<SlashCommand>> */
    private const COMMANDS = [
        '/lieu' => OpenVenueModal::class,
    ];

    /** @var array<string, class-string<Modal>> */
    private const MODALS = [
        VenueCreateModal::CALLBACK_ID => VenueCreateModal::class,
    ];

    public function command(Request $request): Response
    {
        $handler = self::COMMANDS[$request->string('command')->toString()] ?? null;

        if ($handler === null) {
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => 'Commande inconnue 🤷',
            ]);
        }

        return app($handler)->handle($request->all());
    }

    public function interact(Request $request): Response
    {
        $payload = json_decode($request->string('payload')->toString(), true);

        if (! is_array($payload)) {
            return response('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return match ($payload['type'] ?? null) {
            'view_submission' => $this->submitView($payload),
            // Autres interactions (boutons, menus…) : simple accusé de réception pour l'instant.
            default => response(''),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function submitView(array $payload): Response
    {
        $handler = self::MODALS[$payload['view']['callback_id'] ?? ''] ?? null;

        if ($handler === null) {
            report(new RuntimeException('Modale Slack inconnue : '.($payload['view']['callback_id'] ?? '(vide)')));

            return response('');
        }

        return app($handler)->submit($payload);
    }
}
