<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Token CSRF kedaluwarsa tidak menampilkan "419 Page Expired", tetapi mengarahkan pengguna.
 */
class SesiBerakhirTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function withCsrf(): static
    {
        // Laravel melewati pemeriksaan CSRF saat unit test; nyalakan lagi untuk tes ini.
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });

        return $this;
    }

    public function test_login_dengan_token_kedaluwarsa_kembali_ke_form_login(): void
    {
        $this->withCsrf()->post('/login', ['email' => 'x@y.z', 'password' => 'a', '_token' => 'basi'])
            ->assertRedirect(route('login'))->assertSessionHas('toast');
        $this->get('/login')->assertOk()->assertSee('Sesi Anda sudah berakhir', false);
    }

    public function test_logout_dengan_token_kedaluwarsa_ke_login(): void
    {
        $this->actingAs(User::where('role', 'admin')->first());
        $this->withCsrf()->post('/logout', ['_token' => 'basi'])->assertRedirect(route('login'));
    }

    public function test_form_saat_masih_login_kembali_ke_halaman_sebelumnya(): void
    {
        $this->actingAs(User::where('role', 'admin')->first());
        $this->withCsrf()->from('/data-pembayaran')->post('/data-pembayaran', ['_token' => 'basi'])->assertRedirect('/data-pembayaran');
    }

    public function test_autosave_json_mendapat_419_json(): void
    {
        $this->actingAs(User::where('role', 'peserta')->first());
        $this->withCsrf()->postJson('/ujian/cbt/1/simpan', ['_token' => 'basi'])->assertStatus(419)->assertJson(['expired_session' => true]);
    }
}
