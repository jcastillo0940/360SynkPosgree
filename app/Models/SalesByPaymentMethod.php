<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesByPaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'sales_by_payment_method';

    protected $fillable = [
        'period_id',
        'payment_method',
        'payment_method_title',
        'total_orders',
        'total_revenue',
        'percentage',
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function period()
    {
        return $this->belongsTo(SalesAnalysisPeriod::class, 'period_id');
    }
}