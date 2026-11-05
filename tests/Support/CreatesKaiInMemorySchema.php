<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait CreatesKaiInMemorySchema
{
    protected function createKaiInMemorySchema(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nip')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('role_id');
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('action');
            $table->string('module');
            $table->text('description')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('wilayah', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_wilayah')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('resorts', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_resort')->nullable();
            $table->foreignId('wilayah_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('jpl', function (Blueprint $table): void {
            $table->id();
            $table->string('kode_jpl')->unique();
            $table->string('nama_jpl');
            $table->foreignId('resort_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_shift');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->timestamps();
        });

        Schema::create('kereta_api', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor_ka')->unique();
            $table->string('nama_ka')->nullable();
            $table->string('jenis')->nullable();
            $table->timestamps();
        });

        Schema::create('jadwal_ka_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('status', ['draft', 'scheduled', 'active', 'archived'])->default('draft');
            $table->date('tanggal_mulai_berlaku')->nullable();
            $table->timestamps();
        });

        Schema::create('jadwal_ka_batch_jpl', function (Blueprint $table): void {
            $table->foreignId('jadwal_ka_batch_id');
            $table->foreignId('jpl_id');
            $table->date('tanggal_akhir_berlaku')->nullable();
            $table->primary(['jadwal_ka_batch_id', 'jpl_id']);
        });

        Schema::create('jadwal_ka', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kereta_api_id')->nullable();
            $table->string('dari')->nullable();
            $table->string('ke')->nullable();
            $table->foreignId('jpl_id');
            $table->time('jam_lewat')->nullable();
            $table->json('hari_berlaku')->nullable();
            $table->date('tanggal_berlaku')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('batch_import')->nullable();
            $table->foreignId('jadwal_ka_batch_id')->nullable();
            $table->timestamps();
        });

        Schema::create('dinas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('jpl_id');
            $table->foreignId('shift_id')->nullable();
            $table->date('tanggal_dinas');
            $table->dateTime('jam_mulai')->nullable();
            $table->dateTime('jam_selesai')->nullable();
            $table->foreignId('jadwal_ka_batch_id')->nullable();
            $table->json('jadwal_ka_snapshot')->nullable();
            $table->string('status')->default('berlangsung');
            $table->boolean('is_pengganti')->default(false);
            $table->foreignId('user_digantikan_id')->nullable();
            $table->timestamps();
        });

        Schema::create('realisasi_ka', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dinas_id');
            $table->foreignId('jadwal_ka_id')->nullable();
            $table->foreignId('kereta_api_id')->nullable();
            $table->string('status_lewat')->nullable();
            $table->time('jam_aktual')->nullable();
            $table->string('status_s21')->nullable();
            $table->string('status_genta')->nullable();
            $table->string('status_pintu_perlintasan')->nullable();
            $table->string('status_s35')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->text('catatan')->nullable();
            $table->dateTime('diedit_terakhir_pada')->nullable();
            $table->string('hulu')->nullable();
            $table->string('hilir')->nullable();
            $table->string('relasi')->nullable();
            $table->timestamps();
        });

        Schema::create('realisasi_ka_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('toleransi_keterlambatan_menit')->nullable();
            $table->unsignedInteger('peringatan_keterlambatan_tinggi_menit')->nullable();
            $table->boolean('warning_keterlambatan_aktif')->default(false);
            $table->boolean('warning_keterlambatan_tinggi_aktif')->default(false);
            $table->unsignedInteger('jarak_minimum_antar_ka_menit')->nullable();
            $table->boolean('warning_antar_ka_aktif')->default(false);
            $table->unsignedInteger('batas_penutupan_palang_menit')->nullable();
            $table->boolean('warning_penutupan_palang_aktif')->default(false);
            $table->unsignedInteger('batas_lebih_awal_menit')->nullable();
            $table->boolean('warning_lebih_awal_aktif')->default(false);
            $table->unsignedInteger('batas_awas_2_arah_menit')->nullable();
            $table->boolean('warning_awas_2_arah_aktif')->default(false);
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('serah_terima', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dinas_id');
            $table->foreignId('dinas_penerima_id')->nullable();
            $table->foreignId('penerima_user_id')->nullable();
            $table->dateTime('waktu_serah_terima')->nullable();
            $table->json('ringkasan')->nullable();
            $table->string('tanda_tangan_penyerah')->nullable();
            $table->string('tanda_tangan_penerima')->nullable();
            $table->string('status')->nullable();
            $table->string('status_review')->nullable();
            $table->text('catatan_revisi')->nullable();
            $table->foreignId('ditinjau_oleh')->nullable();
            $table->dateTime('ditinjau_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('catatan_dinas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dinas_id');
            $table->foreignId('user_id')->nullable();
            $table->text('catatan')->nullable();
            $table->dateTime('waktu_catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('catatan_dinas_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catatan_dinas_id');
            $table->string('path')->nullable();
            $table->timestamps();
        });

        Schema::create('inventaris', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jpl_id');
            $table->string('nama_barang');
            $table->string('kategori')->nullable();
            $table->string('kode_barang')->nullable();
            $table->unsignedInteger('jumlah')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('jumlah_aktual_terakhir')->nullable();
            $table->string('kondisi_terakhir')->nullable();
            $table->string('status_tinjauan')->nullable();
            $table->text('catatan_masalah')->nullable();
            $table->dateTime('dilaporkan_pada')->nullable();
            $table->foreignId('dilaporkan_oleh')->nullable();
            $table->timestamps();
        });

        Schema::create('realisasi_inventaris', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dinas_id');
            $table->foreignId('inventaris_id');
            $table->string('status')->nullable();
            $table->string('kondisi')->nullable();
            $table->unsignedInteger('jumlah_aktual')->nullable();
            $table->text('catatan')->nullable();
            $table->dateTime('diperiksa_pada')->nullable();
            $table->foreignId('diperiksa_oleh')->nullable();
            $table->timestamps();
        });
    }
}
