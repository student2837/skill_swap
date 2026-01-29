<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillRequest extends Model
{
    use HasFactory;

    protected $table='requests';
    protected $fillable=['student_id','skill_id','status','quiz_started_at','quiz_completed_at'];
    protected $casts = [
        'quiz_started_at' => 'datetime',
        'quiz_completed_at' => 'datetime',
    ];


    public function student(){
        return $this->belongsTo(User::class, 'student_id');
    }

    public function skill(){
        return $this->belongsTo(Skill::class);
    }

    public function review()
{
    return $this->hasOne(Review::class);
}

    public function messages()
{
    return $this->hasMany(Message::class);
}






}
