<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopCustomer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'city'];

    public function orders(): HasMany
    {
        return $this->hasMany(ShopOrder::class, 'customer_id');
    }
}
