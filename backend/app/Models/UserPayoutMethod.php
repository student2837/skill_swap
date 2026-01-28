<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class UserPayoutMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'method',
        'details_encrypted',
        'is_default',
        'is_verified',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDetailsAttribute(): array
    {
        try {
            $json = Crypt::decryptString((string) $this->details_encrypted);
            $decoded = json_decode($json, true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function setDetailsAttribute(array $value): void
    {
        $this->attributes['details_encrypted'] = Crypt::encryptString(json_encode($value));
    }
}

