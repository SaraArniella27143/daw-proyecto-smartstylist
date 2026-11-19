<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['outfit_id', 'used_at'])]
class OutfitLog extends Model
{
    protected function casts(): array
    {
        return [
            'used_at' => 'immutable_datetime',
        ];
    }

    public function outfit(): BelongsTo
    {
        return $this->belongsTo(Outfit::class);
    }
}
