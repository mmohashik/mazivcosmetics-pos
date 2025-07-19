<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class ProductCategoryRepository
 */
class ProductRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'code',
        'product_cost',
        'product_price',
        'product_unit',
        'sale_unit',
        'purchase_unit',
        'stock_alert',
        'order_tax',
        'notes',
        'created_at',
    ];

    /**
     * @var string[]
     */
    protected $allowedFields = [
        'name',
        'code',
        'product_cost',
        'product_price',
        'product_unit',
        'sale_unit',
        'purchase_unit',
        'stock_alert',
        'order_tax',
        'notes',
    ];

    public function getAvailableRelations(): array
    {
        return array_values(Product::$availableRelations);
    }

    /**
     * Return searchable fields
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Product::class;
    }

    /**
     * @return LengthAwarePaginator|Collection|mixed
     */
    public function storeProduct($input)
    {
        try {
            DB::beginTransaction();
            $product = $this->create($input);
            $reference_code = 'PR_' . $product->id;
            $this->generateBarcode($input, $reference_code);
            $product['barcode_image_url'] = Storage::url('product_barcode/barcode-' . $reference_code . '.png');

            // create purchase

        if (isset($input['purchase_quantity'])) {
            $purchaseStock = [
                'warehouse_id' => $input['purchase_warehouse_id'],
                'supplier_id' => $input['purchase_supplier_id'],
                'quantity' => $input['purchase_quantity'],
                'status' => $input['purchase_status'],
                'date' => $input['purchase_date'],
            ];

            //            $purchaseStock = $input['manage_purchase_stock'][0];

            if ($purchaseStock) {
                if (empty($purchaseStock['warehouse_id'])) {
                    throw new UnprocessableEntityHttpException('Please Select the warehouse.');
                } elseif (empty($purchaseStock['supplier_id'])) {
                    throw new UnprocessableEntityHttpException('Please Select the supplier.');
                } elseif ($purchaseStock['quantity'] <= 0) {
                    throw new UnprocessableEntityHttpException('Please Enter Attlist One stock Quantity.');
                } elseif (empty($purchaseStock['status'])) {
                    throw new UnprocessableEntityHttpException('Please Select the status.');
                }

                $purchaseStock['tax_rate'] = 0;
                $purchaseStock['tax_amount'] = 0;
                $purchaseStock['discount'] = 0;
                $purchaseStock['shipping'] = 0;
                $purchaseStock['payment_type'] = 0;

                $purchaseStock['date'] = $purchaseStock['date'] ?? date('Y/m/d');

                $purchaseInputArray = Arr::only($purchaseStock, [
                    'supplier_id', 'warehouse_id', 'date', 'status', 'discount', 'tax_rate', 'tax_amount', 'shipping', 'payment_type',
                ]);

                /** @var Purchase $purchase */
                $purchase = Purchase::create($purchaseInputArray);

                $perItemTaxAmount = 0;

                $purchaseStock['net_unit_cost'] = $product->product_cost;

                if ($product->order_tax <= 100 && $product->order_tax >= 0) {
                    if ($product->tax_type == Purchase::EXCLUSIVE) {
                        $purchaseStock['tax_amount'] = (($purchaseStock['net_unit_cost'] * $product->order_tax) / 100) * $purchaseStock['quantity'];
                        $perItemTaxAmount = $purchaseStock['tax_amount'] / $purchaseStock['quantity'];
                    } elseif ($product->tax_type == Purchase::INCLUSIVE) {
                        $purchaseStock['tax_amount'] = ($purchaseStock['net_unit_cost'] * $product->order_tax) / (100 + $product->order_tax) * $purchaseStock['quantity'];
                        $perItemTaxAmount = $purchaseStock['tax_amount'] / $purchaseStock['quantity'];
                        $purchaseStock['net_unit_cost'] -= $perItemTaxAmount;
                    }
                } else {
                    throw new UnprocessableEntityHttpException('Please enter tax value between 0 to 100 ');
                }
                $purchaseStock['sub_total'] = ($purchaseStock['net_unit_cost'] + $perItemTaxAmount) * $purchaseStock['quantity'];

                $purchaseItemArr = [
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'product_cost' => $product->product_cost,
                    'net_unit_cost' => $purchaseStock['net_unit_cost'],
                    'tax_type' => $product->tax_type,
                    'tax_value' => $product->order_tax,
                    'tax_amount' => $purchaseStock['tax_amount'],
                    'discount_type' => Purchase::FIXED,
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'purchase_unit' => $product->purchase_unit,
                    'quantity' => $purchaseStock['quantity'],
                    'sub_total' => $purchaseStock['sub_total'],
                ];

                $purchaseItem = new PurchaseItem($purchaseItemArr);
                $purchase->purchaseItems()->save($purchaseItem);

                $purchase->update([
                    'reference_code' => getSettingValue('purchase_code') . '_111' . $purchase->id,
                    'grand_total' => $purchaseStock['sub_total'],
                ]);

                // manage stock

                manageStock($purchaseStock['warehouse_id'], $product->id, $purchaseStock['quantity']);
            }
        }
            DB::commit();

            return $product;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @return LengthAwarePaginator|Collection|mixed
     */
    public function updateProduct($input, $id)
    {
        try {
            DB::beginTransaction();
            $product = $this->update($input, $id);

            $product->clearMediaCollection(Product::PRODUCT_BARCODE_PATH);
            $reference_code = 'PR_' . $product->id;
            $this->generateBarcode($input, $reference_code);
            $product['barcode_image_url'] = Storage::url('product_barcode/barcode-' . $reference_code . '.png');

            DB::commit();

            return $product;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function generateBarcode($input, $reference_code): bool
    {
        $barcodeType = null;
        $generator = new BarcodeGeneratorPNG();
        switch ($input['barcode_symbol']) {
            case Product::CODE128:
                $barcodeType = $generator::TYPE_CODE_128;
                break;
            case Product::CODE39:
                $barcodeType = $generator::TYPE_CODE_39;
                break;
            case Product::EAN8:
                $barcodeType = $generator::TYPE_EAN_8;
                break;
            case Product::EAN13:
                $barcodeType = $generator::TYPE_EAN_13;
                break;
            case Product::UPC:
                $barcodeType = $generator::TYPE_UPC_A;
                break;
        }

        Storage::disk(config('app.media_disc'))->put(
            'product_barcode/barcode-' . $reference_code . '.png',
            $generator->getBarcode($input['code'], $barcodeType, 4, 70)
        );

        return true;
    }

    /**
     * Get the last sale price for a given product and customer.
     *
     * @param  int  $productId
     * @param  int  $customerId
     * @return float|null
     */
    public function getLastSalePrice(int $productId, int $customerId): ?float
    {
        // Get the most recent sale for this customer-product combination
        // Using multiple ordering criteria to ensure we get the absolute latest record
        $saleItem = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.product_id', $productId)
            ->where('sales.customer_id', $customerId)
            ->orderBy('sales.created_at', 'DESC') // Primary: Most recently created sale
            ->orderBy('sales.id', 'DESC') // Secondary: Highest sale ID (most recent)
            ->orderBy('sale_items.created_at', 'DESC') // Tertiary: Most recently created sale item
            ->orderBy('sale_items.id', 'DESC') // Final: Highest sale_item ID (most recent)
            ->select(
                'sale_items.product_price', 
                'sale_items.net_unit_price', 
                'sale_items.discount_amount', 
                'sale_items.quantity', 
                'sales.date', 
                'sales.created_at', 
                'sales.id as sale_id', 
                'sale_items.id as sale_item_id',
                'sale_items.created_at as item_created_at'
            )
            ->first();

        if (!$saleItem) {
            return null;
        }

        // Log for debugging (can be removed in production)
        \Log::info("getLastSalePrice Debug", [
            'product_id' => $productId,
            'customer_id' => $customerId,
            'sale_id' => $saleItem->sale_id,
            'sale_item_id' => $saleItem->sale_item_id,
            'sale_date' => $saleItem->date,
            'sale_created_at' => $saleItem->created_at,
            'item_created_at' => $saleItem->item_created_at,
            'product_price' => $saleItem->product_price,
            'net_unit_price' => $saleItem->net_unit_price,
            'discount_amount' => $saleItem->discount_amount,
            'quantity' => $saleItem->quantity
        ]);

        // Calculate the actual price paid per unit
        // If net_unit_price is set, use it; otherwise calculate it
        if ($saleItem->net_unit_price !== null) {
            return (float) $saleItem->net_unit_price;
        } else {
            // Fallback calculation: product_price - (discount_amount / quantity)
            $discountPerUnit = $saleItem->quantity > 0 ? $saleItem->discount_amount / $saleItem->quantity : 0;
            return (float) ($saleItem->product_price - $discountPerUnit);
        }
    }
}
