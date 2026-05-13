<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class OfferDrawing extends Model
{
    public $timestamps = false;
    protected $fillable = ['offer_id', 'filename', 'upload_date'];

    protected $casts = [
        'upload_date' => 'datetime',
    ];

    protected $appends = ['preview_url'];

    public function getPreviewUrlAttribute(): string
    {
        $basePath = config('offer_drawings.base_path');
        $year     = $this->upload_date ? $this->upload_date->format('Y') : now()->year;

        return Storage::disk('public')->url("{$basePath}/{$year}/{$this->filename}");
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}
