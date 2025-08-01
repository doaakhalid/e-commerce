<?php
namespace App\Services;
use App\Models\Product;
use App\Models\Country;
use App\Models\ProductPrice;
use App\Models\PriceHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\PriceChanged;
class PriceCalculationService
{

    public function calculate(Product $product, Country $country)
    {
        $cacheKey = "price_{$product->id}_{$country->id}";
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($product, $country) {
            $basePrice = $this->getBasePrice($product, $country);

            $taxedPrice = $basePrice + ($basePrice * $country->tax_rate / 100);
            $finalPrice = $this->adjustForDemandAndCompetition($product, $country, $taxedPrice);

            $this->storePrice($product, $country, $finalPrice);
            return $finalPrice;
        });
    }

    protected function getBasePrice(Product $product, Country $country)
    {
        return ProductPrice::firstOrCreate(
            ['product_id' => $product->id, 'country_id' => $country->id],
            ['base_price' => 100, 'final_price' => 100]
        )->base_price;
    }

    protected function adjustForDemandAndCompetition(Product $product, Country $country, float $price)
    {
        if (!$product->uses_dynamic_pricing)
            return $price;

        $competitorPrice = $this->fetchCompetitorPrice($product, $country);
        $demandMultiplier = $this->getDemandFactor($product);

        $adjusted = ($competitorPrice * 0.9 + $price * 0.1) * $demandMultiplier;

        return round($adjusted, 2);
    }

    protected function fetchCompetitorPrice(Product $product, Country $country): float
    {
        try {
            $token = env('PRICE_API_TOKEN');
            $client = new \GuzzleHttp\Client();

            $response = $client->request('GET', 'https://api.priceapi.com/v2/jobs?page=1&per_page=10&token=' . $token, [
                'body' =>
                    '{"source":"google_shopping",
  "country":{$country->name},
  "values":"apple iphone 6S 64GB gold",
 ',
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ],
            ]);
            $response = json_decode($response->getBody(), true);
            if ($response->successful() && isset($response['price'])) {
                return floatval($response['price']);
            }
        } catch (\Exception $e) {
            Log::warning("Competitor price API failed for product {$product->id}: {$e->getMessage()}");
        }
        return $product->base_price;
    }


    protected function getDemandFactor(Product $product): float
    {
        return 1.1; 
    }

    protected function storePrice(Product $product, Country $country, float $newPrice)
    {
        $existing = ProductPrice::where('product_id', $product->id)
            ->where('country_id', $country->id)
            ->first();

        if (!$existing) {
            ProductPrice::create([
                'product_id' => $product->id,
                'country_id' => $country->id,
                'base_price' => $newPrice,
                'final_price' => $newPrice,
                'is_dynamic' => $product->uses_dynamic_pricing,
                'last_fetched_at' => now(),
            ]);
        } elseif ($existing->final_price != $newPrice) {
            // Save to price history before updating
            $this->logPriceHistory($product, $country, $existing->final_price, $newPrice);

            $existing->update([
                'final_price' => $newPrice,
                'last_fetched_at' => now(),
            ]);

            event(new PriceChanged($product, $country, $newPrice));
        }
    }

    protected function logPriceHistory(Product $product, Country $country, float $old, float $new)
    {
        // Ensure no duplicates for same change
        $exists = PriceHistory::where([
            'product_id' => $product->id,
            'country_id' => $country->id,
            'old_price' => $old,
            'new_price' => $new,
        ])->where('changed_at', '>=', now()->subMinutes(30))->exists();

        if (!$exists) {
            PriceHistory::create([
                'product_id' => $product->id,
                'country_id' => $country->id,
                'old_price' => $old,
                'new_price' => $new,
                'changed_at' => now(),
            ]);
        }
    }
}
