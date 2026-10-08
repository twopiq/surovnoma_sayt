<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LoginSuggester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoginSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggestions_use_only_surname_and_name_without_quotes(): void
    {
        $suggestions = LoginSuggester::suggest("Qurbonpo'latov Behzodjon Norqul o'g'li");

        $this->assertSame('b.qurbonpolatov', $suggestions[0]);
        $this->assertContains('b.qurbonpolatov', $suggestions);
        $this->assertContains('qurbonpolatov.b', $suggestions);

        foreach ($suggestions as $login) {
            $this->assertLessThanOrEqual(20, strlen($login));
            $this->assertTrue(LoginSuggester::isValid($login), $login);
            $this->assertStringNotContainsString('norqul', $login);
            $this->assertStringNotContainsString('ogli', $login);
        }

        $this->assertSame(['ozod', 'gofurov'], array_reverse(LoginSuggester::parts('Gʻofurov “Ozod”')));
    }

    public function test_taken_login_gets_number_and_stays_within_limit(): void
    {
        User::factory()->create(['name' => 'Aliyev Sardor', 'login' => 'sardor.aliyev']);

        $suggestions = LoginSuggester::suggest('Aliyev Sardor');

        $this->assertNotContains('sardor.aliyev', $suggestions);
        $this->assertContains('s.aliyev', $suggestions);

        $this->getJson(route('login.suggestions', ['name' => 'Aliyev Sardor']))
            ->assertOk()
            ->assertJsonMissing(['sardor.aliyev']);
    }

    public function test_user_chosen_login_is_saved_and_validated(): void
    {
        $user = User::factory()->create(['name' => 'Karimov Jasur', 'login' => 'jasur.k']);
        $this->assertSame('jasur.k', $user->login);

        $auto = User::factory()->create(['name' => "Qurbonpo'latov Behzodjon Norqul o'g'li", 'login' => null]);
        $this->assertSame('b.qurbonpolatov', $auto->login);

        $this->post(route('register'), [
            'name' => 'Toshmatov Anvar',
            'phone' => '+998 90 111 22 33',
            'email' => 'anvar@example.test',
            'login' => 'Bad Login!',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('login');
    }

    public function test_migration_shortens_existing_long_logins(): void
    {
        $user = User::factory()->create(['name' => "Qurbonpo'latov Behzodjon Norqul o'g'li", 'login' => 'tmp']);
        DB::table('users')->where('id', $user->id)->update(['login' => 'qurbonpo.latov.behzodjon.norqul.o.g.li']);

        (require database_path('migrations/2026_10_08_160000_shorten_user_logins.php'))->up();

        $this->assertSame('b.qurbonpolatov', $user->fresh()->login);
    }
}
