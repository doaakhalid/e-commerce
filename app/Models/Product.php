<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'base_price', 'is_dynamic_pricing_enabled'];

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function priceHistories()
    {
        return $this->hasMany(PriceHistory::class);
    }
}
