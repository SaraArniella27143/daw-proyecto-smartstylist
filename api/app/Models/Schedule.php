<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['outfit_id', 'scheduled_date'])]
class Schedule extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
        ];
    }

    public function outfit(): BelongsTo
    {
        return $this->belongsTo(Outfit::class);
    }
}
