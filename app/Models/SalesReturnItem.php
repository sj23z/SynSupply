<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends Model
{
    protected $fillable = [
        'sales_return_id', 'product_id', 'invoice_item_id', 'qty', 'unit_price',
        'line_total', 'batch_number', 'expiry_date', 'restock_batch_id', 'unit_cost_used',
    ];

    protected function casts(): array
    {
        return ['expiry_date' => 'date'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }
}
