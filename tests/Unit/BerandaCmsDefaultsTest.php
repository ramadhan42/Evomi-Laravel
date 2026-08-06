<?php

namespace Tests\Unit;

use App\Support\BerandaCmsDefaults;
use PHPUnit\Framework\TestCase;

class BerandaCmsDefaultsTest extends TestCase
{
    public function test_catalog_includes_core_sections_and_typography(): void
    {
        $rows = BerandaCmsDefaults::rows();
        $this->assertNotEmpty($rows);

        $map = [];
        foreach ($rows as [$section, $key, $type, $value]) {
            $map[$section.'|'.$key] = [$type, $value];
        }

        $this->assertSame('Temukan', $map['hero|headline_1'][1]);
        $this->assertSame('28px', $map['hero|headline_1_fs_mobile'][1]);
        $this->assertSame('42px', $map['hero|headline_1_fs_desktop'][1]);
        $this->assertSame('600', $map['hero|headline_1_font_weight'][1]);
        $this->assertSame('1', $map['hero|headline_1_max_lines'][1]);

        $this->assertSame("Rebel\nBrave", $map['second|card3_name'][1]);
        $this->assertSame('16px', $map['second|card1_name_fs_mobile'][1]);
        $this->assertSame('24px', $map['second|card1_name_fs_desktop'][1]);

        $this->assertSame('Brand', $map['third|title_1'][1]);
        $this->assertSame('Every Version of Me', $map['third|tagline'][1]);
        $this->assertSame('700', $map['third|tagline_font_weight'][1]);

        $this->assertSame('Khas', $map['fifth|title_1'][1]);
        $this->assertSame('Lihat Koleksi', $map['fifth|cta_label'][1]);
        $this->assertSame('13px', $map['fifth|card1_title_fs_mobile'][1]);
        $this->assertSame('16px', $map['fifth|card1_title_fs_desktop'][1]);

        $this->assertSame('Packaging', $map['sixth|title_1'][1]);
        $this->assertSame('Every Version of Me', $map['sixth|marquee_text'][1]);
        $this->assertSame('500', $map['sixth|label1_font_weight'][1]);
        $this->assertSame('9px', $map['sixth|label1_fs_mobile'][1]);

        $this->assertSame('Mulai Kuis', $map['seventh|cta_label'][1]);
        $this->assertSame('30px', $map['seventh|headline_1_fs_mobile'][1]);
        $this->assertSame('55px', $map['seventh|headline_1_fs_desktop'][1]);
        $this->assertSame('14px', $map['seventh|cta_label_fs_mobile'][1]);
        $this->assertSame('19px', $map['seventh|cta_label_fs_desktop'][1]);
    }

    public function test_merge_prefers_database_values(): void
    {
        $db = [
            [
                'id' => 10,
                'page' => 'beranda',
                'section' => 'sixth',
                'key' => 'title_1',
                'locale' => 'id',
                'type' => 'string',
                'value' => 'Custom Title',
            ],
        ];

        $merged = BerandaCmsDefaults::mergeAdminRows($db, 'id');
        $title = collect($merged)->firstWhere(fn ($r) => $r['section'] === 'sixth' && $r['key'] === 'title_1');

        $this->assertSame(10, $title['id']);
        $this->assertSame('Custom Title', $title['value']);

        $label = collect($merged)->firstWhere(fn ($r) => $r['section'] === 'sixth' && $r['key'] === 'label1');
        $this->assertNull($label['id']);
        $this->assertSame("Purpose\nPrestige", $label['value']);
    }

    public function test_merge_en_leaves_copy_blank_but_fills_style(): void
    {
        $merged = BerandaCmsDefaults::mergeAdminRows([], 'en');
        $copy = collect($merged)->firstWhere(fn ($r) => $r['section'] === 'hero' && $r['key'] === 'headline_1');
        $style = collect($merged)->firstWhere(fn ($r) => $r['section'] === 'hero' && $r['key'] === 'headline_1_fs_desktop');

        $this->assertNull($copy['value']);
        $this->assertSame('42px', $style['value']);
        $this->assertTrue(BerandaCmsDefaults::isLayoutStyleKey('headline_1_fs_desktop'));
        $this->assertFalse(BerandaCmsDefaults::isLayoutStyleKey('headline_1'));
    }
}
