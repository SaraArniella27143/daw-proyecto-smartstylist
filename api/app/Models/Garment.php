<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'category_id', 'color_id', 'style_id', 'name', 'season', 'image_url'])]
class Garment extends Model
{

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    public function outfits(): BelongsToMany
    {
        return $this->belongsToMany(Outfit::class, 'outfit_garments')
            ->using(OutfitGarment::class)
            ->withPivot(['is_primary', 'note']);
    }
}
