<?php

namespace LaravelUpdraft\Console\Commands;

use Illuminate\Console\Command;
use LaravelUpdraft\UpdateService;
use LaravelUpdraft\Models\UpdateHistory;

class RollbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updraft:rollback
                            {backupId? : The backup ID to rollback to. If not provided, will use the most recent backup}
                            {--force : Force the rollback without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback to a previous version using a backup';

    /**
     * Execute the console command.
     */
    public function handle(UpdateService $updateService)
    {
        $this->info('Laravel Updraft - Rollback');
        $this->line('---------------------------');
        
        // Get the backup ID to rollback to
        $backupId = $this->argument('backupId');
        
        if (!$backupId) {
            // If no backup ID provided, list available backups and ask user to select one
            $backupId = $this->askForBackupId();
            
            if (!$backupId) {
                $this->error('Rollback cancelled.');
                return 1;
            }
        }
        
        // Verify the backup exists
        if (!$updateService->backupExists($backupId)) {
            $this->error("Backup ID not found: {$backupId}");
            return 1;
        }
        
        // Get backup info to show user what they're rolling back to
        $backupInfo = $updateService->getBackupInfo($backupId);
        
        $this->info("You are about to rollback to:");
        $this->line("Version: " . ($backupInfo['version'] ?? 'Unknown'));
        $this->line("Timestamp: " . date('Y-m-d H:i:s', $backupInfo['timestamp'] ?? 0));
        
        // Confirm rollback unless --force is used
        if (!$this->option('force') && !$this->confirm('Are you sure you want to rollback? This will revert all file changes made since this backup was created.', false)) {
            $this->info('Rollback cancelled.');
            return 0;
        }
        
        $this->info('Starting rollback...');
        
        // Start a progress bar
        $this->output->progressStart(3);
        
        // Create a backup of current state
        $this->output->progressAdvance();
        $this->line('Creating safety backup of current state...');
        
        // Restore files
        $this->output->progressAdvance();
        $this->line('Restoring files from backup...');
        
        // Update database records
        $this->output->progressAdvance();
        $this->line('Updating application state...');
        
        // Process the rollback
        try {
            $result = $updateService->rollbackToBackup($backupId);
            
            $this->output->progressFinish();
            
            if ($result) {
                $this->info('Rollback successfully completed!');
                return 0;
            } else {
                $this->error('Rollback failed. Check the logs for more information.');
                return 1;
            }
        } catch (\Exception $e) {
            $this->output->progressFinish();
            $this->error('Rollback failed: ' . $e->getMessage());
            $this->line('Check the logs for more information.');
            return 1;
        }
    }
    
    /**
     * Ask the user to select a backup ID
     */
    protected function askForBackupId(): ?string
    {
        // Get list of successful updates with backups
        $updates = UpdateHistory::where('successful', true)
            ->whereNotNull('backup_id')
            ->orderBy('applied_at', 'desc')
            ->get();
            
        if ($updates->isEmpty()) {
            $this->error('No backups available for rollback.');
            return null;
        }
        
        $this->info('Available backups:');
        
        // Display a table of available backups
        $rows = [];
        foreach ($updates as $index => $update) {
            $rows[] = [
                $index + 1,
                $update->version,
                $update->name,
                $update->applied_at->format('Y-m-d H:i:s'),
                $update->backup_id
            ];
        }
        
        $this->table(
            ['#', 'Version', 'Name', 'Applied At', 'Backup ID'],
            $rows
        );
        
        $choice = $this->ask('Enter the number of the backup to rollback to (or 0 to cancel)');
        
        if (!is_numeric($choice) || (int) $choice <= 0 || (int) $choice > count($rows)) {
            return null;
        }
        
        // Return the selected backup ID
        return $updates[(int) $choice - 1]->backup_id;
    }
}
