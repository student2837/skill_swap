<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'request_id',
        'from_user_id',
        'to_user_id',
        'content',
        'read_at'
    ];

    // Message belongs to a conversation
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    // Message belongs to a request (optional, for backward compatibility)
    public function request()
    {
        return $this->belongsTo(SkillRequest::class, 'request_id');
    }

    // Sender
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    // Receiver
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
