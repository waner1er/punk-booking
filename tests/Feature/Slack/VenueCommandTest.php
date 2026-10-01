<?php

use App\Enums\VenueType;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    fakeSlackConfig();
    Http::preventStrayRequests();
});

it('ouvre la modale de création de lieu avec le trigger_id', function () {
    Http::fake(['slack.com/api/views.open' => Http::response(['ok' => true])]);

    postFromSlack('/slack/command', [
        'command' => '/lieu',
        'trigger_id' => '1234.5678.abcdef',
        'user_id' => 'U123',
    ])->assertOk();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://slack.com/api/views.open'
        && $request->hasHeader('Authorization', 'Bearer xoxb-test-token')
        && $request['trigger_id'] === '1234.5678.abcdef'
        && $request['view']['callback_id'] === 'venue_create'
        && collect($request['view']['blocks'])->pluck('block_id')->all() === ['name', 'city', 'type', 'capacity']);
});

it('propose tous les types de lieu dans le select', function () {
    Http::fake(['slack.com/api/views.open' => Http::response(['ok' => true])]);

    postFromSlack('/slack/command', ['command' => '/lieu', 'trigger_id' => 'x']);

    Http::assertSent(function (Request $request) {
        $options = collect($request['view']['blocks'])->firstWhere('block_id', 'type')['element']['options'];

        return collect($options)->pluck('text.text')->contains('Squat / lieu autogéré')
            && count($options) === count(VenueType::cases());
    });
});

it('prévient l\'utilisateur si Slack refuse d\'ouvrir la modale', function () {
    Http::fake(['slack.com/api/views.open' => Http::response(['ok' => false, 'error' => 'expired_trigger_id'])]);

    postFromSlack('/slack/command', ['command' => '/lieu', 'trigger_id' => 'expired'])
        ->assertOk()
        ->assertJson(['response_type' => 'ephemeral']);
});
