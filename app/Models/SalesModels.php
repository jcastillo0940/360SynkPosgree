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

class CustomerCohort extends Model
{
    use HasFactory;

    protected $table = 'customer_cohorts';

    protected $fillable = [
        'period_id',
        'cohort_month',
        'customers_count',
        'initial_revenue',
        'retention_by_month',
    ];

    protected $casts = [
        'cohort_month' => 'date',
        'initial_revenue' => 'decimal:2',
        'retention_by_month' => 'array',
    ];

    public function period()
    {
        return $this->belongsTo(SalesAnalysisPeriod::class, 'period_id');
    }
}
