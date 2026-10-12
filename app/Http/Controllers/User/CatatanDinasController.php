<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CatatanDinas;
use App\Models\Dinas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CatatanDinasController extends Controller
{
    /**
     * Menampilkan halaman Catatan Dinas.
     */
    public function index()
    {
        $user = auth()->user();

        // Pastikan pengguna sudah login.
        if (!$user) {
            return redirect()->route('login');
        }

        // Mengambil catatan milik pengguna yang sedang login.
        $catatan = CatatanDinas::with([
            'user',
            'dinas.user',
            'dinas.shift',
        ])
            ->where('user_id', $user->id)
            ->latest('waktu_catatan')
            ->get();

        // Mengambil jadwal dinas beserta data shift dari database.
        // Jangan mengambil daftar shift dari localStorage.
        $daftarDinas = Dinas::with([
            'user',
            'shift',
        ])
            ->where('user_id', $user->id)
            ->orderByDesc('tanggal_dinas')
            ->orderByDesc('id')
            ->get();

        // Log untuk membantu memeriksa data jadwal dan shift.
        Log::info('Pemeriksaan Catatan Dinas', [
            'user_id' => $user->id,
            'nama_user' => $user->name,
            'jumlah_catatan' => $catatan->count(),
            'jumlah_jadwal' => $daftarDinas->count(),
            'jadwal' => $daftarDinas->map(function ($dinas) {
                return [
                    'dinas_id' => $dinas->id,
                    'user_id' => $dinas->user_id,
                    'tanggal_dinas' => $dinas->getRawOriginal(
                        'tanggal_dinas'
                    ),
                    'shift_id' => $dinas->shift_id,
                    'nama_shift' => $dinas->shift?->nama_shift,
                ];
            })->values()->all(),
        ]);

        return view('user.catatan-dinas', compact(
            'catatan',
            'daftarDinas'
        ));
    }

    /**
     * Menyimpan catatan dinas.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Validasi input dari formulir.
        $validated = $request->validate([
            'tanggal' => [
                'required',
                'date_format:Y-m-d',
            ],
            'shift_id' => [
                'required',
                'integer',
            ],
            'catatan' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        // Jadwal harus dimiliki pengguna yang sedang login,
        // serta sesuai dengan tanggal dan shift yang dipilih.
        $dinas = Dinas::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('tanggal_dinas', $validated['tanggal'])
            ->where('shift_id', $validated['shift_id'])
            ->first();

        if (!$dinas) {
            Log::warning('Jadwal Catatan Dinas tidak ditemukan', [
                'user_id' => $user->id,
                'tanggal' => $validated['tanggal'],
                'shift_id' => $validated['shift_id'],
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'tanggal' =>
                        'Jadwal dinas untuk tanggal dan shift tersebut tidak ditemukan. '
                        . 'Pastikan tanggal dan shift sesuai dengan jadwal dinas Anda.',
                ]);
        }

        // Simpan catatan menggunakan transaksi database.
        DB::transaction(function () use ($dinas, $user, $validated) {
            CatatanDinas::create([
                'dinas_id' => $dinas->id,
                'user_id' => $user->id,
                'judul' => 'Catatan Dinas',
                'kategori' => 'Umum',
                'isi_catatan' => $validated['catatan'],
                'waktu_catatan' => now(),
            ]);
        });

        return redirect()
            ->route('user.catatan-dinas')
            ->with('success', 'Catatan dinas berhasil disimpan.');
    }
}