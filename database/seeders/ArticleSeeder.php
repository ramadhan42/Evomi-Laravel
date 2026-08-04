<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'title' => 'Cara Memilih Parfum Sesuai Kepribadian',
                'title_en' => 'How to Choose Perfume That Matches Your Personality',
                'slug' => 'cara-memilih-parfum-sesuai-kepribadian',
                'excerpt' => 'Setiap aroma punya cerita. Temukan wewangian yang paling dekat dengan karaktermu.',
                'excerpt_en' => 'Every scent tells a story. Find the fragrance that feels closest to who you are.',
                'content' => "Memilih parfum bukan hanya soal tren, tapi soal bagaimana aroma itu mewakili diri Anda.\n\nMulailah dari pertanyaan sederhana: apakah Anda cenderung tenang, berani, manis, atau elegan? Evomi merancang empat karakter aroma agar pencarian ini lebih mudah.\n\nCoba semprotkan di kulit, bukan hanya di kertas tester. Panas tubuh mengubah catatan aroma dan memberi kesan yang lebih jujur.\n\nTerakhir, dengarkan intuisi. Jika satu hembusan membuat Anda merasa lebih yakin, biasanya itu sudah jawaban yang tepat.",
                'content_en' => "Choosing perfume is not only about trends — it is about how a scent represents you.\n\nStart with a simple question: are you calm, bold, soft, or elegant? Evomi designed four scent personas to make that search easier.\n\nSpray on skin, not only on blotter paper. Body heat changes the notes and reveals a more honest impression.\n\nFinally, trust your instinct. If one spray makes you feel more confident, that is usually the right answer.",
                'image' => '/src/images/articles/article-01.jpg',
                'author' => 'Evomi Editorial',
            ],
            [
                'title' => 'Mengenal Top, Middle, dan Base Note',
                'title_en' => 'Understanding Top, Middle, and Base Notes',
                'slug' => 'mengenal-top-middle-dan-base-note',
                'excerpt' => 'Struktur aroma adalah arsitektur keharuman yang membuat parfum terasa hidup sepanjang hari.',
                'excerpt_en' => 'Scent structure is the architecture that keeps a fragrance alive through the day.',
                'content' => "Setiap parfum disusun dalam lapisan yang disebut pyramid notes.\n\nTop note adalah kesan pertama — biasanya segar dan cepat menguap. Middle note menjadi jantung aroma yang muncul setelah beberapa menit. Base note adalah fondasi hangat yang bertahan paling lama.\n\nMemahami tiga lapisan ini membantu Anda menilai apakah sebuah parfum cocok untuk pagi, malam, kerja, atau momen spesial.\n\nDi Evomi, setiap karakter dirancang agar perjalanan aroma terasa utuh dari detik pertama hingga sisa harum di malam hari.",
                'content_en' => "Every perfume is built in layers called the scent pyramid.\n\nTop notes are the first impression — often fresh and quick to fade. Middle notes become the heart after a few minutes. Base notes are the warm foundation that lasts the longest.\n\nUnderstanding these layers helps you judge whether a perfume fits morning, night, work, or special moments.\n\nAt Evomi, each persona is crafted so the journey feels complete from the first second to the lingering trail at night.",
                'image' => '/src/images/articles/article-02.jpg',
                'author' => 'Evomi Editorial',
            ],
            [
                'title' => 'Tips Menyimpan Parfum Agar Aroma Awet',
                'title_en' => 'How to Store Perfume So the Scent Stays Fresh',
                'slug' => 'tips-menyimpan-parfum-agar-aroma-awet',
                'excerpt' => 'Cahaya, panas, dan udara adalah musuh diam-diam kualitas wewangian Anda.',
                'excerpt_en' => 'Light, heat, and air are silent enemies of fragrance quality.',
                'content' => "Simpan botol di tempat sejuk, gelap, dan kering. Lemari tertutup jauh lebih baik daripada meja yang terkena sinar matahari.\n\nHindari kamar mandi karena uap panas dan kelembapan bisa merusak komposisi. Jangan juga biarkan tutup botol terbuka terlalu lama.\n\nJika Anda sering bepergian, gunakan decant kecil. Botol utama sebaiknya tetap di rumah agar lebih aman.\n\nPerawatan sederhana ini menjaga karakter aroma Evomi tetap jernih dan konsisten.",
                'content_en' => "Keep bottles in a cool, dark, dry place. A closed cabinet is far better than a sunny table.\n\nAvoid bathrooms because heat and humidity can damage the composition. Do not leave the cap open for long either.\n\nIf you travel often, use a small decant. Keep the main bottle at home for safety.\n\nThese simple habits keep Evomi’s character clear and consistent.",
                'image' => '/src/images/articles/article-03.jpg',
                'author' => 'Evomi Lab',
            ],
            [
                'title' => 'Parfum untuk Siang vs Malam: Apa Bedanya?',
                'title_en' => 'Day vs Night Perfume: What’s the Difference?',
                'slug' => 'parfum-untuk-siang-vs-malam',
                'excerpt' => 'Intensitas dan karakter aroma sebaiknya mengikuti ritme hari Anda.',
                'excerpt_en' => 'Intensity and character should follow the rhythm of your day.',
                'content' => "Untuk siang hari, pilih aroma yang lebih ringan dan bersih agar tetap nyaman di ruang kerja maupun aktivitas outdoor.\n\nMalam hari memberi ruang untuk aroma yang lebih dalam, hangat, dan berkesan. Catatan woody, amber, atau floral pekat biasanya lebih menonjol di pencahayaan malam.\n\nEvomi punya spektrum dari Peaceful Calm yang lembut hingga Rebel Brave yang bertenaga — cocok untuk menyesuaikan mood harian Anda.\n\nYang terpenting: jangan berlebihan. Dua hingga tiga semprotan biasanya sudah cukup.",
                'content_en' => "For daytime, choose lighter and cleaner scents so they stay comfortable at work or outdoors.\n\nNighttime allows deeper, warmer, more memorable notes. Woody, amber, or rich floral tones usually shine after dark.\n\nEvomi ranges from soft Peaceful Calm to energetic Rebel Brave — ideal for matching your daily mood.\n\nMost importantly: do not overspray. Two to three sprays are usually enough.",
                'image' => '/src/images/articles/article-04.jpg',
                'author' => 'Evomi Editorial',
            ],
            [
                'title' => 'Kenapa Skin Chemistry Mempengaruhi Aroma Parfum',
                'title_en' => 'Why Skin Chemistry Changes How Perfume Smells',
                'slug' => 'kenapa-skin-chemistry-mempengaruhi-aroma',
                'excerpt' => 'Parfum yang sama bisa terasa berbeda di setiap orang — dan itu normal.',
                'excerpt_en' => 'The same perfume can smell different on everyone — and that is normal.',
                'content' => "Kulit memiliki pH, kadar minyak, dan suhu yang unik. Faktor ini mengubah cara molekul aroma berkembang.\n\nItulah sebabnya tester di toko kadang terasa beda saat dipakai seharian. Kulit kering biasanya membuat aroma lebih cepat hilang, sementara kulit berminyak menahan base note lebih lama.\n\nHidrasi dan pelembap ringan sebelum semprot bisa membantu daya tahan aroma.\n\nJadi, uji parfum Evomi di kulit Anda sendiri sebelum memutuskan favorit.",
                'content_en' => "Skin has unique pH, oil levels, and temperature. These factors change how scent molecules unfold.\n\nThat is why a store tester can feel different after a full day of wear. Dry skin often fades faster, while oilier skin holds base notes longer.\n\nHydration and a light moisturizer before spraying can improve longevity.\n\nSo test Evomi on your own skin before choosing a favorite.",
                'image' => '/src/images/articles/article-05.jpg',
                'author' => 'Evomi Lab',
            ],
            [
                'title' => 'Panduan Layering Parfum untuk Pemula',
                'title_en' => 'A Beginner’s Guide to Perfume Layering',
                'slug' => 'panduan-layering-parfum-untuk-pemula',
                'excerpt' => 'Menggabungkan dua aroma bisa menciptakan signature scent yang lebih personal.',
                'excerpt_en' => 'Combining two scents can create a more personal signature fragrance.',
                'content' => "Layering dimulai dari aroma yang lebih ringan sebagai dasar, lalu tambahkan aroma yang lebih dalam.\n\nHindari menggabungkan dua parfum yang sama-sama sangat tajam. Lebih aman memadukan floral lembut dengan woody ringan, atau citrus segar dengan musk.\n\nSemprot pada titik denyut: pergelangan, leher, dan belakang telinga. Biarkan masing-masing lapisan mengering sebelum menambahkan berikutnya.\n\nDengan karakter Evomi yang jelas, Anda bisa bereksperimen tanpa kehilangan identitas aroma.",
                'content_en' => "Start layering with a lighter scent as the base, then add something deeper.\n\nAvoid pairing two very sharp perfumes. It is safer to mix soft floral with light woody, or fresh citrus with musk.\n\nSpray pulse points: wrists, neck, and behind the ears. Let each layer dry before adding the next.\n\nWith Evomi’s clear personas, you can experiment without losing scent identity.",
                'image' => '/src/images/articles/article-06.jpg',
                'author' => 'Evomi Editorial',
            ],
            [
                'title' => 'Eau de Parfum vs Eau de Toilette',
                'title_en' => 'Eau de Parfum vs Eau de Toilette',
                'slug' => 'eau-de-parfum-vs-eau-de-toilette',
                'excerpt' => 'Konsentrasi menentukan kekuatan dan lama bertahannya sebuah wewangian.',
                'excerpt_en' => 'Concentration decides how strong and how long a fragrance lasts.',
                'content' => "Eau de Parfum (EDP) biasanya mengandung konsentrasi minyak wangi yang lebih tinggi dibanding Eau de Toilette (EDT).\n\nHasilnya, EDP cenderung lebih lama di kulit dan terasa lebih kaya. EDT lebih ringan dan sering dipilih untuk cuaca panas atau aktivitas singkat.\n\nKoleksi Evomi mengutamakan karakter yang terasa jelas dan berkesan, sehingga cocok untuk Anda yang ingin aroma tetap hadir sepanjang hari.\n\nPilih berdasarkan kebutuhan: intensitas, durasi, dan kenyamanan.",
                'content_en' => "Eau de Parfum (EDP) usually has a higher fragrance oil concentration than Eau de Toilette (EDT).\n\nAs a result, EDP tends to last longer and feel richer. EDT is lighter and often chosen for hot weather or shorter activities.\n\nEvomi focuses on characters that feel clear and memorable, ideal if you want presence throughout the day.\n\nChoose by need: intensity, longevity, and comfort.",
                'image' => '/src/images/articles/article-07.jpg',
                'author' => 'Evomi Lab',
            ],
            [
                'title' => 'Aroma Floral, Woody, dan Fresh: Mana Cocok Buatmu?',
                'title_en' => 'Floral, Woody, and Fresh: Which Fits You?',
                'slug' => 'aroma-floral-woody-dan-fresh',
                'excerpt' => 'Keluarga aroma membantu mempersempit pilihan sebelum Anda jatuh cinta pada satu botol.',
                'excerpt_en' => 'Fragrance families help narrow choices before you fall for one bottle.',
                'content' => "Floral terasa feminin, lembut, dan romantis. Woody memberi kesan hangat, dewasa, dan grounded. Fresh membawa energi bersih seperti citrus, green, atau aquatic.\n\nTidak ada yang mutlak benar atau salah — yang ada adalah kecocokan dengan gaya hidup.\n\nJika Anda baru memulai, coba bandingkan dua keluarga aroma di hari berbeda. Catat bagaimana orang sekitar merespons dan bagaimana Anda merasa.\n\nEvomi menyederhanakan proses itu lewat pendekatan berbasis karakter.",
                'content_en' => "Floral feels soft, romantic, and graceful. Woody feels warm, mature, and grounded. Fresh brings clean energy like citrus, green, or aquatic.\n\nNothing is absolutely right or wrong — only what fits your lifestyle.\n\nIf you are starting out, compare two families on different days. Note how people respond and how you feel.\n\nEvomi simplifies that journey through a persona-based approach.",
                'image' => '/src/images/articles/article-08.jpg',
                'author' => 'Evomi Editorial',
            ],
            [
                'title' => 'Etika Memakai Parfum di Ruang Publik',
                'title_en' => 'Perfume Etiquette in Public Spaces',
                'slug' => 'etika-memakai-parfum-di-ruang-publik',
                'excerpt' => 'Wewangian yang elegan justru yang memberi ruang bagi orang lain untuk bernapas nyaman.',
                'excerpt_en' => 'Elegant fragrance leaves room for others to breathe comfortably.',
                'content' => "Di kantor, transportasi, atau ruang tertutup, gunakan semprotan lebih sedikit. Aroma yang terlalu kuat bisa mengganggu.\n\nSemprot dari jarak wajar dan biarkan mengering sebelum berangkat. Hindari menyemprot di lift atau kerumunan.\n\nJika Anda ragu, minta pendapat orang terdekat. Feedback jujur lebih berharga daripada asumsi.\n\nParfum Evomi dirancang untuk berkesan, bukan untuk mendominasi ruangan.",
                'content_en' => "At work, on transit, or in closed rooms, use fewer sprays. An overly strong scent can bother others.\n\nSpray from a reasonable distance and let it dry before you leave. Avoid spraying in elevators or crowds.\n\nIf unsure, ask someone close to you. Honest feedback beats assumptions.\n\nEvomi is designed to be memorable, not to dominate a room.",
                'image' => '/src/images/articles/article-09.jpg',
                'author' => 'Evomi Editorial',
            ],
            [
                'title' => 'Membangun Signature Scent yang Bertahan Lama',
                'title_en' => 'Building a Long-Lasting Signature Scent',
                'slug' => 'membangun-signature-scent-yang-bertahan-lama',
                'excerpt' => 'Signature scent adalah jejak identitas — aroma yang orang ingat sebagai dirimu.',
                'excerpt_en' => 'A signature scent is an identity trail — the aroma people remember as you.',
                'content' => "Pilih satu hingga dua aroma utama yang paling sering Anda pakai. Konsistensi membuat orang mengasosiasikan wewangian itu dengan Anda.\n\nLengkapi dengan ritual kecil: pelembap tanpa aroma, semprotan di titik yang tepat, dan penyimpanan yang benar.\n\nSesekali Anda bisa berganti untuk musim atau momen spesial, tetapi biarkan ada satu karakter yang menjadi ‘rumah’.\n\nDengan Evomi, signature scent tidak hanya harum — ia menjadi cermin kepribadian.",
                'content_en' => "Choose one or two main scents you wear most often. Consistency helps people associate that fragrance with you.\n\nAdd small rituals: unscented moisturizer, precise pulse-point sprays, and proper storage.\n\nYou can still switch for seasons or special moments, but keep one character as ‘home’.\n\nWith Evomi, a signature scent is not only beautiful — it mirrors personality.",
                'image' => '/src/images/articles/article-10.jpg',
                'author' => 'Evomi Editorial',
            ],
            [
                'title' => 'Cara Menyemprot Parfum Agar Projection Maksimal',
                'title_en' => 'How to Spray Perfume for Maximum Projection',
                'slug' => 'cara-menyemprot-parfum-agar-projection-maksimal',
                'excerpt' => 'Teknik semprot yang tepat membuat aroma lebih merata, awet, dan terasa elegan.',
                'excerpt_en' => 'The right spray technique makes scent more even, longer-lasting, and elegant.',
                'content' => "Semprot dari jarak sekitar 15–20 cm agar kabut aroma menyebar tipis, bukan menumpuk di satu titik.\n\nFokus pada pulse points: leher, pergelangan tangan, dan belakang telinga. Area itu lebih hangat sehingga membantu molekul aroma terbuka.\n\nHindari menggosok pergelangan setelah semprot — gerakan itu justru memecah note dan mempercepat pudar.\n\nDengan teknik sederhana ini, karakter Evomi terasa lebih jernih dari hembusan pertama hingga sisa harum di sore hari.",
                'content_en' => "Spray from about 15–20 cm so the mist lands lightly instead of pooling in one spot.\n\nFocus on pulse points: neck, wrists, and behind the ears. Warmer skin helps scent molecules open up.\n\nAvoid rubbing your wrists after spraying — that breaks the notes and makes them fade faster.\n\nWith this simple technique, Evomi’s character stays clearer from the first breath to the afternoon trail.",
                'image' => '/src/images/articles/article-11.jpg',
                'author' => 'Evomi Lab',
            ],
            [
                'title' => 'Memilih Parfum untuk Cuaca Tropis Indonesia',
                'title_en' => 'Choosing Perfume for Indonesia’s Tropical Weather',
                'slug' => 'memilih-parfum-untuk-cuaca-tropis-indonesia',
                'excerpt' => 'Kelembapan tinggi meminta aroma yang ringan, segar, dan tetap nyaman sepanjang hari.',
                'excerpt_en' => 'High humidity calls for scents that stay light, fresh, and comfortable all day.',
                'content' => "Di iklim tropis, aroma yang terlalu berat bisa terasa menyesakkan. Pilih karakter yang lebih fresh, citrus, atau floral ringan untuk aktivitas siang.\n\nKurangi jumlah semprotan saat udara lembap. Satu hingga dua semprotan sering sudah cukup karena panas tubuh memperkuat projection.\n\nSimpan botol jauh dari AC langsung dan sinar matahari. Perubahan suhu drastis bisa mengganggu kestabilan formula.\n\nEvomi menawarkan spektrum aroma yang tetap nyaman dipakai di cuaca Indonesia — dari tenang hingga berani, tanpa kehilangan kejelasan karakter.",
                'content_en' => "In a tropical climate, heavy scents can feel overwhelming. Choose fresher, citrus, or light floral characters for daytime.\n\nUse fewer sprays in humid air. One or two is often enough because body heat boosts projection.\n\nKeep bottles away from direct AC blasts and sunlight. Sharp temperature swings can unsettle the formula.\n\nEvomi offers a range that stays comfortable in Indonesian weather — from calm to bold — without losing character clarity.",
                'image' => '/src/images/articles/article-12.jpg',
                'author' => 'Evomi Editorial',
            ],
        ];

        foreach ($articles as $index => $item) {
            Article::updateOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, [
                    'category' => 'parfum',
                    'is_published' => true,
                    'published_at' => now()->subDays(count($articles) - $index),
                ])
            );
        }
    }
}
