<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OfferRawMaterialService;
use App\Models\Offer;

class SyncRawMaterialPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raw-materials:sync-prices {--offer-id= : Specific offer ID to sync} {--all : Sync all offers} {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync raw material prices from raw_materials table to offers_raw_materials table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $offerId = $this->option('offer-id');
        $syncAll = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        if ($offerId) {
            $this->syncSpecificOffer($offerId, $dryRun);
        } elseif ($syncAll) {
            $this->syncAllOffers($dryRun);
        } else {
            $this->error('Please specify either --offer-id=X or --all option');
            $this->info('Usage examples:');
            $this->info('  php artisan raw-materials:sync-prices --offer-id=1');
            $this->info('  php artisan raw-materials:sync-prices --all');
            $this->info('  php artisan raw-materials:sync-prices --all --dry-run');
            return 1;
        }

        return 0;
    }

    /**
     * Sync prices for a specific offer
     */
    private function syncSpecificOffer(int $offerId, bool $dryRun): void
    {
        $offer = Offer::find($offerId);
        
        if (!$offer) {
            $this->error("Offer with ID {$offerId} not found");
            return;
        }

        $this->info("📋 Syncing prices for Offer #{$offerId}: {$offer->general_offer_number}");
        
        if ($dryRun) {
            $this->showPriceChanges($offerId);
        } else {
            $this->performSync($offerId);
        }
    }

    /**
     * Sync prices for all offers
     */
    private function syncAllOffers(bool $dryRun): void
    {
        $offers = Offer::all();
        
        if ($offers->isEmpty()) {
            $this->info('No offers found');
            return;
        }

        $this->info("📋 Found {$offers->count()} offers to sync");
        
        $bar = $this->output->createProgressBar($offers->count());
        $bar->start();

        foreach ($offers as $offer) {
            if ($dryRun) {
                $this->showPriceChanges($offer->id);
            } else {
                $this->performSync($offer->id);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        
        if (!$dryRun) {
            $this->info('✅ All offers synced successfully');
        }
    }

    /**
     * Show what price changes would be made
     */
    private function showPriceChanges(int $offerId): void
    {
        $offer = Offer::find($offerId);
        $rawMaterials = $offer->rawMaterials()->withPivot(['price', 'price_date'])->get();
        
        $this->newLine();
        $this->info("📊 Price changes for Offer #{$offerId}:");
        
        $hasChanges = false;
        foreach ($rawMaterials as $material) {
            $pivot = $material->pivot;
            $currentPrice = $pivot->price;
            $currentDate = $pivot->price_date;
            $newPrice = $material->price;
            $newDate = $material->price_date;
            
            if ($currentPrice != $newPrice || $currentDate != $newDate) {
                $hasChanges = true;
                $this->line("  • {$material->name}:");
                $this->line("    Price: {$currentPrice} → {$newPrice}");
                $this->line("    Date: {$currentDate} → {$newDate}");
            }
        }
        
        if (!$hasChanges) {
            $this->info("  No price changes needed");
        }
    }

    /**
     * Perform the actual sync operation
     */
    private function performSync(int $offerId): void
    {
        try {
            $service = app(OfferRawMaterialService::class);
            $service->syncPricesFromRawMaterials($offerId);
            $this->info("✅ Offer #{$offerId} synced successfully");
        } catch (\Exception $e) {
            $this->error("❌ Failed to sync Offer #{$offerId}: " . $e->getMessage());
        }
    }
}
