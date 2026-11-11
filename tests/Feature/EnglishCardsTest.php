<?php

namespace Tests\Feature;

use App\Models\EnglishSet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** "Ingliz tili" bo'limi faqat `can_access_english` belgilangan akkauntga ochiq. */
class EnglishCardsTest extends TestCase
{
    use RefreshDatabase;

    private const CARDS = [['term' => 'kun', 'def' => 'day'], ['term' => 'tun', 'def' => 'night']];

    private function owner(): User
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'executor@rtt.local')->firstOrFail();
        $user->forceFill(['can_access_english' => true])->save();

        return $user;
    }

    public function test_section_is_closed_to_everyone_without_the_flag(): void
    {
        $this->owner();

        $this->get(route('english.index'))->assertRedirect(route('login'));
        $this->getJson(route('english.sets.index'))->assertUnauthorized();

        // Admin ham ruxsatsiz kira olmaydi va menyuda ko'rmaydi.
        foreach (['admin', 'manager', 'operator', 'requester'] as $role) {
            $other = User::query()->where('email', "{$role}@rtt.local")->firstOrFail();

            $this->actingAs($other)->get(route('english.index'))->assertForbidden();
            $this->actingAs($other)->getJson(route('english.sets.index'))->assertForbidden();
            $this->actingAs($other)->postJson(route('english.sets.store'), ['title' => 'x', 'cards' => self::CARDS])->assertForbidden();
            $this->actingAs($other)->get(route('profile.edit'))->assertOk()->assertDontSee(route('english.index'));
        }

        $this->assertSame(0, EnglishSet::query()->count());
    }

    public function test_owner_sees_menu_item_and_page(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get(route('profile.edit'))->assertOk()->assertSee(route('english.index'))->assertSee('Ingliz tili');
        // Alohida sayt ko'rinishi: asosiy saytning yon menyusi yo'q, faqat qaytish tugmasi bor.
        $this->actingAs($owner)->get(route('english.index'))->assertOk()
            ->assertSee('english-app')->assertSee('english-assets/english.js')
            ->assertSee("So'rovnomaga qaytish")->assertSee(route('app.home'))
            ->assertDontSee('side-nav__item');
    }

    public function test_pdf_export_assets_are_shipped(): void
    {
        // PDF brauzerda yaratiladi; kutubxona va shriftlar repozitoriyda turishi shart (CDN ishlatilmaydi).
        foreach (['english-pdf.js', 'vendor/jspdf.umd.min.js', 'vendor/IBMPlexSans-Regular.ttf', 'vendor/IBMPlexSans-SemiBold.ttf'] as $file) {
            $this->assertFileExists(public_path("english-assets/{$file}"));
        }
    }

    public function test_owner_creates_updates_and_deletes_sets(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner);

        $this->postJson(route('english.sets.store'), ['title' => '', 'cards' => self::CARDS])->assertUnprocessable();
        $this->postJson(route('english.sets.store'), ['title' => 'Kunlar', 'cards' => [self::CARDS[0], ['term' => 'x', 'def' => '']]])
            ->assertUnprocessable()->assertJsonValidationErrors('cards');

        $id = $this->postJson(route('english.sets.store'), ['title' => 'Kunlar', 'desc' => 'Vaqt', 'cards' => self::CARDS])
            ->assertOk()->assertJsonPath('title', 'Kunlar')->assertJsonCount(2, 'cards')->json('id');

        $cards = $this->getJson(route('english.sets.index'))->assertOk()->assertJsonCount(1)->json('0.cards');
        $cards[0]['lvl'] = 2;

        $this->putJson(route('english.sets.update', $id), ['title' => 'Kunlar', 'cards' => $cards, 'best' => 4.2])
            ->assertOk()->assertJsonPath('cards.0.lvl', 2)->assertJsonPath('best', 4.2);
        $this->assertSame(2, EnglishSet::query()->findOrFail($id)->cards[0]['lvl']);

        $this->deleteJson(route('english.sets.destroy', $id))->assertOk();
        $this->assertSame(0, EnglishSet::query()->count());
    }

    public function test_sets_of_another_user_are_out_of_reach(): void
    {
        $owner = $this->owner();
        $other = User::query()->where('email', 'manager@rtt.local')->firstOrFail();
        $other->forceFill(['can_access_english' => true])->save();

        $set = new EnglishSet(['title' => 'Maxfiy', 'cards' => self::CARDS]);
        $set->user()->associate($other)->save();

        $this->actingAs($owner)->getJson(route('english.sets.index'))->assertOk()->assertJsonCount(0);
        $this->actingAs($owner)->putJson(route('english.sets.update', $set->id), ['title' => 'x', 'cards' => self::CARDS])->assertNotFound();
        $this->actingAs($owner)->deleteJson(route('english.sets.destroy', $set->id))->assertNotFound();
        $this->assertSame('Maxfiy', $set->fresh()->title);
    }

    public function test_access_command_grants_lists_and_revokes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'executor@rtt.local')->firstOrFail();

        $this->artisan('english:access', ['login' => 'yoq-bunday'])->assertFailed();
        $this->artisan('english:access', ['login' => $user->login])->assertSuccessful();
        $this->assertTrue($user->fresh()->canAccessEnglish());

        $this->artisan('english:access')->expectsOutputToContain($user->login)->assertSuccessful();
        $this->artisan('english:access', ['login' => $user->email, '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->canAccessEnglish());
    }
}
