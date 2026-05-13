<?php

namespace App\Repositories;

use App\Models\Offer;

class OfferRepository
{
    public function getAllSummarized(string $sortBy = 'general_offer_number', string $sortDir = 'asc', string $search = '')
    {
        $allowed = ['general_offer_number', 'general_customer', 'general_profile_description', 'general_creation_date', 'status'];
        $sortBy  = in_array($sortBy, $allowed) ? $sortBy : 'general_offer_number';
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';

        $columns = [
            'offers.id',
            'offers.general_offer_number',
            'offers.general_customer',
            'offers.general_profile_description',
            'offers.general_creation_date',
            'offers.general_status_id',
        ];

        $query = Offer::query()
            ->select($columns)
            ->with('status')
            ->whereHas('status', function ($query) {
                $query->where('name', '!=', 'Gelöscht');
            })
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . $search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('offers.general_offer_number', 'like', $term)
                      ->orWhere('offers.general_customer', 'like', $term)
                      ->orWhere('offers.general_profile_description', 'like', $term);
                });
            });

        if ($sortBy === 'status') {
            $query->join('offer_status', 'offers.general_status_id', '=', 'offer_status.id')
                ->orderBy('offer_status.name', $sortDir);
        } else {
            $query->orderBy('offers.' . $sortBy, $sortDir);
        }

        return $query->get();
    }


    public function getOfferById(int $id): Offer
    {
        return Offer::with('createdByUser')->findOrFail($id);
    }

    public function duplicate(Offer $offer): Offer
    {
        $newOffer = $offer->replicate();
        $newOffer->general_offer_number = 'Copy of ' . $offer->general_offer_number;
        $newOffer->general_creation_date = now();
        $newOffer->save();

        return $newOffer;
    }

    public function createOffer(array $data): Offer
    {
        return Offer::create($data);
    }

    public function updateSingleField(Offer $offer, string $field, mixed $value): Offer
    {
        if (!$offer->exists) {
            throw new \Exception('Offer model is not persisted');
        }

        $offer->{$field} = $value;
        $offer->save();

        return $offer;
    }
}
