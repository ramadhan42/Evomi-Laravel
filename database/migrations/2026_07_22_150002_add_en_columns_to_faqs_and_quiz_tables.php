<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('category_en')->nullable()->after('category');
            $table->string('question_en')->nullable()->after('question');
            $table->text('answer_en')->nullable()->after('answer');
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->text('question_text_en')->nullable()->after('question_text');
        });

        Schema::table('quiz_options', function (Blueprint $table) {
            $table->text('option_text_en')->nullable()->after('option_text');
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn(['category_en', 'question_en', 'answer_en']);
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('question_text_en');
        });

        Schema::table('quiz_options', function (Blueprint $table) {
            $table->dropColumn('option_text_en');
        });
    }
};
