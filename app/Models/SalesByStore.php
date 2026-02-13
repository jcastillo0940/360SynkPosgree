<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesByStore extends Model
{
    use HasFactory;

    protected $table = 'sales_by_store';

    protected $fillable = [
        'period_id',
        'store_code',
        'store_name',
        'total_orders',
        'total_revenue',
        'average_order_value',
        'total_items_sold',
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
        'average_order_value' => 'decimal:2',
    ];

    public function period()
    {
        return $this->belongsTo(SalesAnalysisPeriod::class, 'period_id');
    }
}