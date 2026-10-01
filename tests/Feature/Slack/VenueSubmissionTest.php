<?php

use App\Enums\VenueType;
use App\Models\Venue;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    fakeSlackConfig();
    Http::preventStrayRequests();
    Http::fake(['hooks.slack.com/*' => Http::response('ok')]);
    $this->withoutDefer();
});

/**
 * Construit le payload d'un view_submission de la modale venue_create.
 *
 * @param  array<string, string|null>  $fields
 * @return array{payload: string}
 */
function venueSubmission(array $fields): array
{
    $values = [];

    foreach (['name', 'city', 'capacity'] as $field) {
        $values[$field][$field] = ['type' => 'plain_text_input', 'value' => $fields[$field] ?? null];
    }

    $values['type']['type'] = [
        'type' => 'static_select',
        'selected_option' => isset($fields['type']) ? ['value' => $fields['type']] : null,
    ];

    return ['payload' => json_encode([
        'type' => 'view_submission',
        'user' => ['id' => 'U123'],
        'view' => ['callback_id' => 'venue_create', 'state' => ['values' => $values]],
    ])];
}

it('crée le lieu, ferme la modale et prévient le canal', function () {
    postFromSlack('/slack/interact', venueSubmission([
        'name' => '  Le Ferrailleur ',
        'city' => 'Nantes',
        'type' => VenueType::Club->value,
        'capacity' => '400',
    ]))
        ->assertOk()
        ->assertContent('');

    $venue = Venue::sole();
    expect($venue->name)->toBe('Le Ferrailleur')
        ->and($venue->city)->toBe('Nantes')
        ->and($venue->type)->toBe(VenueType::Club)
        ->and($venue->capacity)->toBe(400);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://hooks.slack.com/services/T000/B000/test'
        && $request['text'] === '📍 Nouveau lieu ajouté par <@U123> : *Le Ferrailleur* (Nantes)');
});

it('accepte un lieu sans jauge', function () {
    postFromSlack('/slack/interact', venueSubmission([
        'name' => 'Le Bar du coin',
        'city' => 'Rennes',
        'type' => VenueType::Bar->value,
    ]))->assertOk();

    expect(Venue::sole()->capacity)->toBeNull();
});

it('renvoie les erreurs dans la modale sans rien créer', function () {
    postFromSlack('/slack/interact', venueSubmission([
        'name' => '   ',
        'city' => '',
        'type' => VenueType::Club->value,
        'capacity' => '-12',
    ]))
        ->assertOk()
        ->assertExactJson([
            'response_action' => 'errors',
            'errors' => [
                'name' => 'Le nom est obligatoire.',
                'city' => 'La ville est obligatoire.',
                'capacity' => 'La jauge doit être supérieure à zéro.',
            ],
        ]);

    expect(Venue::count())->toBe(0);
    Http::assertNothingSent();
});

it('refuse une jauge non entière', function (string $capacity) {
    postFromSlack('/slack/interact', venueSubmission([
        'name' => 'Le Ferrailleur',
        'city' => 'Nantes',
        'type' => VenueType::Club->value,
        'capacity' => $capacity,
    ]))->assertJsonPath('errors.capacity', 'La jauge doit être un nombre entier.');

    expect(Venue::count())->toBe(0);
})->with(['12.5', 'beaucoup']);
