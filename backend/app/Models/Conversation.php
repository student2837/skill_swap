<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'request_id'
    ];

    /**
     * Get the first user in the conversation
     */
    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * Get the second user in the conversation
     */
    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * Get the associated request (if any)
     */
    public function request()
    {
        return $this->belongsTo(SkillRequest::class, 'request_id');
    }

    /**
     * Get all messages in this conversation
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message in this conversation
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Get the other user in the conversation
     */
    public function getOtherUser($currentUserId)
    {
        if ($this->user_one_id == $currentUserId) {
            return $this->userTwo;
        }
        return $this->userOne;
    }

    /**
     * Check if a user is part of this conversation
     */
    public function hasUser($userId)
    {
        return $this->user_one_id == $userId || $this->user_two_id == $userId;
    }

    /**
     * Get or create a conversation between two users
     */
    public static function getOrCreate($userOneId, $userTwoId, $requestId = null)
    {
        // Ensure consistent ordering (smaller ID first)
        $ids = [$userOneId, $userTwoId];
        sort($ids);
        
        $conversation = self::where('user_one_id', $ids[0])
            ->where('user_two_id', $ids[1])
            ->first();
        
        if (!$conversation) {
            $conversation = self::create([
                'user_one_id' => $ids[0],
                'user_two_id' => $ids[1],
                'request_id' => $requestId
            ]);
        } elseif ($requestId && !$conversation->request_id) {
            // Update request_id if it wasn't set
            $conversation->update(['request_id' => $requestId]);
        }
        
        return $conversation;
    }
}
