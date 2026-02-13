<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TopProduct extends Model
{
    use HasFactory;

    protected $table = 'top_products';

    protected $fillable = [
        'period_id',
        'sku',
        'product_name',
        'quantity_sold',
        'total_revenue',
        'times_ordered',
        'average_price',
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
        'average_price' => 'decimal:2',
    ];

    public function period()
    {
        return $this->belongsTo(SalesAnalysisPeriod::class, 'period_id');
    }
}