<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_english(): void
    {
        $this->withoutVite()
            ->get(route('login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('locale', 'en'));
    }

    public function test_locale_cookie_sets_myanmar_locale(): void
    {
        $this->withoutVite()
            ->withUnencryptedCookie('locale', 'mm')
            ->get(route('login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('locale', 'mm'));

        $this->assertSame('မှတ်ပုံတင် ဖန်တီးပြီးပါပြီ။', __('Enrollment created.'));
    }

    public function test_invalid_locale_cookie_falls_back_to_default(): void
    {
        $this->withoutVite()
            ->withUnencryptedCookie('locale', 'fr')
            ->get(route('login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('locale', 'en'));
    }

    public function test_language_settings_page_is_reachable(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('language.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('settings/language'));
    }
}
