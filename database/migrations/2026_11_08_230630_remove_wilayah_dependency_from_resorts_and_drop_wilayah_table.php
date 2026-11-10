<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resorts', function (Blueprint $table): void {
            $table->dropForeign(['wilayah_id']);
            $table->dropColumn('wilayah_id');
        });

        Schema::dropIfExists('wilayah');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Historical Resort-to-Wilayah mappings cannot be reconstructed after the up migration.
        Schema::create('wilayah', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_wilayah');
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('resorts', function (Blueprint $table): void {
            $table->foreignId('wilayah_id')
                ->nullable()
                ->after('id')
                ->constrained('wilayah')
                ->restrictOnDelete();
        });
    }
};
