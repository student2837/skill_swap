<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable=[
    'user_id',
    'title',
    'description',
    'shortDesc',
    'what_youll_learn',
    'price',
    'lesson_type',
    'category',
    'status',
    'rating_avg',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function categories(){
        return $this->belongsToMany(Category::class,'category_skill');
    }

    public function requests() {
    return $this->hasMany(SkillRequest::class);
}

    public function students() {
    return $this->belongsToMany(User::class, 'requests', 'skill_id', 'student_id')
                ->withPivot('status')
                ->wherePivot('status', 'accepted');
}

    public function favorites()
{
    return $this->hasMany(Favorite::class, 'skill_id');
}

}
