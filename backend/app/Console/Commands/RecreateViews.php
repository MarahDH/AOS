<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RecreateViews extends Command
{
    protected $signature = 'views:recreate {--force : Force recreation without confirmation}';
    protected $description = 'Safely recreate database views without affecting table data';

    public function handle()
    {
        $this->info('🔄 Database View Recreation Tool');
        $this->newLine();
        
        $this->warn('⚠️  IMPORTANT INFORMATION:');
        $this->line('  • This will recreate VIEW definitions only');
        $this->line('  • Your TABLE data will NOT be affected');
        $this->line('  • Existing offers data remains untouched');
        $this->line('  • Only calculated values (from views) may change if formulas were modified');
        $this->newLine();
        
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to continue?', true)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }
        
        $this->newLine();
        $this->info('Step 1: Recreating offers_calculated_temp views...');
        
        try {
            // Read and execute the temp view migration
            $tempViewPath = database_path('migrations/2025_03_30_172628_create_offers_calculated_temp_view.php');
            if (!File::exists($tempViewPath)) {
                $this->error("Migration file not found: {$tempViewPath}");
                return 1;
            }
            
            require $tempViewPath;
            $migration = new \CreateOffersCalculatedTempView();
            $migration->up();
            $this->info('   ✅ Temp views created successfully');
            
        } catch (\Exception $e) {
            $this->error('   ❌ Failed to create temp views: ' . $e->getMessage());
            return 1;
        }
        
        $this->newLine();
        $this->info('Step 2: Recreating offers_calculated view...');
        
        try {
            // Read and execute the main view migration
            $mainViewPath = database_path('migrations/2025_09_02_085908_restore_offers_calculated_view_complete.php');
            if (!File::exists($mainViewPath)) {
                $this->error("Migration file not found: {$mainViewPath}");
                return 1;
            }
            
            // Load the migration file (it returns an anonymous class)
            $migration = require $mainViewPath;
            $migration->up();
            
            $this->info('   ✅ Main calculated view created successfully');
            
        } catch (\Exception $e) {
            $this->error('   ❌ Failed to create main view: ' . $e->getMessage());
            return 1;
        }
        
        $this->newLine();
        $this->info('✅ All views recreated successfully!');
        $this->info('📊 Your table data is completely untouched.');
        $this->warn('⚠️  Calculated values may change if formulas were modified.');
        
        return 0;
    }
}

