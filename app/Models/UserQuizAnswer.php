<?php

// app/Models/UserQuizAnswer.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserQuizAnswer extends Model {
    protected $guarded = ['id'];
    public function attempt() { return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id'); }
    public function question() { return $this->belongsTo(QuizQuestion::class, 'quiz_question_id'); }
    public function option() { return $this->belongsTo(QuizOption::class, 'quiz_option_id'); }
}