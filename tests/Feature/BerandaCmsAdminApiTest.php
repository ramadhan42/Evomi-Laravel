<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use App\Models\User;
use App\Support\BerandaCmsDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BerandaCmsAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'email' => 'cms-admin@evomi.test',
            'password' => Hash::make('password123'),
            'is_admin' => true,
        ]);
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_admin_beranda_returns_catalog_defaults_when_db_empty(): void
    {
        $this->actingAsAdmin();

        $res = $this->getJson('/api/admin/cms/beranda?locale=id');
        $res->assertOk()->assertJsonPath('success', true);

        $data = collect($res->json('data'));
        $this->assertTrue($data->contains(fn ($r) => ($r['section'] ?? '') === 'third' && ($r['key'] ?? '') === 'tagline'));
        $this->assertTrue($data->contains(fn ($r) => ($r['section'] ?? '') === 'fifth' && ($r['key'] ?? '') === 'title_1'));
        $this->assertTrue($data->contains(fn ($r) => ($r['section'] ?? '') === 'sixth' && ($r['key'] ?? '') === 'marquee_text'));

        $hlFs = $data->first(fn ($r) => ($r['section'] ?? '') === 'seventh' && ($r['key'] ?? '') === 'headline_1_fs_desktop');
        $this->assertSame('55px', $hlFs['value'] ?? null);

        $this->assertGreaterThanOrEqual(count(BerandaCmsDefaults::rows()), $data->count());
    }

    public function test_admin_beranda_keeps_existing_db_values(): void
    {
        $this->actingAsAdmin();

        SiteContent::create([
            'page' => 'beranda',
            'section' => 'sixth',
            'key' => 'title_1',
            'locale' => 'id',
            'type' => 'string',
            'value' => 'Packaging Live',
        ]);

        $res = $this->getJson('/api/admin/cms/beranda?locale=id');
        $res->assertOk();

        $title = collect($res->json('data'))->first(
            fn ($r) => ($r['section'] ?? '') === 'sixth' && ($r['key'] ?? '') === 'title_1'
        );
        $this->assertSame('Packaging Live', $title['value'] ?? null);
    }

    public function test_admin_beranda_en_does_not_auto_fill_copy(): void
    {
        $this->actingAsAdmin();

        $res = $this->getJson('/api/admin/cms/beranda?locale=en');
        $res->assertOk();

        $copy = collect($res->json('data'))->first(
            fn ($r) => ($r['section'] ?? '') === 'hero' && ($r['key'] ?? '') === 'headline_1'
        );
        $style = collect($res->json('data'))->first(
            fn ($r) => ($r['section'] ?? '') === 'hero' && ($r['key'] ?? '') === 'headline_1_fs_mobile'
        );

        $this->assertTrue($copy['value'] === null || $copy['value'] === '');
        $this->assertSame('28px', $style['value'] ?? null);
    }
}
