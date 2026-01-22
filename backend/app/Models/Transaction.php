<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'fee',
        'status',
        'reference_id'
    ];

    // Transaction belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
