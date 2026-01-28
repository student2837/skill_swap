<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_type',
        'external_id',
        'headers',
        'payload',
        'processed',
        'processed_at',
        'processing_error',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];
}

