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
     * Menampilkan panel pengaturan peringatan Realisasi KA.
     */
    public function index()
    {
        $setting = RealisasiKaSetting::firstOrCreate([]);

        return view('admin.realisasi-ka-settings.index', compact('setting'));
    }

    /**
     * Menyimpan pengaturan yang ditentukan admin.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
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
            'jarak_minimum_antar_ka_menit' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],
            'batas_penutupan_palang_menit' => [
                'required',
                'integer',
                'min:0',
                'max:1440',
            ],
            'warning_keterlambatan_aktif' => [
                'required',
                Rule::in(['0', '1', 0, 1]),
            ],
            'warning_keterlambatan_tinggi_aktif' => [
                'required',
                Rule::in(['0', '1', 0, 1]),
            ],
            'warning_antar_ka_aktif' => [
                'required',
                Rule::in(['0', '1', 0, 1]),
            ],
            'warning_penutupan_palang_aktif' => [
                'required',
                Rule::in(['0', '1', 0, 1]),
            ],
        ], [
            'peringatan_keterlambatan_tinggi_menit.gte' =>
                'Ambang keterlambatan tinggi harus sama dengan atau lebih besar daripada toleransi keterlambatan.',
            'min' => 'Nilai tidak boleh kurang dari 0.',
            'max' => 'Nilai maksimal adalah 1440 menit.',
        ]);

        DB::transaction(function () use ($validated) {
            $setting = RealisasiKaSetting::firstOrCreate([]);

            $setting->update([
                'toleransi_keterlambatan_menit' =>
                    $validated['toleransi_keterlambatan_menit'],

                'peringatan_keterlambatan_tinggi_menit' =>
                    $validated['peringatan_keterlambatan_tinggi_menit'],

                'jarak_minimum_antar_ka_menit' =>
                    $validated['jarak_minimum_antar_ka_menit'],

                'batas_penutupan_palang_menit' =>
                    $validated['batas_penutupan_palang_menit'],

                'warning_keterlambatan_aktif' =>
                    (bool) $validated['warning_keterlambatan_aktif'],

                'warning_keterlambatan_tinggi_aktif' =>
                    (bool) $validated['warning_keterlambatan_tinggi_aktif'],

                'warning_antar_ka_aktif' =>
                    (bool) $validated['warning_antar_ka_aktif'],

                'warning_penutupan_palang_aktif' =>
                    (bool) $validated['warning_penutupan_palang_aktif'],

                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('admin.realisasi-ka-settings.index')
            ->with('success', 'Pengaturan peringatan Realisasi KA berhasil disimpan.');
    }
}
