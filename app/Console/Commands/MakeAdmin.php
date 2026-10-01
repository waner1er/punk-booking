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
