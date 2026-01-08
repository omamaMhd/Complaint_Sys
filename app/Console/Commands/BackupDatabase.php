<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Create database backup';

    public function handle()
    {
        $dbHost = config('database.connections.mysql.host');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $date = Carbon::now()->format('Y-m-d_H-i-s');
        $fileName = "backup_{$dbName}_{$date}.sql";
        $backupDir = storage_path('app/backups');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $path = "{$backupDir}/{$fileName}";

   //     $command = "mysqldump -h{$dbHost} -u{$dbUser} -p{$dbPass} {$dbName} > {$path}";
        $mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe';

        $command = "\"{$mysqldump}\" -h{$dbHost} -u{$dbUser}  {$dbName} > \"{$path}\"";

        exec($command, $output, $status);

        if ($status !== 0) {
            $this->error('Backup failed');
            return Command::FAILURE;
        }

        $this->info('Backup created successfully');
        return Command::SUCCESS;
    }
}
