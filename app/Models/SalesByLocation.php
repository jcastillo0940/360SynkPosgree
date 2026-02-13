<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesByLocation extends Model
{
    use HasFactory;

    protected $table = 'sales_by_location';

    protected $fillable = [
        'period_id',
        'country',
        'region',
        'city',
        'postcode',
        'total_orders',
        'total_revenue',
        'total_customers',
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
    ];

    public function period()
    {
        return $this->belongsTo(SalesAnalysisPeriod::class, 'period_id');
    }
}