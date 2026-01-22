<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'from_user_id',
        'to_user_id',
        'rating',
        'comment'
    ];

    // The request this review belongs to
    public function request()
    {
        return $this->belongsTo(SkillRequest::class, 'request_id');
    }

    // The user who wrote the review
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    // The user who received the review
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
