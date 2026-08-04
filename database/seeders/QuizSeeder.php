<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizPersonalityResult;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserQuizAnswer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizSeeder extends Seeder
{
    /**
     * Data awalan kuis (ID + EN) — sinkron dengan frontend Scent Finder Quiz.
     * Urutan opsi: peaceful_calm, prestige (Purpose Prestige), sweet_shy, rebel_brave
     */
    public function run(): void
    {
        DB::transaction(function () {
            UserQuizAnswer::query()->delete();
            QuizAttempt::query()->delete();
            QuizOption::query()->delete();
            QuizQuestion::query()->delete();

            foreach ($this->questionsPayload() as $q) {
                $question = QuizQuestion::create([
                    'question_text' => $q['text'],
                    'question_text_en' => $q['text_en'],
                ]);

                foreach ($q['options'] as $opt) {
                    QuizOption::create([
                        'quiz_question_id' => $question->id,
                        'option_text' => $opt['text'],
                        'option_text_en' => $opt['text_en'],
                        'peaceful_calm_score' => $opt['peaceful_calm'] ?? 0,
                        'prestige_score' => $opt['prestige'] ?? 0,
                        'sweet_shy_score' => $opt['sweet_shy'] ?? 0,
                        'rebel_brave_score' => $opt['rebel_brave'] ?? 0,
                    ]);
                }
            }

            $this->seedPersonalityResults();
            $this->seedSampleResults();
        });
    }

    private function questionsPayload(): array
    {
        return [
            [
                'text' => 'Apa aktivitas akhir pekan favoritmu?',
                'text_en' => "What's your favorite weekend activity?",
                'options' => [
                    [
                        'text' => 'Bersantai menikmati ketenangan alam',
                        'text_en' => "Relaxing while enjoying nature's calm",
                        'peaceful_calm' => 1,
                    ],
                    [
                        'text' => 'Makan malam mewah dan eksklusif',
                        'text_en' => 'An exclusive fine-dining dinner',
                        'prestige' => 1,
                    ],
                    [
                        'text' => 'Piknik santai membaca buku',
                        'text_en' => 'A casual picnic while reading a book',
                        'sweet_shy' => 1,
                    ],
                    [
                        'text' => 'Olahraga atau aktivitas menantang',
                        'text_en' => 'Sports or challenging activities',
                        'rebel_brave' => 1,
                    ],
                ],
            ],
            [
                'text' => 'Bagaimana gaya berpakaian andalanmu sehari-hari?',
                'text_en' => "What's your go-to everyday style?",
                'options' => [
                    [
                        'text' => 'Casual, simpel, dan nyaman',
                        'text_en' => 'Casual, simple, and comfortable',
                        'peaceful_calm' => 1,
                    ],
                    [
                        'text' => 'Elegan, rapi, dan terstruktur',
                        'text_en' => 'Elegant, neat, and structured',
                        'prestige' => 1,
                    ],
                    [
                        'text' => 'Warna pastel dan lembut',
                        'text_en' => 'Soft pastel colors',
                        'sweet_shy' => 1,
                    ],
                    [
                        'text' => 'Sporty, edgy, dan berani',
                        'text_en' => 'Sporty, edgy, and bold',
                        'rebel_brave' => 1,
                    ],
                ],
            ],
            [
                'text' => 'Aroma seperti apa yang paling menarik perhatianmu?',
                'text_en' => 'What kind of scent catches your attention most?',
                'options' => [
                    [
                        'text' => 'Aroma laut dan udara yang sejuk',
                        'text_en' => 'Ocean scent and cool fresh air',
                        'peaceful_calm' => 1,
                    ],
                    [
                        'text' => 'Aroma kayu-kayuan dan rempah mewah',
                        'text_en' => 'Woody notes and luxurious spices',
                        'prestige' => 1,
                    ],
                    [
                        'text' => 'Aroma bunga-bunga yang manis',
                        'text_en' => 'Sweet floral notes',
                        'sweet_shy' => 1,
                    ],
                    [
                        'text' => 'Aroma citrus yang tajam dan segar',
                        'text_en' => 'Sharp, fresh citrus',
                        'rebel_brave' => 1,
                    ],
                ],
            ],
            [
                'text' => 'Kesan apa yang ingin kamu tinggalkan saat bertemu orang baru?',
                'text_en' => 'What impression do you want to leave when meeting someone new?',
                'options' => [
                    [
                        'text' => 'Tenang, suportif, dan mudah didekati',
                        'text_en' => 'Calm, supportive, and approachable',
                        'peaceful_calm' => 1,
                    ],
                    [
                        'text' => 'Misterius, karismatik, dan berwibawa',
                        'text_en' => 'Mysterious, charismatic, and authoritative',
                        'prestige' => 1,
                    ],
                    [
                        'text' => 'Hangat, pemalu, namun menggemaskan',
                        'text_en' => 'Warm, shy, yet adorable',
                        'sweet_shy' => 1,
                    ],
                    [
                        'text' => 'Penuh semangat, percaya diri, dan tegas',
                        'text_en' => 'Energetic, confident, and assertive',
                        'rebel_brave' => 1,
                    ],
                ],
            ],
            [
                'text' => 'Pilih suasana cuaca yang paling membuat mood kamu naik:',
                'text_en' => 'Pick the weather that lifts your mood the most:',
                'options' => [
                    [
                        'text' => 'Pagi hari yang sejuk dan tenang',
                        'text_en' => 'A cool, peaceful morning',
                        'peaceful_calm' => 1,
                    ],
                    [
                        'text' => 'Malam hari yang dingin dan syahdu',
                        'text_en' => 'A cold, serene night',
                        'prestige' => 1,
                    ],
                    [
                        'text' => 'Sore hari musim semi yang hangat',
                        'text_en' => 'A warm spring afternoon',
                        'sweet_shy' => 1,
                    ],
                    [
                        'text' => 'Siang hari yang terik untuk beraktivitas',
                        'text_en' => 'A hot midday for staying active',
                        'rebel_brave' => 1,
                    ],
                ],
            ],
        ];
    }

    private function seedPersonalityResults(): void
    {
        $rows = [
            [
                'personality_key' => 'purpose_prestige',
                'title' => 'Kamu adalah, Purpose Prestige',
                'title_en' => 'You are, Purpose Prestige',
                'description' => 'Menghadirkan aroma yang merefleksikan ketenangan, kepercayaan diri, dan kejelasan tujuan.',
                'description_en' => 'Presenting a scent that reflects calmness, confidence, and clarity of purpose.',
                'color' => '#1172BA',
                'bg_image' => '/src/images/kuis/purpose-kanan.png',
                'product_image' => '/src/images/kuis/purpose-produk.png',
                'forced_product_id' => '1',
            ],
            [
                'personality_key' => 'peaceful_calm',
                'title' => 'Kamu adalah, Peaceful Calm',
                'title_en' => 'You are, Peaceful Calm',
                'description' => 'Menghadirkan aroma yang menenangkan, seimbang, dan menyatu dengan diri.',
                'description_en' => 'Presenting a soothing, balanced scent that feels at one with yourself.',
                'color' => '#5EA14A',
                'bg_image' => '/src/images/kuis/peaceful-kanan.png',
                'product_image' => '/src/images/kuis/peaceful-produk.png',
                'forced_product_id' => '2',
            ],
            [
                'personality_key' => 'rebel_brave',
                'title' => 'Kamu adalah, Rebel Brave',
                'title_en' => 'You are, Rebel Brave',
                'description' => 'Merepresentasikan keberanian, energi, dan semangat untuk mengekspresikan diri.',
                'description_en' => 'Representing courage, energy, and the spirit of self-expression.',
                'color' => '#E33D35',
                'bg_image' => '/src/images/kuis/rebel-kanan.png',
                'product_image' => '/src/images/kuis/rebel-produk.png',
                'forced_product_id' => '3',
            ],
            [
                'personality_key' => 'sweet_shy',
                'title' => 'Kamu adalah, Sweet Shy',
                'title_en' => 'You are, Sweet Shy',
                'description' => 'Menghadirkan aroma lembut yang merefleksikan sisi manis, hangat, dan penuh empati.',
                'description_en' => 'Presenting a soft scent that reflects a sweet, warm, and empathetic side.',
                'color' => '#DD74A5',
                'bg_image' => '/src/images/kuis/sweet-kanan.png',
                'product_image' => '/src/images/kuis/sweet-produk.png',
                'forced_product_id' => '4',
            ],
        ];

        foreach ($rows as $row) {
            QuizPersonalityResult::updateOrCreate(
                ['personality_key' => $row['personality_key']],
                $row,
            );
        }
    }

    private function seedSampleResults(): void
    {
        $demo = User::where('email', 'demo@evomi.com')->first();
        if (!$demo) {
            return;
        }

        $samples = [
            [
                'dominant' => 'peaceful_calm',
                'scores' => [
                    'prestige' => 1,
                    'peaceful_calm' => 4,
                    'rebel_brave' => 0,
                    'sweet_shy' => 0,
                ],
                'option_indexes' => [0, 0, 0, 0, 0],
            ],
            [
                'dominant' => 'prestige',
                'scores' => [
                    'prestige' => 3,
                    'peaceful_calm' => 1,
                    'rebel_brave' => 0,
                    'sweet_shy' => 1,
                ],
                'option_indexes' => [1, 1, 1, 0, 2],
            ],
            [
                'dominant' => 'rebel_brave',
                'scores' => [
                    'prestige' => 0,
                    'peaceful_calm' => 1,
                    'rebel_brave' => 3,
                    'sweet_shy' => 1,
                ],
                'option_indexes' => [3, 3, 3, 0, 2],
            ],
        ];

        $allQuestions = QuizQuestion::with('options')->orderBy('id')->get();

        foreach ($samples as $sample) {
            $product = Product::where('personality_type', $sample['dominant'])->first();

            $attempt = QuizAttempt::create([
                'user_id' => $demo->id,
                'total_prestige' => $sample['scores']['prestige'],
                'total_peaceful_calm' => $sample['scores']['peaceful_calm'],
                'total_rebel_brave' => $sample['scores']['rebel_brave'],
                'total_sweet_shy' => $sample['scores']['sweet_shy'],
                'dominant_personality' => $sample['dominant'],
                'product_id' => $product?->id,
            ]);

            foreach ($allQuestions as $qi => $question) {
                $opts = $question->options->values();
                $optIndex = $sample['option_indexes'][$qi] ?? 0;
                $option = $opts[$optIndex] ?? $opts->first();
                if (!$option) {
                    continue;
                }

                UserQuizAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $question->id,
                    'quiz_option_id' => $option->id,
                ]);
            }
        }
    }
}
