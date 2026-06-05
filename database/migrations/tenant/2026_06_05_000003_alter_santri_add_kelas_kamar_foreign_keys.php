<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            // Drop old string columns and their indexes
            $table->dropIndex(['pesantren_id', 'kelas']);
            $table->dropIndex(['pesantren_id', 'kamar']);
            $table->dropColumn(['kelas', 'kamar']);

            // Add foreign keys (nullable agar data lama tidak error)
            $table->foreignId('kelas_id')->nullable()->after('nis')
                ->constrained('kelas')->nullOnDelete();
            $table->foreignId('kamar_id')->nullable()->after('kelas_id')
                ->constrained('kamar')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('santri', function (Blueprint $table) {
            $table->dropForeign(['kelas_id']);
            $table->dropForeign(['kamar_id']);
            $table->dropColumn(['kelas_id', 'kamar_id']);

            $table->string('kelas', 50)->after('nis');
            $table->string('kamar', 50)->after('kelas');
            $table->index(['pesantren_id', 'kelas']);
            $table->index(['pesantren_id', 'kamar']);
        });
    }
};
