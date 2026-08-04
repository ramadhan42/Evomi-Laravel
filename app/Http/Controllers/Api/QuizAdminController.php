<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizPersonalityResult;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuizAdminController extends Controller
{
    private const PERSONALITIES = ['prestige', 'peaceful_calm', 'rebel_brave', 'sweet_shy'];

    private const PERSONALITY_TO_FRONTEND = [
        'prestige' => 'purpose_prestige',
        'purpose_prestige' => 'purpose_prestige',
        'peaceful_calm' => 'peaceful_calm',
        'rebel_brave' => 'rebel_brave',
        'sweet_shy' => 'sweet_shy',
    ];

    /**
     * GET /api/admin/quiz/questions
     */
    public function indexQuestions()
    {
        $questions = QuizQuestion::with(['options' => fn ($q) => $q->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(fn (QuizQuestion $q) => $this->formatQuestion($q));

        return response()->json([
            'success' => true,
            'message' => 'Daftar soal kuis berhasil diambil.',
            'data' => $questions,
        ]);
    }

    /**
     * GET /api/admin/quiz/questions/{id}
     */
    public function showQuestion($id)
    {
        $question = QuizQuestion::with(['options' => fn ($q) => $q->orderBy('id')])->find($id);

        if (! $question) {
            return response()->json([
                'success' => false,
                'message' => 'Soal tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatQuestion($question),
        ]);
    }

    /**
     * POST /api/admin/quiz/questions
     */
    public function storeQuestion(Request $request)
    {
        $validated = $request->validate([
            'question_text' => 'required|string|max:2000',
            'question_text_en' => 'nullable|string|max:2000',
            'options' => 'required|array|min:2',
            'options.*.option_text' => 'required|string|max:1000',
            'options.*.option_text_en' => 'nullable|string|max:1000',
            'options.*.prestige_score' => 'nullable|integer|min:0|max:100',
            'options.*.peaceful_calm_score' => 'nullable|integer|min:0|max:100',
            'options.*.rebel_brave_score' => 'nullable|integer|min:0|max:100',
            'options.*.sweet_shy_score' => 'nullable|integer|min:0|max:100',
        ]);

        try {
            $question = DB::transaction(function () use ($validated) {
                $question = QuizQuestion::create([
                    'question_text' => $validated['question_text'],
                    'question_text_en' => $validated['question_text_en'] ?? null,
                ]);

                foreach ($validated['options'] as $opt) {
                    $question->options()->create([
                        'option_text' => $opt['option_text'],
                        'option_text_en' => $opt['option_text_en'] ?? null,
                        'prestige_score' => (int) ($opt['prestige_score'] ?? 0),
                        'peaceful_calm_score' => (int) ($opt['peaceful_calm_score'] ?? 0),
                        'rebel_brave_score' => (int) ($opt['rebel_brave_score'] ?? 0),
                        'sweet_shy_score' => (int) ($opt['sweet_shy_score'] ?? 0),
                    ]);
                }

                return $question->load(['options' => fn ($q) => $q->orderBy('id')]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Soal kuis berhasil dibuat.',
                'data' => $this->formatQuestion($question),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat soal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/admin/quiz/questions/{id}
     * Sync opsi: update yang punya id, buat yang baru, hapus yang tidak dikirim.
     */
    public function updateQuestion(Request $request, $id)
    {
        $question = QuizQuestion::find($id);

        if (! $question) {
            return response()->json([
                'success' => false,
                'message' => 'Soal tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'question_text' => 'required|string|max:2000',
            'question_text_en' => 'nullable|string|max:2000',
            'options' => 'required|array|min:2',
            'options.*.id' => 'nullable|integer|exists:quiz_options,id',
            'options.*.option_text' => 'required|string|max:1000',
            'options.*.option_text_en' => 'nullable|string|max:1000',
            'options.*.prestige_score' => 'nullable|integer|min:0|max:100',
            'options.*.peaceful_calm_score' => 'nullable|integer|min:0|max:100',
            'options.*.rebel_brave_score' => 'nullable|integer|min:0|max:100',
            'options.*.sweet_shy_score' => 'nullable|integer|min:0|max:100',
        ]);

        try {
            $question = DB::transaction(function () use ($question, $validated) {
                $question->update([
                    'question_text' => $validated['question_text'],
                    'question_text_en' => $validated['question_text_en'] ?? null,
                ]);

                $keptIds = [];

                foreach ($validated['options'] as $opt) {
                    $payload = [
                        'option_text' => $opt['option_text'],
                        'option_text_en' => $opt['option_text_en'] ?? null,
                        'prestige_score' => (int) ($opt['prestige_score'] ?? 0),
                        'peaceful_calm_score' => (int) ($opt['peaceful_calm_score'] ?? 0),
                        'rebel_brave_score' => (int) ($opt['rebel_brave_score'] ?? 0),
                        'sweet_shy_score' => (int) ($opt['sweet_shy_score'] ?? 0),
                    ];

                    if (! empty($opt['id'])) {
                        $option = QuizOption::where('id', $opt['id'])
                            ->where('quiz_question_id', $question->id)
                            ->first();

                        if ($option) {
                            $option->update($payload);
                            $keptIds[] = $option->id;

                            continue;
                        }
                    }

                    $created = $question->options()->create($payload);
                    $keptIds[] = $created->id;
                }

                $question->options()->whereNotIn('id', $keptIds)->delete();

                return $question->load(['options' => fn ($q) => $q->orderBy('id')]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Soal kuis berhasil diperbarui.',
                'data' => $this->formatQuestion($question),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui soal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/quiz/questions/{id}
     */
    public function destroyQuestion($id)
    {
        $question = QuizQuestion::find($id);

        if (! $question) {
            return response()->json([
                'success' => false,
                'message' => 'Soal tidak ditemukan.',
            ], 404);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Soal kuis berhasil dihapus.',
        ]);
    }

    /**
     * POST /api/admin/quiz/options
     */
    public function storeOption(Request $request)
    {
        $validated = $request->validate([
            'quiz_question_id' => 'required|integer|exists:quiz_questions,id',
            'option_text' => 'required|string|max:1000',
            'prestige_score' => 'nullable|integer|min:0|max:100',
            'peaceful_calm_score' => 'nullable|integer|min:0|max:100',
            'rebel_brave_score' => 'nullable|integer|min:0|max:100',
            'sweet_shy_score' => 'nullable|integer|min:0|max:100',
        ]);

        $option = QuizOption::create([
            'quiz_question_id' => $validated['quiz_question_id'],
            'option_text' => $validated['option_text'],
            'prestige_score' => (int) ($validated['prestige_score'] ?? 0),
            'peaceful_calm_score' => (int) ($validated['peaceful_calm_score'] ?? 0),
            'rebel_brave_score' => (int) ($validated['rebel_brave_score'] ?? 0),
            'sweet_shy_score' => (int) ($validated['sweet_shy_score'] ?? 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jawaban berhasil ditambahkan.',
            'data' => $this->formatOption($option),
        ], 201);
    }

    /**
     * PUT /api/admin/quiz/options/{id}
     */
    public function updateOption(Request $request, $id)
    {
        $option = QuizOption::find($id);

        if (! $option) {
            return response()->json([
                'success' => false,
                'message' => 'Jawaban tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'option_text' => 'sometimes|required|string|max:1000',
            'prestige_score' => 'nullable|integer|min:0|max:100',
            'peaceful_calm_score' => 'nullable|integer|min:0|max:100',
            'rebel_brave_score' => 'nullable|integer|min:0|max:100',
            'sweet_shy_score' => 'nullable|integer|min:0|max:100',
        ]);

        $option->update([
            'option_text' => $validated['option_text'] ?? $option->option_text,
            'prestige_score' => array_key_exists('prestige_score', $validated)
                ? (int) $validated['prestige_score']
                : $option->prestige_score,
            'peaceful_calm_score' => array_key_exists('peaceful_calm_score', $validated)
                ? (int) $validated['peaceful_calm_score']
                : $option->peaceful_calm_score,
            'rebel_brave_score' => array_key_exists('rebel_brave_score', $validated)
                ? (int) $validated['rebel_brave_score']
                : $option->rebel_brave_score,
            'sweet_shy_score' => array_key_exists('sweet_shy_score', $validated)
                ? (int) $validated['sweet_shy_score']
                : $option->sweet_shy_score,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jawaban berhasil diperbarui.',
            'data' => $this->formatOption($option->fresh()),
        ]);
    }

    /**
     * DELETE /api/admin/quiz/options/{id}
     */
    public function destroyOption($id)
    {
        $option = QuizOption::find($id);

        if (! $option) {
            return response()->json([
                'success' => false,
                'message' => 'Jawaban tidak ditemukan.',
            ], 404);
        }

        $count = QuizOption::where('quiz_question_id', $option->quiz_question_id)->count();
        if ($count <= 2) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal harus ada 2 jawaban per soal.',
            ], 422);
        }

        $option->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jawaban berhasil dihapus.',
        ]);
    }

    /**
     * GET /api/admin/quiz/scores
     */
    public function indexScores()
    {
        $scores = QuizAttempt::with(['user', 'recommendedProduct'])
            ->latest()
            ->get()
            ->map(fn (QuizAttempt $a) => $this->formatScore($a));

        return response()->json([
            'success' => true,
            'message' => 'Daftar skor kuis berhasil diambil.',
            'data' => $scores,
        ]);
    }

    /**
     * GET /api/admin/quiz/scores/{id}
     */
    public function showScore($id)
    {
        $attempt = QuizAttempt::with([
            'user',
            'recommendedProduct',
            'answers.question',
            'answers.option',
        ])->find($id);

        if (! $attempt) {
            return response()->json([
                'success' => false,
                'message' => 'Skor tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatScore($attempt, true),
        ]);
    }

    /**
     * PUT /api/admin/quiz/scores/{id}
     */
    public function updateScore(Request $request, $id)
    {
        $attempt = QuizAttempt::find($id);

        if (! $attempt) {
            return response()->json([
                'success' => false,
                'message' => 'Skor tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'total_prestige' => 'sometimes|integer|min:0',
            'total_peaceful_calm' => 'sometimes|integer|min:0',
            'total_rebel_brave' => 'sometimes|integer|min:0',
            'total_sweet_shy' => 'sometimes|integer|min:0',
            'dominant_personality' => ['sometimes', 'string', Rule::in(self::PERSONALITIES)],
            'product_id' => 'nullable|integer|exists:products,id',
        ]);

        $attempt->update($validated);
        $attempt->load(['user', 'recommendedProduct']);

        return response()->json([
            'success' => true,
            'message' => 'Skor kuis berhasil diperbarui.',
            'data' => $this->formatScore($attempt),
        ]);
    }

    /**
     * DELETE /api/admin/quiz/scores/{id}
     */
    public function destroyScore($id)
    {
        $attempt = QuizAttempt::find($id);

        if (! $attempt) {
            return response()->json([
                'success' => false,
                'message' => 'Skor tidak ditemukan.',
            ], 404);
        }

        $attempt->answers()->delete();
        $attempt->delete();

        return response()->json([
            'success' => true,
            'message' => 'Skor kuis berhasil dihapus.',
        ]);
    }

    private function formatQuestion(QuizQuestion $question): array
    {
        return [
            'id' => $question->id,
            'question_text' => $question->question_text,
            'question_text_en' => $question->question_text_en,
            'text' => $question->question_text,
            'options_count' => $question->options->count(),
            'options' => $question->options->map(fn (QuizOption $o) => $this->formatOption($o))->values(),
            'created_at' => $question->created_at,
            'updated_at' => $question->updated_at,
        ];
    }

    private function formatOption(QuizOption $option): array
    {
        return [
            'id' => $option->id,
            'quiz_question_id' => $option->quiz_question_id,
            'question_id' => $option->quiz_question_id,
            'option_text' => $option->option_text,
            'option_text_en' => $option->option_text_en,
            'text' => $option->option_text,
            'prestige_score' => (int) $option->prestige_score,
            'peaceful_calm_score' => (int) $option->peaceful_calm_score,
            'rebel_brave_score' => (int) $option->rebel_brave_score,
            'sweet_shy_score' => (int) $option->sweet_shy_score,
            'created_at' => $option->created_at,
            'updated_at' => $option->updated_at,
        ];
    }

    private function formatScore(QuizAttempt $attempt, bool $withAnswers = false): array
    {
        $scoreValues = [
            (int) $attempt->total_prestige,
            (int) $attempt->total_peaceful_calm,
            (int) $attempt->total_rebel_brave,
            (int) $attempt->total_sweet_shy,
        ];
        $total = array_sum($scoreValues) ?: 1;

        $data = [
            'id' => $attempt->id,
            'user_id' => $attempt->user_id,
            'user' => $attempt->user ? [
                'id' => $attempt->user->id,
                'name' => $attempt->user->name,
                'email' => $attempt->user->email,
            ] : null,
            'total_prestige' => (int) $attempt->total_prestige,
            'total_peaceful_calm' => (int) $attempt->total_peaceful_calm,
            'total_rebel_brave' => (int) $attempt->total_rebel_brave,
            'total_sweet_shy' => (int) $attempt->total_sweet_shy,
            'dominant_personality' => $attempt->dominant_personality,
            'personality_type' => self::PERSONALITY_TO_FRONTEND[$attempt->dominant_personality]
                ?? $attempt->dominant_personality,
            'match_percentage' => (int) round((max($scoreValues) / $total) * 100),
            'product_id' => $attempt->product_id,
            'recommended_product' => $attempt->recommendedProduct,
            'created_at' => $attempt->created_at,
            'updated_at' => $attempt->updated_at,
        ];

        if ($withAnswers) {
            $data['answers'] = $attempt->answers->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'question_id' => $answer->quiz_question_id,
                    'question_text' => $answer->question?->question_text,
                    'question_text_en' => $answer->question?->question_text_en,
                    'option_id' => $answer->quiz_option_id,
                    'option_text' => $answer->option?->option_text,
                    'option_text_en' => $answer->option?->option_text_en,
                ];
            })->values();
        }

        return $data;
    }

    private const RESULT_PERSONALITY_KEYS = [
        'purpose_prestige',
        'peaceful_calm',
        'rebel_brave',
        'sweet_shy',
    ];

    /**
     * GET /api/admin/quiz/results
     */
    public function indexResults()
    {
        $rows = QuizPersonalityResult::query()
            ->orderBy('id')
            ->get()
            ->map(fn (QuizPersonalityResult $row) => $this->formatResult($row));

        return response()->json([
            'success' => true,
            'message' => 'Daftar hasil kuis berhasil diambil.',
            'data' => $rows,
        ]);
    }

    /**
     * PUT /api/admin/quiz/results/{personalityKey}
     */
    public function updateResult(Request $request, string $personalityKey)
    {
        if (! in_array($personalityKey, self::RESULT_PERSONALITY_KEYS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Personality key tidak valid.',
            ], 404);
        }

        $validated = $request->validate([
            'product_image' => 'sometimes|nullable|string|max:2048',
            'bg_image' => 'sometimes|nullable|string|max:2048',
            'product_image_width_mobile' => 'sometimes|nullable|integer|min:40|max:1200',
            'product_image_width_desktop' => 'sometimes|nullable|integer|min:40|max:1600',
            'bg_image_width_mobile' => 'sometimes|nullable|integer|min:40|max:1200',
            'bg_image_width_desktop' => 'sometimes|nullable|integer|min:40|max:1600',
            'title' => 'sometimes|nullable|string|max:255',
            'title_en' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string|max:5000',
            'description_en' => 'sometimes|nullable|string|max:5000',
            'color' => 'sometimes|nullable|string|max:32',
            'forced_product_id' => 'sometimes|nullable|string|max:64',
        ]);

        $row = QuizPersonalityResult::query()->where('personality_key', $personalityKey)->first();

        if (! $row) {
            return response()->json([
                'success' => false,
                'message' => 'Hasil kuis tidak ditemukan.',
            ], 404);
        }

        $row->fill($validated);
        $row->save();

        return response()->json([
            'success' => true,
            'message' => 'Hasil kuis berhasil diperbarui.',
            'data' => $this->formatResult($row->fresh()),
        ]);
    }

    private function formatResult(QuizPersonalityResult $row): array
    {
        return [
            'id' => $row->id,
            'personality_key' => $row->personality_key,
            'title' => $row->title,
            'title_en' => $row->title_en,
            'description' => $row->description,
            'description_en' => $row->description_en,
            'color' => $row->color,
            'bg_image' => $row->bg_image,
            'product_image' => $row->product_image,
            'product_image_width_mobile' => $row->product_image_width_mobile,
            'product_image_width_desktop' => $row->product_image_width_desktop,
            'bg_image_width_mobile' => $row->bg_image_width_mobile,
            'bg_image_width_desktop' => $row->bg_image_width_desktop,
            'forced_product_id' => $row->forced_product_id,
            'updated_at' => $row->updated_at,
        ];
    }
}
