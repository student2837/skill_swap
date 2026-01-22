<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_user_id',
        'skill_id'
    ];

    // The user who added the favorite
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }



    // The skill being favorited (optional)
    public function skill()
    {
        return $this->belongsTo(Skill::class, 'skill_id');
    }
}
