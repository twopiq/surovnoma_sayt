<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_switch_language(): void
    {
        $this->get('/')->assertOk()->assertSee('Qaysi yo', false);

        $this->from('/')->post(route('locale.switch', 'ru'))->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('Какой путь вам подходит?')->assertDontSee('Qaysi yo', false);

        $this->post(route('locale.switch', 'en'));
        $this->get('/')->assertOk()->assertSee('Which way suits you?');
    }

    public function test_unknown_language_is_rejected(): void
    {
        $this->post('/locale/de')->assertNotFound();
    }

    public function test_user_choice_is_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('locale.switch', 'en'));

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_every_translation_key_exists_in_all_languages(): void
    {
        $pattern = '/__\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/';
        $keys = [];

        $files = Finder::create()->files()->in([resource_path('views'), app_path()])->name('*.php');

        foreach ($files as $file) {
            preg_match_all($pattern, $file->getContents(), $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $key = isset($match[2]) ? stripcslashes($match[2]) : str_replace(["\\'", '\\\\'], ["'", '\\'], $match[1]);

                if (! preg_match('/^[a-z_]+\.[a-z_.]+$/', $key)) {
                    $keys[$key] = true;
                }
            }
        }

        foreach (array_keys(Locales::AVAILABLE) as $locale) {
            if ($locale === 'uz') {
                continue;
            }

            $json = json_decode(file_get_contents(lang_path("{$locale}.json")), true);
            $missing = array_values(array_diff(array_keys($keys), array_keys($json)));

            $this->assertSame([], $missing, "lang/{$locale}.json da tarjima yo'q");

            foreach (['auth', 'passwords', 'pagination', 'validation'] as $group) {
                $uzbek = Arr::dot(require lang_path("uz/{$group}.php"));
                $other = Arr::dot(require lang_path("{$locale}/{$group}.php"));

                foreach (array_keys($uzbek) as $key) {
                    if ($key === 'custom' || str_starts_with($key, 'custom.')) {
                        continue;
                    }

                    $this->assertArrayHasKey($key, $other, "lang/{$locale}/{$group}.php da «{$key}» yo'q");
                }
            }
        }
    }
}
