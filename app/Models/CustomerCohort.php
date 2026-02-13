<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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