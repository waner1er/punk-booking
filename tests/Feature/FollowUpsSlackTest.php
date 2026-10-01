<?php

use App\Enums\GigStatus;
use App\Models\Gig;
use App\Models\Venue;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    fakeSlackConfig();
    Http::preventStrayRequests();
    Http::fake(['hooks.slack.com/*' => Http::response('ok')]);
});

it('poste le récap des relances avec --slack', function () {
    Gig::factory()
        ->for(Venue::factory()->state(['name' => 'Le Ferrailleur', 'city' => 'Nantes']))
        ->create(['status' => GigStatus::Contacted, 'next_follow_up_at' => today()->subDays(3)]);
    Gig::factory()->create(['status' => GigStatus::Option, 'next_follow_up_at' => today()]);
    // Pas encore à relancer, ou déjà confirmé : exclus du récap.
    Gig::factory()->create(['status' => GigStatus::Contacted, 'next_follow_up_at' => today()->addWeek()]);
    Gig::factory()->createQuietly(['status' => GigStatus::Confirmed, 'next_follow_up_at' => today()]);

    Http::assertNothingSent();

    $this->artisan('booking:follow-ups', ['--slack' => true])->assertSuccessful();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_contains($request['text'], '2 relances à faire')
        && str_contains($request['text'], '*Le Ferrailleur* (Nantes)')
        && str_contains($request['text'], 'en retard'));
});

it('n\'envoie rien sans l\'option --slack', function () {
    Gig::factory()->create(['status' => GigStatus::Contacted, 'next_follow_up_at' => today()]);

    $this->artisan('booking:follow-ups')->assertSuccessful();

    Http::assertNothingSent();
});

it('n\'envoie rien quand il n\'y a pas de relance', function () {
    $this->artisan('booking:follow-ups', ['--slack' => true])
        ->expectsOutputToContain('Rien à relancer')
        ->assertSuccessful();

    Http::assertNothingSent();
});
