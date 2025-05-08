<?php

namespace Espionage\ProjectUpdater\Console\Commands;

use Illuminate\Console\Command;
use Espionage\ProjectUpdater\UpdateService;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:update
                            {file? : Path to the update package ZIP file}
                            {--force : Force the update without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply an update package to the Laravel application';

    /**
     * Execute the console command.
     */
    public function handle(UpdateService $updateService)
    {
        $this->info('Laravel Project Updater');
        $this->line('-------------------------');
        
        // Get the path to the update file
        $file = $this->argument('file');
        
        if (!$file) {
            // If no file provided, ask to upload one
            $file = $this->askForUpdateFile();
        }
        
        if (!file_exists($file)) {
            $this->error("Update file not found: {$file}");
            return 1;
        }
        
        // Confirm update unless --force is used
        if (!$this->option('force') && !$this->confirm('Are you sure you want to apply this update? Make sure you have backed up your application first.', false)) {
            $this->info('Update cancelled.');
            return 0;
        }
        
        $this->info('Applying update...');
        
        // Start a progress bar
        $this->output->progressStart(6);
        
        // Extract update
        $this->output->progressAdvance();
        $this->line('Extracting update package...');
        
        // Validate package
        $this->output->progressAdvance();
        $this->line('Validating update package...');
        
        // Create backup
        $this->output->progressAdvance();
        $this->line('Creating backup...');
        
        // Apply updates
        $this->output->progressAdvance();
        $this->line('Applying file changes...');
        
        // Run migrations
        $this->output->progressAdvance();
        $this->line('Running database migrations...');
        
        // Run post-update commands
        $this->output->progressAdvance();
        $this->line('Running post-update commands...');
        
        // Process the update
        try {
            $result = $updateService->processUpdate($file);
            
            $this->output->progressFinish();
            
            if ($result) {
                $this->info('Update successfully applied!');
                return 0;
            } else {
                $this->error('Update failed. Check the logs for more information.');
                return 1;
            }
        } catch (\Exception $e) {
            $this->output->progressFinish();
            $this->error('Update failed: ' . $e->getMessage());
            $this->line('Check the logs for more information.');
            return 1;
        }
    }
    
    /**
     * Ask the user to upload an update file
     */
    protected function askForUpdateFile(): string
    {
        $this->info('No update file specified.');
        
        // In a real implementation, this could handle file uploads or allow selection
        // from a list of previously uploaded files
        return $this->ask('Please provide the full path to the update file');
    }
}