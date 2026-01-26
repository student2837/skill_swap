<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
        'profile_pic',
        'credits',
        'rating_avg',
        'is_admin',
        'is_verified',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_verified' => 'boolean',
            'rating_avg' => 'float',

        ];
    }

    public function skills(){
        return $this->hasMany(Skill::class);
    }

    public function requests() {
    return $this->hasMany(SkillRequest::class, 'student_id');
}


    public function learningSkills() {
        return $this->belongsToMany(Skill::class, 'requests', 'student_id', 'skill_id')
                ->withPivot('status')
                ->wherePivot('status', 'accepted');
}

// Users that this user has favorited (skills or people)
public function favoritesGiven()
{
    return $this->hasMany(Favorite::class, 'user_id');
}

// Reviews written by this user
public function reviewsGiven()
{
    return $this->hasMany(Review::class, 'from_user_id');
}

// Reviews this user received
public function reviewsReceived()
{
    return $this->hasMany(Review::class, 'to_user_id');
}

public function transactions()
{
    return $this->hasMany(Transaction::class);
}

public function payouts()
{
    return $this->hasMany(Payout::class);
}

//this two is optional
public function sentMessages()
{
    return $this->hasMany(Message::class, 'from_user_id');
}

public function receivedMessages()
{
    return $this->hasMany(Message::class, 'to_user_id');
}





}
