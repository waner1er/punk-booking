#!/usr/bin/env bash
# =============================================================================
#  Outil de booking — génération du socle (Laravel 13 + Filament v5)
#
#  Usage : à la racine du projet, Sail démarré (sail up -d), puis :
#          bash setup-booking.sh
#
#  Tout passe par les commandes console (sail artisan make:*), puis le script
#  remplit le contenu des fichiers générés.
# =============================================================================
set -euo pipefail

# Un alias n'est pas lu dans un script non interactif : on redéclare "sail"
# en fonction pour pouvoir écrire "sail artisan ..." partout, comme en terminal.
sail() { ./vendor/bin/sail "$@"; }

step()      { printf '\n\033[1;33m▶ %s\033[0m\n' "$1"; }
migration() { ls database/migrations/*_"$1".php | tail -n 1; }

[ -x ./vendor/bin/sail ] || { echo "✗ Lance ce script à la racine du projet Laravel."; exit 1; }
[ ! -e app/Models/Gig.php ] || { echo "✗ app/Models/Gig.php existe déjà : script déjà lancé ?"; exit 1; }

# -----------------------------------------------------------------------------
step "1/8 · Enums"
# -----------------------------------------------------------------------------
for enum in GigStatus DealType VenueType InteractionType; do
    sail artisan make:enum "Enums/$enum"
done

cat > app/Enums/GigStatus.php <<'PHP'
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GigStatus: string implements HasColor, HasLabel
{
    case Prospect = 'prospect';
    case Contacted = 'contacted';
    case Discussing = 'discussing';
    case Option = 'option';
    case Confirmed = 'confirmed';
    case Played = 'played';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Prospect => 'Piste',
            self::Contacted => 'Contacté',
            self::Discussing => 'En discussion',
            self::Option => 'Option posée',
            self::Confirmed => 'Confirmé',
            self::Played => 'Joué',
            self::Declined => 'Refusé',
            self::Cancelled => 'Annulé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Prospect => 'gray',
            self::Contacted, self::Discussing => 'info',
            self::Option => 'warning',
            self::Confirmed, self::Played => 'success',
            self::Declined, self::Cancelled => 'danger',
        };
    }

    /** Statuts « en cours » : ceux qu'on peut encore relancer. */
    public static function open(): array
    {
        return [
            self::Prospect->value,
            self::Contacted->value,
            self::Discussing->value,
            self::Option->value,
        ];
    }
}
PHP

cat > app/Enums/DealType.php <<'PHP'
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DealType: string implements HasLabel
{
    case Fixed = 'fixed';
    case DoorDeal = 'door_deal';
    case Hat = 'hat';
    case Expenses = 'expenses';
    case Free = 'free';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => 'Cachet fixe',
            self::DoorDeal => '% des entrées',
            self::Hat => 'Au chapeau',
            self::Expenses => 'Défraiement',
            self::Free => 'Gratuit / soutien',
        };
    }
}
PHP

cat > app/Enums/VenueType.php <<'PHP'
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VenueType: string implements HasLabel
{
    case Club = 'club';
    case Bar = 'bar';
    case Squat = 'squat';
    case Festival = 'festival';
    case Association = 'association';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Club => 'Salle',
            self::Bar => 'Bar',
            self::Squat => 'Squat / lieu autogéré',
            self::Festival => 'Festival',
            self::Association => 'Asso',
            self::Other => 'Autre',
        };
    }
}
PHP

cat > app/Enums/InteractionType.php <<'PHP'
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InteractionType: string implements HasLabel
{
    case Email = 'email';
    case Call = 'call';
    case Message = 'message';
    case Meeting = 'meeting';

    public function getLabel(): string
    {
        return match ($this) {
            self::Email => 'Mail',
            self::Call => 'Appel',
            self::Message => 'Message',
            self::Meeting => 'Rencontre',
        };
    }
}
PHP

# -----------------------------------------------------------------------------
step "2/8 · Modèles, migrations et factories"
# -----------------------------------------------------------------------------
# Le sleep garantit des horodatages distincts : sinon des migrations créées dans
# la même seconde passent dans l'ordre alphabétique et les clés étrangères cassent.
for model in Venue Contact Tour Gig Interaction Unavailability; do
    sail artisan make:model "$model" -mf
    sleep 1
done
sail artisan make:migration create_contact_venue_table

# --- Migrations ---------------------------------------------------------------
cat > "$(migration create_venues_table)" <<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('club');
            $table->string('city')->nullable()->index();
            $table->string('address')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
PHP

cat > "$(migration create_contacts_table)" <<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->string('organization')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
PHP

cat > "$(migration create_tours_table)" <<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
PHP

cat > "$(migration create_gigs_table)" <<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gigs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tour_id')->nullable()->constrained()->nullOnDelete();

            $table->date('date')->nullable()->index();
            $table->time('load_in_at')->nullable();
            $table->time('set_time')->nullable();
            $table->unsignedSmallInteger('set_duration')->nullable()->comment('minutes');

            $table->string('status')->default('prospect')->index();

            $table->string('deal_type')->nullable();
            $table->decimal('fee', 8, 2)->nullable();
            $table->decimal('travel_costs', 8, 2)->nullable();
            $table->boolean('accommodation')->default(false);
            $table->boolean('meals')->default(false);

            $table->date('next_follow_up_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gigs');
    }
};
PHP

cat > "$(migration create_interactions_table)" <<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('email');
            $table->dateTime('happened_at');
            $table->text('summary');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
PHP

cat > "$(migration create_unavailabilities_table)" <<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unavailabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unavailabilities');
    }
};
PHP

cat > "$(migration create_contact_venue_table)" <<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_venue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['contact_id', 'venue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_venue');
    }
};
PHP

# --- Modèles ------------------------------------------------------------------
cat > app/Models/Venue.php <<'PHP'
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
PHP

cat > app/Models/Contact.php <<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'phone', 'role', 'organization', 'notes'])]
class Contact extends Model
{
    use HasFactory;

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class)->withTimestamps();
    }

    public function gigs(): HasMany
    {
        return $this->hasMany(Gig::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }
}
PHP

cat > app/Models/Tour.php <<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'starts_at', 'ends_at', 'notes'])]
class Tour extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function gigs(): HasMany
    {
        return $this->hasMany(Gig::class);
    }
}
PHP

cat > app/Models/Gig.php <<'PHP'
<?php

namespace App\Models;

use App\Enums\DealType;
use App\Enums\GigStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
PHP

cat > app/Models/Interaction.php <<'PHP'
<?php

namespace App\Models;

use App\Enums\InteractionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gig_id', 'contact_id', 'type', 'happened_at', 'summary'])]
class Interaction extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => InteractionType::class,
            'happened_at' => 'datetime',
        ];
    }

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
PHP

cat > app/Models/Unavailability.php <<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'starts_at', 'ends_at', 'reason'])]
class Unavailability extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
PHP

# -----------------------------------------------------------------------------
step "3/8 · Commandes console"
# -----------------------------------------------------------------------------
sail artisan make:command MakeAdmin
sail artisan make:command ListFollowUps

cat > app/Console/Commands/MakeAdmin.php <<'PHP'
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'booking:make-admin
                            {email : Email du compte}
                            {--revoke : Retire les droits admin au lieu de les donner}';

    protected $description = 'Donne ou retire l\'accès au panel Filament à un utilisateur';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Aucun utilisateur avec cet email.');

            return self::FAILURE;
        }

        $isAdmin = ! $this->option('revoke');

        // is_admin n'est volontairement pas fillable : on passe par forceFill.
        $user->forceFill(['is_admin' => $isAdmin])->save();

        $this->info($isAdmin
            ? "{$user->email} a maintenant accès au panel."
            : "{$user->email} n'a plus accès au panel.");

        return self::SUCCESS;
    }
}
PHP

cat > app/Console/Commands/ListFollowUps.php <<'PHP'
<?php

namespace App\Console\Commands;

use App\Models\Gig;
use Illuminate\Console\Command;

class ListFollowUps extends Command
{
    protected $signature = 'booking:follow-ups';

    protected $description = 'Liste les dates à relancer aujourd\'hui ou en retard';

    public function handle(): int
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

        return self::SUCCESS;
    }
}
PHP

# -----------------------------------------------------------------------------
step "4/8 · Migration de la base"
# -----------------------------------------------------------------------------
sail artisan migrate

# -----------------------------------------------------------------------------
step "5/8 · Resources Filament (générées depuis la base)"
# -----------------------------------------------------------------------------
sail artisan make:filament-resource Venue --generate --record-title-attribute=name
sail artisan make:filament-resource Contact --generate --record-title-attribute=name
sail artisan make:filament-resource Tour --generate --record-title-attribute=name
sail artisan make:filament-resource Gig --generate
sail artisan make:filament-resource Unavailability --generate

# -----------------------------------------------------------------------------
step "6/8 · Relation managers"
# -----------------------------------------------------------------------------
sail artisan make:filament-relation-manager VenueResource gigs date
sail artisan make:filament-relation-manager VenueResource contacts name --attach
sail artisan make:filament-relation-manager GigResource interactions summary
sail artisan make:filament-relation-manager TourResource gigs date

# -----------------------------------------------------------------------------
step "7/8 · Widgets du dashboard"
# -----------------------------------------------------------------------------
sail artisan make:filament-widget BookingStats --stats-overview --panel=admin
sail artisan make:filament-widget GigsToFollowUp --table --panel=admin
sail artisan make:filament-widget UpcomingGigs --table --panel=admin

cat > app/Filament/Widgets/BookingStats.php <<'PHP'
<?php

namespace App\Filament\Widgets;

use App\Enums\GigStatus;
use App\Models\Gig;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make(
                'Dates confirmées à venir',
                Gig::where('status', GigStatus::Confirmed)->whereDate('date', '>=', today())->count(),
            )->color('success'),

            Stat::make('Pistes en cours', Gig::open()->count())->color('info'),

            Stat::make('À relancer', Gig::toFollowUp()->count())->color('warning'),
        ];
    }
}
PHP

cat > app/Filament/Widgets/GigsToFollowUp.php <<'PHP'
<?php

namespace App\Filament\Widgets;

use App\Models\Gig;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class GigsToFollowUp extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('À relancer')
            ->query(Gig::query()->toFollowUp()->with(['venue', 'contact']))
            ->defaultSort('next_follow_up_at')
            ->columns([
                TextColumn::make('next_follow_up_at')->label('Relance')->date('d/m/Y')->sortable(),
                TextColumn::make('venue.name')->label('Lieu'),
                TextColumn::make('venue.city')->label('Ville'),
                TextColumn::make('contact.name')->label('Contact')->placeholder('—'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('date')->label('Date visée')->date('d/m/Y')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('postpone')
                    ->label('+15 jours')
                    ->icon('heroicon-o-clock')
                    ->action(fn (Gig $record) => $record->postponeFollowUp(15)),
            ])
            ->emptyStateHeading('Rien à relancer');
    }
}
PHP

cat > app/Filament/Widgets/UpcomingGigs.php <<'PHP'
<?php

namespace App\Filament\Widgets;

use App\Enums\GigStatus;
use App\Models\Gig;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingGigs extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Prochaines dates')
            ->query(
                Gig::query()
                    ->where('status', GigStatus::Confirmed)
                    ->whereDate('date', '>=', today())
                    ->with('venue')
            )
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')->date('d/m/Y')->sortable(),
                TextColumn::make('venue.name')->label('Lieu'),
                TextColumn::make('venue.city')->label('Ville'),
                TextColumn::make('set_time')->label('Set')->time('H:i')->placeholder('—'),
                TextColumn::make('fee')->label('Cachet')->money('EUR')->placeholder('—'),
                IconColumn::make('accommodation')->label('Hébergé')->boolean(),
            ])
            ->emptyStateHeading('Aucune date confirmée');
    }
}
PHP

# -----------------------------------------------------------------------------
step "8/8 · Formatage et caches"
# -----------------------------------------------------------------------------
sail pint --dirty || true
sail artisan optimize:clear

printf '\n\033[1;32m✔ Socle généré.\033[0m\n'
cat <<'TXT'

À faire à la main :
  1. Enregistrer les relation managers dans getRelations() de chaque Resource.
  2. Dans les formulaires générés, remplacer les TextInput de status, deal_type,
     type par des Select::make('status')->options(GigStatus::class).
  3. Promouvoir ton compte :  sail artisan booking:make-admin ton@email.fr
  4. Voir les relances en terminal :  sail artisan booking:follow-ups
TXT
