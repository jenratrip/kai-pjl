<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RealisasiKaSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RealisasiKaSettingController extends Controller
{
    /**
     * Menampilkan panel pengaturan Realisasi KA.
     */
    public function index()
    {
        $setting = RealisasiKaSetting::firstOrCreate([], [
            'toleransi_keterlambatan_menit' => 5,
            'peringatan_keterlambatan_tinggi_menit' => 10,

            'jarak_minimum_antar_ka_menit' => 0,
            'warning_antar_ka_aktif' => false,

            'batas_penutupan_palang_menit' => 0,
            'warning_penutupan_palang_aktif' => false,

            'batas_lebih_awal_menit' => 2,
            'warning_lebih_awal_aktif' => true,

            'batas_awas_2_arah_menit' => 5,
            'warning_awas_2_arah_aktif' => false,

            'warning_keterlambatan_aktif' => false,
            'warning_keterlambatan_tinggi_aktif' => false,
        ]);

        return view(
            'admin.realisasi-ka-settings.index',
            compact('setting')
        );
    }

    /**
     * Menyimpan pengaturan Realisasi KA.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([

            // ==========================================
            // 1. KETERLAMBATAN KA
            // ==========================================

            'toleransi_keterlambatan_menit' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],

            'peringatan_keterlambatan_tinggi_menit' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
                'gte:toleransi_keterlambatan_menit',
            ],

            'warning_keterlambatan_aktif' => [
                'required',
                Rule::in(['0', '1', 0, 1]),
            ],

            'warning_keterlambatan_tinggi_aktif' => [
                'required',
                Rule::in(['0', '1', 0, 1]),
            ],

            // ==========================================
            // 2. JARAK ANTAR KA
            // ==========================================

            'jarak_minimum_antar_ka_menit' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],

            'warning_antar_ka_aktif' => [
                'required',
                Rule::in(['0', '1', 0, 1]),
            ],

            // ==========================================
            // 3. PENUTUPAN PALANG
            // ==========================================

            'batas_penutupan_palang_menit' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],

            'warning_penutupan_palang_aktif' => [
                'required',
                Rule::in(['0', '1', 0, 1]),
            ],

            // ==========================================
            // 4. KA LEBIH AWAL DARI JADWAL
            // ==========================================

            'batas_lebih_awal_menit' => [
                'required',
                'integer',
                'min:1',
                'max:1440',
            ],

            'warning_lebih_awal_aktif' => [
                'required',
                Rule::in(['0', '1', 0, 1]),
            ],

            // ==========================================
            // 5. AWAS 2 ARAH
            // ==========================================

            'batas_awas_2_arah_menit' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],

            'warning_awas_2_arah_aktif' => [
                'required',
                Rule::in(['0', '1', 0, 1]),
            ],
        ], [
            'peringatan_keterlambatan_tinggi_menit.gte' =>
                'Ambang keterlambatan tinggi harus sama dengan atau lebih besar daripada toleransi keterlambatan.',

            'batas_lebih_awal_menit.min' =>
                'Batas KA lebih awal minimal 1 menit.',

            'min' =>
                'Nilai tidak boleh kurang dari 0.',

            'max' =>
                'Nilai maksimal adalah 1440 menit.',

            'required' =>
                'Semua pengaturan wajib diisi.',

            'integer' =>
                'Nilai pengaturan harus berupa bilangan bulat.',
        ]);

        DB::transaction(function () use ($validated) {

            $setting = RealisasiKaSetting::firstOrCreate([]);

            $setting->update([

                // ======================================
                // 1. KETERLAMBATAN KA
                // ======================================

                'toleransi_keterlambatan_menit' =>
                    $validated['toleransi_keterlambatan_menit'],

                'peringatan_keterlambatan_tinggi_menit' =>
                    $validated['peringatan_keterlambatan_tinggi_menit'],

                'warning_keterlambatan_aktif' =>
                    (bool) $validated['warning_keterlambatan_aktif'],

                'warning_keterlambatan_tinggi_aktif' =>
                    (bool) $validated['warning_keterlambatan_tinggi_aktif'],

                // ======================================
                // 2. JARAK ANTAR KA
                // ======================================

                'jarak_minimum_antar_ka_menit' =>
                    $validated['jarak_minimum_antar_ka_menit'],

                'warning_antar_ka_aktif' =>
                    (bool) $validated['warning_antar_ka_aktif'],

                // ======================================
                // 3. PENUTUPAN PALANG
                // ======================================

                'batas_penutupan_palang_menit' =>
                    $validated['batas_penutupan_palang_menit'],

                'warning_penutupan_palang_aktif' =>
                    (bool) $validated['warning_penutupan_palang_aktif'],

                // ======================================
                // 4. KA LEBIH AWAL DARI JADWAL
                // ======================================

                'batas_lebih_awal_menit' =>
                    $validated['batas_lebih_awal_menit'],

                'warning_lebih_awal_aktif' =>
                    (bool) $validated['warning_lebih_awal_aktif'],

                // ======================================
                // 5. AWAS 2 ARAH
                // ======================================

                'batas_awas_2_arah_menit' =>
                    $validated['batas_awas_2_arah_menit'],

                'warning_awas_2_arah_aktif' =>
                    (bool) $validated['warning_awas_2_arah_aktif'],

                // ======================================
                // 6. ADMIN TERAKHIR YANG MEMPERBARUI
                // ======================================

                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('admin.realisasi-ka-settings.index')
            ->with(
                'success',
                'Pengaturan Realisasi KA berhasil disimpan.'
            );
    }
}
