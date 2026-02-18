<?php

namespace App\Console\Commands;

use App\Models\UserSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyUserSettings extends Command
{
    protected $signature = 'legacy:import-user-settings {--dry-run : No DB write, only show what would happen}';
    protected $description = 'Import legacy usersettings from legacy DB into laravel user_settings table';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $rows = DB::connection('legacy')
            ->table('usersettings')
            ->get();

        $this->info("Found {$rows->count()} rows in legacy.usersettings");

        $imported = 0;

        foreach ($rows as $row) {

            $userId = $row->UserID;

            $data = [
                'AutoLogin'               => $row->AutoLogin,
                'ReceiveNotifications'    => $row->ReceiveNotifications,
                'PreferredChartTheme'     => $row->PreferredChartTheme,
                'PreferredChartInterval'  => $row->PreferredChartInterval,
                'NewsLimit'               => $row->NewsLimit,
                'NewsPerSymbolLimit'      => $row->NewsPerSymbolLimit,
                'NewsPortfolioTotalLimit' => $row->NewsPortfolioTotalLimit,
                'CalendarLimit'           => $row->CalendarLimit,
            ];

            if (!$dryRun) {
                UserSetting::updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'timezone' => null,
                        'data' => $data,
                    ]
                );
            }

            $imported++;
        }

        $this->info($dryRun
            ? 'Dry-run finished.'
            : "Imported/updated {$imported} user_settings rows."
        );

        return self::SUCCESS;
    }

}
