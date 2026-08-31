<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use App\Models\User;
use App\Support\CmsStorefront;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Field teks CMS disunting dengan editor seperti di menu artikel, jadi nilainya
 * bisa berupa HTML inline. Yang diuji di sini: apa yang boleh tersimpan, dan
 * bagaimana halaman mencetaknya.
 */
class CmsRichTextTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'email' => 'cms-rich@evomi.test',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function saveText(string $page, string $section, string $key, string $value): void
    {
        $this->putJson("/api/admin/cms/{$page}?locale=id", [
            'fields' => [
                ['section' => $section, 'key' => $key, 'type' => 'text', 'value' => $value],
            ],
        ])->assertOk();
    }

    public function test_inline_formatting_survives_saving(): void
    {
        $this->actingAsAdmin();

        $this->saveText(
            'beranda',
            'fifth',
            'subtitle',
            'Empat karakter aroma yang <b>mewakili</b> <span style="color: #1172BA">sisimu</span>.'
        );

        $stored = SiteContent::where('page', 'beranda')
            ->where('section', 'fifth')
            ->where('key', 'subtitle')
            ->where('locale', 'id')
            ->value('value');

        $this->assertStringContainsString('<b>mewakili</b>', (string) $stored);
        $this->assertStringContainsString('color: #1172BA', (string) $stored);
    }

    public function test_block_markup_and_scripts_are_stripped_on_save(): void
    {
        $this->actingAsAdmin();

        $this->saveText(
            'beranda',
            'fifth',
            'subtitle',
            '<h1>Judul</h1><script>alert(1)</script><p onclick="x()">Isi</p>'
        );

        $stored = (string) SiteContent::where('page', 'beranda')
            ->where('section', 'fifth')
            ->where('key', 'subtitle')
            ->where('locale', 'id')
            ->value('value');

        $this->assertStringNotContainsString('<h1', $stored);
        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('onclick', $stored);
        $this->assertStringContainsString('Judul', $stored);
        $this->assertStringContainsString('Isi', $stored);
    }

    public function test_title_fields_are_sanitised_even_though_they_are_stored_as_string(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/admin/cms/beranda?locale=id', [
            'fields' => [
                [
                    'section' => 'hero',
                    'key' => 'headline_1',
                    'type' => 'string',
                    'value' => '<h2>Temu</h2><span style="color: #FF8A84" onmouseover="x()">kan</span>',
                ],
            ],
        ])->assertOk();

        $stored = (string) SiteContent::where('page', 'beranda')
            ->where('section', 'hero')
            ->where('key', 'headline_1')
            ->where('locale', 'id')
            ->value('value');

        $this->assertStringNotContainsString('<h2', $stored);
        $this->assertStringNotContainsString('onmouseover', $stored);
        $this->assertStringContainsString('color: #FF8A84', $stored);
        $this->assertStringContainsString('kan', $stored);
    }

    public function test_plain_text_values_are_escaped_when_rendered(): void
    {
        SiteContent::create([
            'page' => 'beranda',
            'section' => 'fifth',
            'key' => 'subtitle',
            'locale' => 'id',
            'type' => 'text',
            'value' => 'Aroma <untuk> semua & siapa saja',
        ]);

        $html = CmsStorefront::forPage('beranda')->richText('fifth', 'subtitle', '', 3);

        $this->assertSame('Aroma &lt;untuk&gt; semua &amp; siapa saja', $html);
    }

    public function test_lines_are_split_per_break_and_clamped(): void
    {
        SiteContent::insert([
            [
                'page' => 'beranda',
                'section' => 'second',
                'key' => 'card1_name',
                'locale' => 'id',
                'type' => 'text',
                'value' => '<span style="color: #ff0000">Pur<b>pose</b><br>Presti</span>ge<br>Ketiga',
            ],
            [
                'page' => 'beranda',
                'section' => 'second',
                'key' => 'card1_name_max_lines',
                'locale' => 'id',
                'type' => 'string',
                'value' => '2',
            ],
        ]);

        $lines = CmsStorefront::forPage('beranda')->richLines('second', 'card1_name', '', 2);

        // Tag yang terbuka saat <br> ditutup lalu dibuka lagi di baris berikutnya,
        // supaya tiap baris tetap utuh saat dicetak ke elemen sendiri-sendiri.
        $this->assertSame([
            '<span style="color: #ff0000">Pur<b>pose</b></span>',
            '<span style="color: #ff0000">Presti</span>ge',
        ], $lines);
    }
}
