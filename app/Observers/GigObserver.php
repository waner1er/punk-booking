<?php

namespace App\Observers;

use App\Enums\GigStatus;
use App\Models\Gig;
use App\Models\Unavailability;
use App\Services\SlackNotifier;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Annonce dans Slack les dates confirmées et les conflits de disponibilité.
 * Déclenché après le commit : pas de message pour une sauvegarde annulée.
 */
class GigObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly SlackNotifier $slack) {}

    public function created(Gig $gig): void
    {
        if ($gig->status === GigStatus::Confirmed) {
            $this->announceConfirmation($gig);
        }
    }

    public function updated(Gig $gig): void
    {
        if ($gig->wasChanged('status') && $gig->status === GigStatus::Confirmed) {
            $this->announceConfirmation($gig);
        }
    }

    private function announceConfirmation(Gig $gig): void
    {
        $gig->loadMissing('venue');

        $venue = SlackNotifier::escape($gig->venue->name);
        $city = SlackNotifier::escape($gig->venue->city ?? '—');
        $date = $gig->date?->format('d/m/Y') ?? 'date à fixer';
        $deal = $this->describeDeal($gig);

        $this->slack->send(
            "🎉 Date confirmée : *{$venue}* ({$city}) le {$date} — {$deal}",
            [
                [
                    'type' => 'section',
                    'text' => ['type' => 'mrkdwn', 'text' => '🎉 *Date confirmée !*'],
                    'fields' => [
                        ['type' => 'mrkdwn', 'text' => "*Lieu*\n{$venue}"],
                        ['type' => 'mrkdwn', 'text' => "*Ville*\n{$city}"],
                        ['type' => 'mrkdwn', 'text' => "*Date*\n{$date}"],
                        ['type' => 'mrkdwn', 'text' => "*Cachet*\n{$deal}"],
                    ],
                ],
            ],
        );

        $this->warnAboutUnavailableMembers($gig);
    }

    private function warnAboutUnavailableMembers(Gig $gig): void
    {
        if ($gig->date === null) {
            return;
        }

        $members = Unavailability::query()
            ->with('user')
            ->whereDate('starts_at', '<=', $gig->date)
            ->whereDate('ends_at', '>=', $gig->date)
            ->get()
            ->map(fn (Unavailability $unavailability) => SlackNotifier::escape($unavailability->user->name))
            ->unique()
            ->sort()
            ->values();

        if ($members->isEmpty()) {
            return;
        }

        $verb = $members->count() > 1 ? 'sont indisponibles' : 'est indisponible';

        $this->slack->send(sprintf(
            '⚠️ Conflit de dispo pour *%s* (%s) le %s : %s %s ce jour-là.',
            SlackNotifier::escape($gig->venue->name),
            SlackNotifier::escape($gig->venue->city ?? '—'),
            $gig->date->format('d/m/Y'),
            $members->join(', ', ' et '),
            $verb,
        ));
    }

    private function describeDeal(Gig $gig): string
    {
        $fee = $gig->fee !== null
            ? number_format((float) $gig->fee, 2, ',', ' ').' €'
            : null;

        return collect([$gig->deal_type?->getLabel(), $fee])
            ->filter()
            ->whenEmpty(fn ($parts) => $parts->push('non précisé'))
            ->join(' · ');
    }
}
