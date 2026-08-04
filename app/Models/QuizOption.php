<?php

// app/Models/QuizOption.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class QuizOption extends Model {
    protected $guarded = ['id'];
    public function question() { return $this->belongsTo(QuizQuestion::class, 'quiz_question_id'); }
}