<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesAnalysisPeriod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'period_type',
        'period_start',
        'period_end',
        'status',
        'total_orders',
        'total_revenue',
        'average_order_value',
        'total_items_sold',
        'total_customers',
        'new_customers',
        'returning_customers',
        'total_sessions',
        'conversion_rate',
        'carts_created',
        'carts_abandoned',
        'cart_abandonment_rate',
        'carts_recovered',
        'cart_recovery_rate',
        'repeat_customers',
        'repeat_purchase_rate',
        'customer_retention_rate',
        'churn_rate',
        'customer_lifetime_value',
        'started_at',
        'completed_at',
        'error_message',
        'additional_data',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_revenue' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'cart_abandonment_rate' => 'decimal:2',
        'cart_recovery_rate' => 'decimal:2',
        'repeat_purchase_rate' => 'decimal:2',
        'customer_retention_rate' => 'decimal:2',
        'churn_rate' => 'decimal:2',
        'customer_lifetime_value' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'additional_data' => 'array',
    ];

    // Relaciones
    public function salesByStore()
    {
        return $this->hasMany(SalesByStore::class, 'period_id');
    }

    public function salesByPaymentMethod()
    {
        return $this->hasMany(SalesByPaymentMethod::class, 'period_id');
    }

    public function salesByLocation()
    {
        return $this->hasMany(SalesByLocation::class, 'period_id');
    }

    public function topProducts()
    {
        return $this->hasMany(TopProduct::class, 'period_id');
    }

    public function customerCohorts()
    {
        return $this->hasMany(CustomerCohort::class, 'period_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByPeriodType($query, $type)
    {
        return $query->where('period_type', $type);
    }

    // Mutators & Accessors
    public function getFormattedRevenueAttribute()
    {
        return '$' . number_format($this->total_revenue, 2);
    }

    public function getFormattedAovAttribute()
    {
        return '$' . number_format($this->average_order_value, 2);
    }

    public function getPeriodLabelAttribute()
    {
        $labels = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];
        
        return $labels[$this->period_type] ?? ucfirst($this->period_type);
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ];
        
        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'gray',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
        ];
        
        return $colors[$this->status] ?? 'gray';
    }

    // Métodos de negocio
    public function markAsStarted()
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    public function getDurationAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffForHumans($this->completed_at, true);
    }
}
