<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'admin_note',
        'processed_at'
    ];

    // Payout belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
