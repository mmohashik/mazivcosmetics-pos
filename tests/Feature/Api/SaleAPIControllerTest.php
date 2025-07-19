<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleAPIControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        // Add permission if required by the route middleware, e.g.:
        // $this->user->givePermissionTo('manage_sale'); // Assuming a permission system
    }

    public function test_get_last_sale_price_for_customer_api()
    {
        // 1. Setup
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        // Old sale
        $sale1 = Sale::factory()->create([
            'customer_id' => $customer->id,
            'date' => now()->subDays(2),
        ]);
        SaleItem::factory()->create([
            'sale_id' => $sale1->id,
            'product_id' => $product->id,
            'product_price' => 20.50,
        ]);

        // More recent sale
        $sale2 = Sale::factory()->create([
            'customer_id' => $customer->id,
            'date' => now()->subDay(), // More recent
        ]);
        SaleItem::factory()->create([
            'sale_id' => $sale2->id,
            'product_id' => $product->id,
            'product_price' => 25.75, // New price
        ]);

        // 2. Test API endpoint - successful retrieval
        $response = $this->actingAs($this->user, 'sanctum') // or 'api' depending on your auth guard
                         ->getJson("/api/products/{$product->id}/customers/{$customer->id}/last-sale-price");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => 25.75,
                     'message' => 'Last sale price retrieved successfully.',
                 ]);

        // 3. Test API endpoint: No previous sale
        $otherProduct = Product::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $responseNoSale = $this->actingAs($this->user, 'sanctum')
                               ->getJson("/api/products/{$otherProduct->id}/customers/{$otherCustomer->id}/last-sale-price");

        // Assuming the API returns error false and message, but data is null or specific message for not found.
        // The prompt mentioned "sendError" in SaleAPIController, which typically results in a different structure or status.
        // Let's adjust based on the sendError implementation.
        // If sendError returns {success: false, message: "..."} and HTTP 200 (as AppBaseController often does)
        $responseNoSale->assertStatus(200) // Or 404 if sendError is configured to do so
                       ->assertJson([
                           'success' => false, // Because sendError was used
                           'message' => 'No sale record found for this product and customer.',
                       ]);
                       // ->assertJsonMissingPath('data'); // Or assert data is null if present

        // Test with a product that has sales but not for this specific customer
        $responseSaleOtherCustomer = $this->actingAs($this->user, 'sanctum')
                                          ->getJson("/api/products/{$product->id}/customers/{$otherCustomer->id}/last-sale-price");
        $responseSaleOtherCustomer->assertStatus(200)
                                  ->assertJson([
                                      'success' => false,
                                      'message' => 'No sale record found for this product and customer.',
                                  ]);
    }
}
