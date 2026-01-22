<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillRequest extends Model
{
    use HasFactory;

    protected $table='requests';
    protected $fillable=['student_id','skill_id','status'];


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
