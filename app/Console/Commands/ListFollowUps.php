<?php

namespace App\Console\Commands;

use App\Models\Gig;
use App\Services\SlackNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ListFollowUps extends Command
{
    protected $signature = 'booking:follow-ups
                            {--slack : Poste aussi le récap des relances dans Slack}';

    protected $description = 'Liste les dates à relancer aujourd\'hui ou en retard';

    public function handle(SlackNotifier $slack): int
    {
        $gigs = Gig::query()
            ->toFollowUp()
            ->with(['venue', 'contact'])
            ->orderBy('next_follow_up_at')
            ->get();

        if ($gigs->isEmpty()) {
            $this->info('Rien à relancer 🤘');

            return self::SUCCESS;
        }

        $this->table(
            ['Relance', 'Lieu', 'Ville', 'Contact', 'Statut', 'Date visée'],
            $gigs->map(fn (Gig $gig) => [
                $gig->next_follow_up_at->format('d/m/Y'),
                $gig->venue->name,
                $gig->venue->city ?? '—',
                $gig->contact?->name ?? '—',
                $gig->status->getLabel(),
                $gig->date?->format('d/m/Y') ?? '—',
            ])->all(),
        );

        if ($this->option('slack')) {
            $this->postToSlack($slack, $gigs);
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Gig>  $gigs
     */
    private function postToSlack(SlackNotifier $slack, Collection $gigs): void
    {
        if (! $slack->isConfigured()) {
            $this->warn('Webhook Slack non configuré (SLACK_BOOKING_WEBHOOK) : rien envoyé.');

            return;
        }

        $count = $gigs->count();
        $title = sprintf('🔔 *%d relance%s à faire*', $count, $count > 1 ? 's' : '');

        $lines = $gigs->map(fn (Gig $gig) => sprintf(
            '• *%s* (%s) — %s — %s — date visée : %s%s',
            SlackNotifier::escape($gig->venue->name),
            SlackNotifier::escape($gig->venue->city ?? '—'),
            SlackNotifier::escape($gig->contact?->name ?? 'pas de contact'),
            $gig->status->getLabel(),
            $gig->date?->format('d/m/Y') ?? '—',
            $gig->next_follow_up_at->isPast() && ! $gig->next_follow_up_at->isToday()
                ? ' _(en retard depuis le '.$gig->next_follow_up_at->format('d/m').')_'
                : '',
        ));

        $slack->send($title."\n".$lines->join("\n"));

        $this->info('Récap posté dans Slack.');
    }
}
