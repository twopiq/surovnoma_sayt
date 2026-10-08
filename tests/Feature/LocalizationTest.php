<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_english_framework_key_has_uzbek_translation(): void
    {
        $this->assertSame('uz', app()->getLocale());
        $this->assertSame('uz', app()->getFallbackLocale());

        foreach (['auth', 'passwords', 'pagination', 'validation'] as $group) {
            $english = require lang_path("en/{$group}.php");
            $uzbek = require lang_path("uz/{$group}.php");

            foreach (array_keys(\Illuminate\Support\Arr::dot($english)) as $key) {
                if (in_array($key, ['custom', 'attributes'], true) || str_starts_with($key, 'custom.') || str_starts_with($key, 'attributes.')) {
                    continue;
                }

                $this->assertArrayHasKey($key, \Illuminate\Support\Arr::dot($uzbek), "lang/uz/{$group}.php da «{$key}» yo'q");
            }
        }
    }

    public function test_validation_and_login_errors_are_in_uzbek(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('login'), ['login' => 'yoq@example.test', 'password' => 'notogri'])
            ->assertSessionHasErrors(['login' => "Login yoki parol noto'g'ri."]);

        $this->post(route('register'), [])
            ->assertSessionHasErrors(['email' => "Email maydonini to'ldiring."]);

        $this->assertStringNotContainsString('The ', Lang::get('validation.required', ['attribute' => 'x']));
    }

    public function test_pages_do_not_show_english_ui_words(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@rtt.local')->firstOrFail();

        foreach ([route('login'), route('register'), route('home')] as $url) {
            $this->get($url)->assertOk()->assertDontSee('>Home<', false)->assertDontSee('Guest forma');
        }

        $this->actingAs($admin)->get(route('admin.guest-blocks.index'))
            ->assertOk()
            ->assertSee('Mehmon himoyasi')
            ->assertDontSee('>Dashboard<', false);
    }
}
