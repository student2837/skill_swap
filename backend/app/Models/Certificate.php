<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'skill_id',
        'student_id',
        'teacher_id',
        'course_name',
        'teacher_name',
        'student_name',
        'certificate_code',
        'score',
        'percentage',
        'passed',
        'certificate_text',
        'completion_date',
    ];

    protected $casts = [
        'completion_date' => 'date',
        'passed' => 'boolean',
        'percentage' => 'decimal:2',
    ];

    public function request()
    {
        return $this->belongsTo(SkillRequest::class, 'request_id');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
