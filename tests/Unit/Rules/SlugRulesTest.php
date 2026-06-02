<?php

namespace Tests\Unit\Rules;

use App\Rules\SlugNotReserved;
use App\Rules\ValidTenantSlug;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SlugRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Buat tabel slug_releases di SQLite test jika belum ada
        if (! Schema::hasTable('slug_releases')) {
            Schema::create('slug_releases', function ($table) {
                $table->string('slug')->primary();
                $table->timestamp('released_at');
            });
        }
    }

    public function test_valid_slug_passes(): void
    {
        $v = Validator::make(['slug' => 'al-hidayah'], ['slug' => [new ValidTenantSlug]]);
        $this->assertFalse($v->fails());
    }

    public function test_slug_too_short_fails(): void
    {
        $v = Validator::make(['slug' => 'ab'], ['slug' => [new ValidTenantSlug]]);
        $this->assertTrue($v->fails());
    }

    public function test_slug_too_long_fails(): void
    {
        $v = Validator::make(['slug' => str_repeat('a', 31)], ['slug' => [new ValidTenantSlug]]);
        $this->assertTrue($v->fails());
    }

    public function test_slug_uppercase_fails(): void
    {
        $v = Validator::make(['slug' => 'Al-Hidayah'], ['slug' => [new ValidTenantSlug]]);
        $this->assertTrue($v->fails());
    }

    public function test_slug_starts_with_hyphen_fails(): void
    {
        $v = Validator::make(['slug' => '-hidayah'], ['slug' => [new ValidTenantSlug]]);
        $this->assertTrue($v->fails());
    }

    public function test_slug_ends_with_hyphen_fails(): void
    {
        $v = Validator::make(['slug' => 'hidayah-'], ['slug' => [new ValidTenantSlug]]);
        $this->assertTrue($v->fails());
    }

    public function test_reserved_slug_fails(): void
    {
        foreach (['www', 'app', 'api', 'admin', 'dash', 'billing'] as $reserved) {
            $v = Validator::make(['slug' => $reserved], ['slug' => [new SlugNotReserved]]);
            $this->assertTrue($v->fails(), "Slug '{$reserved}' seharusnya ditolak");
        }
    }

    public function test_non_reserved_slug_passes(): void
    {
        $v = Validator::make(['slug' => 'al-furqon'], ['slug' => [new SlugNotReserved]]);
        $this->assertFalse($v->fails());
    }
}
