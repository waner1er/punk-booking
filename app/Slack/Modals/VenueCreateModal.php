<?php

namespace App\Slack\Modals;

use App\Enums\VenueType;
use App\Models\Venue;
use App\Services\SlackNotifier;
use App\Slack\Modal;
use App\Slack\ViewState;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

// Import explicite : l'extension Swoole (image Sail) déclare un defer() global qui masquerait celui de Laravel.
use function Illuminate\Support\defer;

/**
 * Modale « Nouveau lieu », ouverte par /lieu.
 */
class VenueCreateModal implements Modal
{
    public const CALLBACK_ID = 'venue_create';

    public function __construct(private readonly SlackNotifier $notifier) {}

    public function view(): array
    {
        return [
            'type' => 'modal',
            'callback_id' => self::CALLBACK_ID,
            'title' => $this->plainText('Nouveau lieu'),
            'submit' => $this->plainText('Ajouter'),
            'close' => $this->plainText('Annuler'),
            'blocks' => [
                $this->textInput('name', 'Nom', 'Le Ferrailleur'),
                $this->textInput('city', 'Ville', 'Nantes'),
                $this->typeSelect(),
                $this->textInput('capacity', 'Jauge', '250', optional: true),
            ],
        ];
    }

    public function submit(array $payload): Response
    {
        $state = ViewState::fromPayload($payload);

        $data = [
            'name' => $state->get('name'),
            'city' => $state->get('city'),
            'type' => $state->get('type') ?? VenueType::Club->value,
            'capacity' => $state->get('capacity'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(VenueType::class)],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'name.max' => 'Le nom est trop long (255 caractères max).',
            'city.required' => 'La ville est obligatoire.',
            'city.max' => 'La ville est trop longue (255 caractères max).',
            'type' => 'Type de lieu inconnu.',
            'capacity.integer' => 'La jauge doit être un nombre entier.',
            'capacity.min' => 'La jauge doit être supérieure à zéro.',
        ]);

        if ($validator->fails()) {
            // Les clés correspondent aux block_id : Slack affiche chaque erreur sous son champ.
            return response()->json([
                'response_action' => 'errors',
                'errors' => array_map(fn (array $messages) => $messages[0], $validator->errors()->messages()),
            ]);
        }

        $venue = Venue::create([
            ...$data,
            'capacity' => $data['capacity'] === null ? null : (int) $data['capacity'],
        ]);

        $userId = (string) ($payload['user']['id'] ?? '');

        // Envoyé après la réponse HTTP : la modale se ferme sans attendre le webhook.
        defer(fn () => $this->notifier->send(sprintf(
            '📍 Nouveau lieu ajouté par <@%s> : *%s* (%s)',
            $userId,
            SlackNotifier::escape($venue->name),
            SlackNotifier::escape($venue->city),
        )));

        return response('');
    }

    /**
     * @return array<string, mixed>
     */
    private function typeSelect(): array
    {
        $options = array_map(fn (VenueType $type) => [
            'text' => $this->plainText($type->getLabel()),
            'value' => $type->value,
        ], VenueType::cases());

        return [
            'type' => 'input',
            'block_id' => 'type',
            'label' => $this->plainText('Type'),
            'element' => [
                'type' => 'static_select',
                'action_id' => 'type',
                'options' => $options,
                'initial_option' => $options[0],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function textInput(string $field, string $label, string $placeholder, bool $optional = false): array
    {
        return [
            'type' => 'input',
            'block_id' => $field,
            'optional' => $optional,
            'label' => $this->plainText($label),
            'element' => [
                'type' => 'plain_text_input',
                'action_id' => $field,
                'placeholder' => $this->plainText($placeholder),
            ],
        ];
    }

    /**
     * @return array{type: string, text: string}
     */
    private function plainText(string $text): array
    {
        return ['type' => 'plain_text', 'text' => $text];
    }
}
