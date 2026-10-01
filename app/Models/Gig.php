<?php

namespace App\Models;

use App\Enums\DealType;
use App\Enums\GigStatus;
use App\Observers\GigObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'venue_id', 'contact_id', 'tour_id',
    'date', 'load_in_at', 'set_time', 'set_duration',
    'status', 'deal_type', 'fee', 'travel_costs', 'accommodation', 'meals',
    'next_follow_up_at', 'notes',
])]
#[ObservedBy(GigObserver::class)]
class Gig extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'next_follow_up_at' => 'date',
            'status' => GigStatus::class,
            'deal_type' => DealType::class,
            'fee' => 'decimal:2',
            'travel_costs' => 'decimal:2',
            'accommodation' => 'boolean',
            'meals' => 'boolean',
            'set_duration' => 'integer',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class)->latest('happened_at');
    }

    /** Pistes encore en cours (non confirmées, non closes). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', GigStatus::open());
    }

    /** Pistes en cours dont la date de relance est atteinte. */
    public function scopeToFollowUp(Builder $query): Builder
    {
        return $query->open()->whereDate('next_follow_up_at', '<=', today());
    }

    public function postponeFollowUp(int $days = 15): void
    {
        $this->update(['next_follow_up_at' => today()->addDays($days)]);
    }
}
