<?php

use App\Enums\DealType;
use App\Enums\GigStatus;
use App\Models\Gig;
use App\Models\Unavailability;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    fakeSlackConfig();
    Http::preventStrayRequests();
    Http::fake(['hooks.slack.com/*' => Http::response('ok')]);

    $this->gig = Gig::factory()
        ->for(Venue::factory()->state(['name' => 'Le Ferrailleur', 'city' => 'Nantes']))
        ->create([
            'status' => GigStatus::Option,
            'date' => '2026-11-14',
            'deal_type' => DealType::Fixed,
            'fee' => 300,
        ]);
});

it('annonce la date quand elle passe à Confirmé', function () {
    $this->gig->update(['status' => GigStatus::Confirmed]);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_contains($request['text'], 'Le Ferrailleur')
        && str_contains($request['text'], 'Nantes')
        && str_contains($request['text'], '14/11/2026')
        && str_contains($request['text'], 'Cachet fixe · 300,00 €'));
});

it('n\'annonce rien pour un autre changement de statut', function () {
    $this->gig->update(['status' => GigStatus::Declined]);

    Http::assertNothingSent();
});

it('n\'annonce pas deux fois une date déjà confirmée', function () {
    $this->gig->update(['status' => GigStatus::Confirmed]);
    $this->gig->update(['notes' => 'Balances à 17h']);

    Http::assertSentCount(1);
});

it('annonce un gig créé directement confirmé', function () {
    Gig::factory()->create(['status' => GigStatus::Confirmed]);

    Http::assertSentCount(1);
});

it('alerte si un membre est indisponible à la date du gig', function () {
    Unavailability::factory()
        ->for(User::factory()->state(['name' => 'Jo']))
        ->create(['starts_at' => '2026-11-10', 'ends_at' => '2026-11-16']);
    Unavailability::factory()
        ->for(User::factory()->state(['name' => 'Max']))
        ->create(['starts_at' => '2026-11-14', 'ends_at' => '2026-11-14']);
    Unavailability::factory()
        ->for(User::factory()->state(['name' => 'Lou']))
        ->create(['starts_at' => '2026-11-15', 'ends_at' => '2026-11-20']);

    $this->gig->update(['status' => GigStatus::Confirmed]);

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request) => str_starts_with($request['text'], '⚠️')
        && str_contains($request['text'], 'Jo et Max sont indisponibles')
        && ! str_contains($request['text'], 'Lou'));
});

it('n\'alerte pas sans chevauchement', function () {
    Unavailability::factory()->create(['starts_at' => '2026-12-01', 'ends_at' => '2026-12-05']);

    $this->gig->update(['status' => GigStatus::Confirmed]);

    Http::assertSentCount(1);
});

it('ne plante pas si le webhook n\'est pas configuré', function () {
    config(['services.slack.booking_webhook' => null]);

    $this->gig->update(['status' => GigStatus::Confirmed]);

    expect($this->gig->fresh()->status)->toBe(GigStatus::Confirmed);
    Http::assertNothingSent();
});
