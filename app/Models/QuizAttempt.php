<?php

// app/Models/QuizAttempt.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model {
    protected $guarded = ['id'];
    public function user() { return $this->belongsTo(User::class); }
    public function recommendedProduct() { return $this->belongsTo(Product::class, 'product_id'); }
    public function answers() { return $this->hasMany(UserQuizAnswer::class); }
}