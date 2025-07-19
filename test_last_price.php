<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\ProductRepository;

try {
    echo "Testing Last Sale Price Functionality\n";
    echo "====================================\n\n";

    // Create test instances
    $productRepo = app(ProductRepository::class);

    // Test scenario: Create a customer, product, and sale with discount
    echo "1. Creating test data...\n";

    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'country' => 'Test Country',
        'city' => 'Test City',
        'zip_code' => '12345',
    ]);
    echo "   Created customer ID: {$customer->id}\n";

    $product = Product::create([
        'name' => 'Test Product',
        'code' => 'TEST-001',
        'product_price' => 120.00,
        'product_cost' => 100.00,
        'tax_type' => 1,
        'order_tax' => 0,
        'stock_alert' => 10,
        'notes' => 'Test product for last price testing'
    ]);
    echo "   Created product ID: {$product->id}\n";

    $sale = Sale::create([
        'customer_id' => $customer->id,
        'warehouse_id' => 1, // Assuming warehouse exists
        'date' => now(),
        'tax_rate' => 0.00,
        'tax_amount' => 0.00,
        'discount' => 10.00,
        'shipping' => 0.00,
        'grand_total' => 110.00,
        'status_id' => 1,
        'payment_status' => 1,
        'payment_type' => 1,
    ]);
    echo "   Created sale ID: {$sale->id}\n";

    // Create sale item with original price 120, but customer paid 110 (net_unit_price)
    $saleItem = SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_price' => 120.00, // Original selling price
        'net_unit_price' => 110.00, // Price after discount
        'tax_type' => 1,
        'tax_value' => 0.00,
        'tax_amount' => 0.00,
        'discount_type' => 1,
        'discount_value' => 10.00,
        'discount_amount' => 10.00,
        'sale_unit' => 1,
        'quantity' => 1,
        'sub_total' => 110.00,
    ]);
    echo "   Created sale item ID: {$saleItem->id}\n\n";

    // Test the getLastSalePrice method
    echo "2. Testing getLastSalePrice method...\n";
    $lastPrice = $productRepo->getLastSalePrice($product->id, $customer->id);
    
    echo "   Product original price: 120.00\n";
    echo "   Discount given: 10.00\n";
    echo "   Customer actually paid: 110.00\n";
    echo "   getLastSalePrice returned: " . ($lastPrice ?? 'null') . "\n\n";

    if ($lastPrice == 110.00) {
        echo "✅ SUCCESS: Last price correctly returns the discounted price!\n";
    } else {
        echo "❌ FAILED: Expected 110.00, got " . ($lastPrice ?? 'null') . "\n";
    }

    // Test with a product that has no sales
    $product2 = Product::create([
        'name' => 'Test Product 2',
        'code' => 'TEST-002',
        'product_price' => 50.00,
        'product_cost' => 40.00,
        'tax_type' => 1,
        'order_tax' => 0,
        'stock_alert' => 5,
        'notes' => 'Test product 2'
    ]);
    
    echo "\n3. Testing with product that has no sales...\n";
    $noSalePrice = $productRepo->getLastSalePrice($product2->id, $customer->id);
    echo "   getLastSalePrice for unsold product returned: " . ($noSalePrice ?? 'null') . "\n";
    
    if ($noSalePrice === null) {
        echo "✅ SUCCESS: Correctly returns null for products with no sales!\n";
    } else {
        echo "❌ FAILED: Expected null, got " . $noSalePrice . "\n";
    }

    // Clean up
    echo "\n4. Cleaning up test data...\n";
    $saleItem->delete();
    $sale->delete();
    $product->delete();
    $product2->delete();
    $customer->delete();
    echo "   Test data cleaned up.\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
