<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take a database backup and cleanup old ones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database backup...');

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');

        $filename = "backup_" . now()->format('Y-m-d_H-i-s') . ".sql";
        $backupPath = storage_path('app/backups');

        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $filePath = $backupPath . '/' . $filename;

        // Ensure mysqldump is available and build command
        // On Windows with XAMPP, mysqldump is usually in xampp/mysql/bin/mysqldump.exe
        // We'll try to use 'mysqldump' directly if it's in the PATH,
        // otherwise we might need the full path.
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($database),
            escapeshellarg($filePath)
        );

        $this->info("Executing command: mysqldump ...");

        try {
            // Using shell_exec or Process for better control.
            // Note: system() or exec() might be blocked depending on php.ini
            $result = null;
            $output = null;
            exec($command, $output, $result);

            if ($result === 0) {
                $this->info("Backup successfully saved to {$filePath}");
                Log::info("Database backup successful: {$filename}");
            } else {
                $this->error("Backup failed with exit code {$result}");
                Log::error("Database backup failed: {$filename} (Exit code: {$result})");
            }
        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            Log::error("Database backup error: " . $e->getMessage());
        }

        $this->cleanup();
    }

    /**
     * Cleanup backups older than 24 hours.
     */
    protected function cleanup()
    {
        $this->info('Starting cleanup of old backups...');
        $backupPath = storage_path('app/backups');

        if (!File::exists($backupPath)) {
            return;
        }

        $files = File::files($backupPath);
        $now = now();
        $count = 0;

        foreach ($files as $file) {
            // Check individual file modification time
            if ($now->diffInHours($now->createFromTimestamp($file->getMTime())) >= 24) {
                File::delete($file->getPathname());
                $this->info("Deleted old backup: " . $file->getFilename());
                Log::info("Deleted old database backup: " . $file->getFilename());
                $count++;
            }
        }

        $this->info("Cleanup finished. Deleted {$count} files.");
    }
}
