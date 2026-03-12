<?php

namespace App\Services;

use App\Config\RoleConstants;
use App\Models\Offer;
use App\Models\OfferCalculated;
use App\Models\User;
use App\Repositories\OfferRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class OfferService
{
    public function __construct(private OfferRepository $repository) {}

    public function getAllSummary()
    {
        return $this->repository->getAllSummarized();
    }

    public function getOfferById(int $id): OfferCalculated
    {
        // Ensure graduated pricing fields are initialized before returning
        $offer = Offer::find($id);
        if ($offer) {
            // Force initialization check - this ensures graduated fields are set
            $this->initializeGraduatedPricingFields($offer, '');
            // Reload the offer to ensure any updates are reflected
            $offer->refresh();
        }
        
        return OfferCalculated::with(['createdByUser', 'status'])->findOrFail($id);
    }

    public function duplicateOffer(int $id)
    {
        $offer = $this->repository->getOfferById($id);

        if (!$offer) {
            throw new ModelNotFoundException('Offer not found.');
        }

        $newOffer = $this->repository->duplicate($offer);

        return $newOffer;
    }


    public function createOfferFromField(string $field, mixed $value, int $userId): Offer
    {
        $allowedFields = $this->allowedFields();
        if (!in_array($field, $allowedFields)) {
            throw new \InvalidArgumentException('Invalid field: ' . $field);
        }

        $data = [
            $field => $value,
            'general_created_by_user_id' => $userId,
            'general_status_id' => 1,
            'general_creation_date' => now(),
        ];

        $offer = $this->repository->createOffer($data);
        
        // Refresh to ensure database defaults are loaded (setup_time=3, hourly_rate=42)
        $offer->refresh();
        
        // Initialize graduated pricing fields immediately after creation
        // This ensures that default values are used for calculation even if user hasn't set them
        $this->initializeGraduatedPricingFields($offer, $field);
        
        return $offer;
    }

    public function updateField(Offer $offer, string $field, mixed $value): Offer
    {
        $allowedFields = $this->allowedFields();

        if (!in_array($field, $allowedFields)) {
            throw new \InvalidArgumentException('Invalid field: ' . $field);
        }

        // Handle date formatting for date fields
        if (Str::endsWith($field, '_date') && !empty($value)) {
            $value = date('Y-m-d', strtotime($value)); // Convert ISO to Y-m-d
        }

        $updatedOffer = $this->repository->updateSingleField($offer, $field, $value);
        
        // Initialize graduated pricing fields if source values become available
        $this->initializeGraduatedPricingFields($updatedOffer, $field);
        
        // Refresh the offer to ensure we have the latest data after initialization
        $updatedOffer->refresh();
        
        return $updatedOffer;
    }

    /**
     * Initialize graduated pricing fields when source values become available.
     * This ensures calculated fields in the database view work correctly.
     * 
     * This method checks on EVERY field update to see if initialization is needed,
     * ensuring that whenever source values become available, the graduated fields are initialized.
     */
    private function initializeGraduatedPricingFields(Offer $offer, string $updatedField): void
    {
        // Refresh the offer to get latest values
        $offer->refresh();
        
        // Check if we need to initialize setup costs fields
        // Calculate directly from source fields to avoid dependency on view calculations
        // Note: These fields have database defaults (3 and 42), so they should always be available
        $setupTime = $offer->calculation_additional_setup_time;
        $hourlyRate = $offer->calculation_additional_hourly_rate;
        
        // Check if source fields are set (not null and not empty string, but allow "0" as valid)
        // With database defaults (setup_time=3, hourly_rate=42), these should have values after refresh
        // But if they're null, use defaults for calculation
        if ($setupTime === null) {
            $setupTime = 3; // Default value from migration
        }
        if ($hourlyRate === null) {
            $hourlyRate = 42; // Default value from migration
        }
        
        // Check if we have valid numeric values (including defaults)
        $hasSetupTime = $setupTime !== null && $setupTime !== '' && is_numeric($setupTime);
        $hasHourlyRate = $hourlyRate !== null && $hourlyRate !== '' && is_numeric($hourlyRate);
        
        if ($hasSetupTime && $hasHourlyRate) {
            // Calculate setup costs directly: setup_time * hourly_rate
            // Convert to numeric values to ensure proper calculation
            $setupTimeValue = (float)$setupTime;
            $hourlyRateValue = (float)$hourlyRate;
            $setupCostsValue = $setupTimeValue * $hourlyRateValue;
            
            $setupCostFields = [
                'pricing_grad_qtyB_add_setupcosts',
                'pricing_grad_qtyC_add_setupcosts',
                'pricing_grad_qtyD_add_setupcosts',
                'pricing_grad_qtyE_add_setupcosts',
            ];
            
            $needsUpdate = false;
            foreach ($setupCostFields as $field) {
                $currentValue = $offer->{$field};
                // Check for both null and empty string (empty string might be used instead of null)
                if ($currentValue === null || $currentValue === '') {
                    $needsUpdate = true;
                    break;
                }
            }
            
            // Batch update if any field needs updating
            if ($needsUpdate) {
                foreach ($setupCostFields as $field) {
                    $currentValue = $offer->{$field};
                    if ($currentValue === null || $currentValue === '') {
                        $this->repository->updateSingleField($offer, $field, $setupCostsValue);
                    }
                }
                // Single refresh after batch update
                $offer->refresh();
            }
        }
        
        // Check if we need to initialize transport cost fields
        // Initialize if calculation_additional_transport_costs_total is available but graduated fields are null
        $transportValue = $offer->calculation_additional_transport_costs_total;
        // Allow "0" as a valid value, but exclude null and empty string
        if ($transportValue !== null && $transportValue !== '' && is_numeric($transportValue)) {
            $transportFields = [
                'pricing_grad_qtyB_add_transport',
                'pricing_grad_qtyC_add_transport',
                'pricing_grad_qtyD_add_transport',
                'pricing_grad_qtyE_add_transport',
            ];
            
            $needsUpdate = false;
            foreach ($transportFields as $field) {
                $currentValue = $offer->{$field};
                if ($currentValue === null || $currentValue === '') {
                    $needsUpdate = true;
                    break;
                }
            }
            
            // Batch update if any field needs updating
            if ($needsUpdate) {
                foreach ($transportFields as $field) {
                    $currentValue = $offer->{$field};
                    if ($currentValue === null || $currentValue === '') {
                        $this->repository->updateSingleField($offer, $field, $transportValue);
                    }
                }
                // Single refresh after batch update
                $offer->refresh();
            }
        }
    }

    private function allowedFields(): array
    {
        return OfferCalculated::allowedFields();
    }

    public function editableFieldsByRoleAndStatus(User $user, Offer $offer): array
    {
        $role = strtolower($user->getRoleNames()->first());
        $statusName = $offer->status?->name;

        $allFields = $this->allowedFields();
        $statuses = config('offer_statuses.statuses');
        $editableRules = config('offer_statuses.editable_fields');

        // Admin
        if ($editableRules[$role] === 'ALL') {
            return $allFields;
        }

        // If no status (new offer), treat as VORKALKULATION
        if (!$statusName) {
            $statusKey = 'VORKALKULATION';
        } else {
            // Find the status key (example: 'VORKALKULATION', 'AUFTRAG')
            $statusKey = array_search($statusName, $statuses, true);
        }

        // If no matching statusKey found
        if (!$statusKey) {
            return [];
        }

        $roleRules = $editableRules[$role] ?? [];

        // Handle production DEFAULT case
        if ($role === RoleConstants::PRODUCTION_ROLE && !isset($roleRules[$statusKey])) {
            return $roleRules['DEFAULT'] === 'ALL' ? $allFields : $roleRules['DEFAULT'];
        }

        // Normal cases
        $fields = $roleRules[$statusKey] ?? [];

        return $fields === 'ALL' ? $allFields : $fields;
    }
}
