<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OutfitGarment extends Pivot
{
    protected $table = 'outfit_garments';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'outfit_id',
        'garment_id',
        'is_primary',
        'note',
    ];

    public function outfit(): BelongsTo
    {
        return $this->belongsTo(Outfit::class);
    }

    public function garment(): BelongsTo
    {
        return $this->belongsTo(Garment::class);
    }
}
