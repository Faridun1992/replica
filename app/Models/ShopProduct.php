<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopProduct extends Model
{
    protected $fillable = ['name', 'category', 'price', 'stock'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class, 'product_id');
    }
}
