<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'currency', 'tax_percentage'];

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function priceHistories()
    {
        return $this->hasMany(PriceHistory::class);
    }
}
