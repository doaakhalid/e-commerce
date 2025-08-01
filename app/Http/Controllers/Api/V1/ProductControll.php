<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PriceCalculationService;
use App\Models\Product;
use App\Models\Country;
class ProductControll extends Controller
{protected $priceService;

    public function __construct(PriceCalculationService $priceService)
    {
        $this->priceService = $priceService;
    }

    public function show(Request $request, $productId, $countryId)
    {
        $product = Product::findOrFail($productId);
        $country = Country::findOrFail($countryId);

        $price = $this->priceService->calculate($product, $country);

        return response()->json([
            'product_id' => $product->id,
            'country_id' => $country->id,
            'final_price' => $price,
            'currency' => $country->currency,
        ]);
    }

}
