<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'name', 'category', 'description', 'stock', 'unit', 'location', 'expiration_date'])]
class RawMaterial extends Model
{
    protected $table = 'raw_materials';

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:3',
            'expiration_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('quantity_per_product')
            ->withTimestamps();
    }
}
