<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportLegacyUsers extends Command
{
    protected $signature = 'legacy:import-users {--dry-run : No DB write, only show what would happen}';
    protected $description = 'Import legacy users from legacy DB into laravel users table (preserve IDs)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = DB::connection('legacy')->table('users')->get();

        if ($rows->isEmpty()) {
            $this->warn('No rows found in legacy.users');
            return self::SUCCESS;
        }

        $this->info("Found {$rows->count()} rows in legacy.users");

        $imported = 0;

        foreach ($rows as $row) {
            // Legacy-ben általában ID a kulcs
            $legacyId = (int) ($row->ID ?? $row->id ?? 0);
            if ($legacyId <= 0) {
                $this->warn('Skipping row with missing ID');
                continue;
            }

            // Próbáljuk kiszedni az emailt / nevet többféle lehetséges mezőből
            $email = $row->Email ?? $row->email ?? null;
            $name  = $row->Name ?? $row->name ?? ($email ? explode('@', $email)[0] : 'Legacy User');

            if (!$email) {
                // Laravel users.email általában unique + not null, ezért ha nincs email mező,
                // generálunk egy stabil placeholdert (később átírható)
                $email = "legacy{$legacyId}@local.test";
            }

            if ($dryRun) {
                $this->line("DRY RUN: would upsert user id={$legacyId}, email={$email}, name={$name}");
                continue;
            }

            // Upsert: megtartjuk az ID-t
            User::unguard();
            User::updateOrCreate(
                ['id' => $legacyId],
                [
                    'name' => $name,
                    'email' => $email,
                    // jelszót nem tudjuk biztosan visszafejteni, ezért reseteljük egy alapra
                    // (később csinálunk jelszó reset folyamatot)
                    'password' => Hash::make('ChangeMe123!'),
                ]
            );
            User::reguard();

            $imported++;
        }

        $this->info($dryRun ? 'Dry-run finished.' : "Imported/updated {$imported} users.");
        return self::SUCCESS;
    }
}
