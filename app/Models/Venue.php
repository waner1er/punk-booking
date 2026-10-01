<?php

namespace App\Models;

use App\Enums\VenueType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'type', 'city', 'address', 'capacity', 'website', 'notes'])]
class Venue extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => VenueType::class,
            'capacity' => 'integer',
        ];
    }

    public function gigs(): HasMany
    {
        return $this->hasMany(Gig::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)->withTimestamps();
    }
}
