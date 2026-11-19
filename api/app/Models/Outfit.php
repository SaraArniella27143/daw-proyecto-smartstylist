<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'description'])]
class Outfit extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function garments(): BelongsToMany
    {
        return $this->belongsToMany(Garment::class, 'outfit_garments')
            ->using(OutfitGarment::class)
            ->withPivot(['is_primary', 'note']);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OutfitLog::class);
    }
}
