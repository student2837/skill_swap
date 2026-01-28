<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payout_method_id',
        'amount',
        'gross_amount',
        'fee_amount',
        'net_amount',
        'provider',
        'method',
        'provider_reference',
        'idempotency_key',
        'status',
        'admin_note',
        'approved_at',
        'approved_by',
        'processed_at',
        'failure_code',
        'failure_message',
        'method_details',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'method_details' => 'array',
    ];

    /**
     * PayPal requires currency units; we map credits -> USD for payouts.
     * Default mapping: 1 credit = 1 USD (configurable).
     */
    public function getAmountUsdAttribute(): float
    {
        $rate = (float) config('payments.credit_to_usd_rate', 1.0);
        return ((float) $this->amount) * $rate;
    }

    // Payout belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payoutMethod()
    {
        return $this->belongsTo(UserPayoutMethod::class, 'payout_method_id');
    }
}
