<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizPersonalityResult;
use App\Models\QuizQuestion;
use App\Models\UserQuizAnswer;
use App\Support\LocaleResolver;
use App\Support\ProductLocalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    /** Map DB personality key → frontend key */
    private const PERSONALITY_TO_FRONTEND = [
        'prestige' => 'purpose_prestige',
        'purpose_prestige' => 'purpose_prestige',
        'peaceful_calm' => 'peaceful_calm',
        'rebel_brave' => 'rebel_brave',
        'sweet_shy' => 'sweet_shy',
    ];

    /** Map frontend key → DB product.personality_type */
    private const PERSONALITY_TO_DB = [
        'purpose_prestige' => 'prestige',
        'prestige' => 'prestige',
        'peaceful_calm' => 'peaceful_calm',
        'rebel_brave' => 'rebel_brave',
        'sweet_shy' => 'sweet_shy',
    ];

    /**
     * GET /api/quiz/questions?locale=id|en
     */
    public function getQuestions(Request $request)
    {
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));

        $questions = QuizQuestion::with(['options' => function ($q) {
            $q->orderBy('id');
        }])->orderBy('id')->get();

        $mapped = $questions->map(function (QuizQuestion $question) use ($locale) {
            $text = $question->question_text;
            if ($locale === 'en' && ! empty($question->question_text_en)) {
                $text = $question->question_text_en;
            }

            return [
                'id' => $question->id,
                'text' => $text,
                'options' => $question->options->map(function (QuizOption $option) use ($question, $locale) {
                    $optText = $option->option_text;
                    if ($locale === 'en' && ! empty($option->option_text_en)) {
                        $optText = $option->option_text_en;
                    }

                    return [
                        'id' => $option->id,
                        'question_id' => $question->id,
                        'text' => $optText,
                        'prestige_score' => (int) $option->prestige_score,
                        'peaceful_calm_score' => (int) $option->peaceful_calm_score,
                        'rebel_brave_score' => (int) $option->rebel_brave_score,
                        'sweet_shy_score' => (int) $option->sweet_shy_score,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json($mapped);
    }

    /**
     * GET /api/quiz/results?locale=id|en
     * Copy hasil akhir personality (title/desc) dari database.
     */
    public function getResults(Request $request)
    {
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));

        $rows = QuizPersonalityResult::query()->orderBy('id')->get();

        $mapped = $rows->mapWithKeys(function (QuizPersonalityResult $row) use ($locale) {
            $title = $row->title;
            $description = $row->description;
            if ($locale === 'en') {
                if (! empty($row->title_en)) {
                    $title = $row->title_en;
                }
                if (! empty($row->description_en)) {
                    $description = $row->description_en;
                }
            }

            return [
                $row->personality_key => [
                    'personality_key' => $row->personality_key,
                    'title' => $title,
                    'description' => $description,
                    'color' => $row->color,
                    'bg_image' => $row->bg_image,
                    'product_image' => $row->product_image,
                    'product_image_width_mobile' => $row->product_image_width_mobile,
                    'product_image_width_desktop' => $row->product_image_width_desktop,
                    'bg_image_width_mobile' => $row->bg_image_width_mobile,
                    'bg_image_width_desktop' => $row->bg_image_width_desktop,
                    'forced_product_id' => $row->forced_product_id,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'locale' => $locale,
            'data' => $mapped,
        ]);
    }

    /**
     * POST /api/quiz/submit
     */
    public function submitQuiz(Request $request)
    {
        $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|exists:quiz_questions,id',
            'answers.*.option_id' => 'required|integer|exists:quiz_options,id',
        ]);

        $user = $request->user();
        $locale = LocaleResolver::normalize($request->query('locale', $request->input('locale', 'id')));

        $scores = [
            'prestige' => 0,
            'peaceful_calm' => 0,
            'rebel_brave' => 0,
            'sweet_shy' => 0,
        ];

        DB::beginTransaction();
        try {
            foreach ($request->answers as $answer) {
                $option = QuizOption::find($answer['option_id']);
                if ($option) {
                    $scores['prestige'] += (int) $option->prestige_score;
                    $scores['peaceful_calm'] += (int) $option->peaceful_calm_score;
                    $scores['rebel_brave'] += (int) $option->rebel_brave_score;
                    $scores['sweet_shy'] += (int) $option->sweet_shy_score;
                }
            }

            $dominantPersonality = array_keys($scores, max($scores))[0];
            $dbPersonality = self::PERSONALITY_TO_DB[$dominantPersonality] ?? $dominantPersonality;
            $frontendPersonality = self::PERSONALITY_TO_FRONTEND[$dominantPersonality] ?? $dominantPersonality;

            $recommendedProduct = Product::where('personality_type', $dbPersonality)
                ->where('stock_status', '!=', 'habis')
                ->inRandomOrder()
                ->first();

            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'total_prestige' => $scores['prestige'],
                'total_peaceful_calm' => $scores['peaceful_calm'],
                'total_rebel_brave' => $scores['rebel_brave'],
                'total_sweet_shy' => $scores['sweet_shy'],
                'dominant_personality' => $dominantPersonality,
                'product_id' => $recommendedProduct?->id,
            ]);

            foreach ($request->answers as $answer) {
                UserQuizAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $answer['question_id'],
                    'quiz_option_id' => $answer['option_id'],
                ]);
            }

            DB::commit();

            $attempt->load('recommendedProduct');
            $totalScore = array_sum($scores) ?: 1;
            $matchPercentage = (int) round((max($scores) / $totalScore) * 100);

            $resultCopy = QuizPersonalityResult::where('personality_key', $frontendPersonality)->first();
            $resultPayload = null;
            if ($resultCopy) {
                $title = $resultCopy->title;
                $description = $resultCopy->description;
                if ($locale === 'en') {
                    if (! empty($resultCopy->title_en)) {
                        $title = $resultCopy->title_en;
                    }
                    if (! empty($resultCopy->description_en)) {
                        $description = $resultCopy->description_en;
                    }
                }
                $resultPayload = [
                    'personality_key' => $resultCopy->personality_key,
                    'title' => $title,
                    'description' => $description,
                    'color' => $resultCopy->color,
                    'bg_image' => $resultCopy->bg_image,
                    'product_image' => $resultCopy->product_image,
                    'forced_product_id' => $resultCopy->forced_product_id,
                ];
            }

            return response()->json([
                'message' => $locale === 'en' ? 'Quiz submitted successfully' : 'Quiz berhasil disubmit',
                'id' => $attempt->id,
                'personality_type' => $frontendPersonality,
                'recommended_product' => ProductLocalizer::localize(
                    $attempt->recommendedProduct,
                    $locale,
                ),
                'match_percentage' => $matchPercentage,
                'created_at' => $attempt->created_at,
                'result_copy' => $resultPayload,
                'result' => $attempt,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $locale === 'en' ? 'Failed to process quiz' : 'Gagal memproses kuis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/quiz/history
     */
    public function history(Request $request)
    {
        $locale = LocaleResolver::normalize($request->query('locale', 'id'));

        $attempts = QuizAttempt::with('recommendedProduct')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (QuizAttempt $attempt) use ($locale) {
                $scores = [
                    $attempt->total_prestige,
                    $attempt->total_peaceful_calm,
                    $attempt->total_rebel_brave,
                    $attempt->total_sweet_shy,
                ];
                $total = array_sum($scores) ?: 1;

                return [
                    'id' => $attempt->id,
                    'personality_type' => self::PERSONALITY_TO_FRONTEND[$attempt->dominant_personality]
                        ?? $attempt->dominant_personality,
                    'recommended_product' => ProductLocalizer::localize(
                        $attempt->recommendedProduct,
                        $locale,
                    ),
                    'match_percentage' => (int) round((max($scores) / $total) * 100),
                    'created_at' => $attempt->created_at,
                ];
            });

        return response()->json($attempts);
    }
}
