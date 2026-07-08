<?php

namespace Tests\Feature;

use App\Filament\Pages\EditProfile;
use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_tanpa_foto_tidak_punya_avatar_url(): void
    {
        $user = User::factory()->create(['foto_profil' => null]);

        $this->assertNull($user->getFilamentAvatarUrl());
    }

    public function test_user_dengan_foto_punya_avatar_url_dari_disk_public(): void
    {
        $user = User::factory()->create(['foto_profil' => 'user-photos/contoh.jpg']);

        $this->assertSame(
            Storage::disk('public')->url('user-photos/contoh.jpg'),
            $user->getFilamentAvatarUrl(),
        );
    }

    public function test_ustadz_bisa_upload_foto_profil_lewat_halaman_edit_profile(): void
    {
        Storage::fake('public');

        $pesantren = Pesantren::factory()->create();
        $ustadz    = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($ustadz);

        $file = UploadedFile::fake()->image('foto-ustadz.jpg', 400, 400);

        Livewire::test(EditProfile::class)
            ->fillForm(['foto_profil' => [$file]])
            ->call('save')
            ->assertHasNoFormErrors();

        $ustadz->refresh();
        $this->assertNotNull($ustadz->foto_profil);
        Storage::disk('public')->assertExists($ustadz->foto_profil);
    }

    public function test_upload_foto_baru_menghapus_file_foto_lama(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('user-photos/lama.jpg', 'konten-lama');

        $user = User::factory()->create();
        $user->forceFill(['foto_profil' => 'user-photos/lama.jpg'])->saveQuietly();

        $user->update(['foto_profil' => 'user-photos/baru.jpg']);

        Storage::disk('public')->assertMissing('user-photos/lama.jpg');
        $this->assertSame('user-photos/baru.jpg', $user->fresh()->foto_profil);
    }
}
