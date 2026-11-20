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
     * Mengambil data dinas aktif berdasarkan session.
     *
     * Dinas harus:
     * - Sesuai dengan ID dinas pada session.
     * - Dimiliki oleh user yang sedang login.
     * - Berstatus berlangsung.
     */
    private function dinasAktif(): ?Dinas
    {
        if (! auth()->check()) {
            return null;
        }

        $dinasId = request()->session()->get('dinas_id');

        if (! is_numeric($dinasId) || (int) $dinasId <= 0) {
            return null;
        }

        return Dinas::with([
            'user',
            'userDigantikan',
            'jpl.resort',
            'shift',
        ])
            ->whereKey((int) $dinasId)
            ->where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->first();
    }

    /**
     * Menampilkan halaman Catatan Dinas.
     */
    public function index()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Dinas aktif dari session (juga dipakai untuk header).
        $dinasAktif = $this->dinasAktif();

        // JPL tempat pengguna login saat ini (misal JPL 351).
        $jplId = $dinasAktif?->jpl_id
            ? (int) $dinasAktif->jpl_id
            : null;

        // Semua petugas pada JPL yang sama (shift 1, 2, 3)
        // dapat membaca catatan satu sama lain.
        $catatan = $jplId
            ? CatatanDinas::with([
                'user',
                'dinas.user',
                'dinas.userDigantikan',
                'dinas.shift',
                'dinas.jpl.resort',
            ])
                ->whereHas('dinas', function ($query) use ($jplId) {
                    $query->where('jpl_id', $jplId);
                })
                ->latest('waktu_catatan')
                ->get()
            : collect();

        // Jadwal milik pengguna sendiri (untuk pilihan tanggal/shift di form).
        $daftarDinas = Dinas::with([
            'user',
            'userDigantikan',
            'jpl.resort',
            'shift',
        ])
            ->where('user_id', $user->id)
            ->orderByDesc('tanggal_dinas')
            ->orderByDesc('id')
            ->get();

        Log::info('Pemeriksaan Catatan Dinas', [
            'user_id' => $user->id,
            'nama_user' => $user->name,
            'session_dinas_id' => request()->session()->get('dinas_id'),
            'dinas_aktif_id' => $dinasAktif?->id,
            'dinas_aktif_status' => $dinasAktif?->status,
            'jpl_id' => $jplId,
            'jumlah_catatan' => $catatan->count(),
            'jumlah_jadwal' => $daftarDinas->count(),
        ]);

        return view('user.catatan-dinas.index', [
            'catatan' => $catatan,
            'daftarDinas' => $daftarDinas,
            'dinasAktif' => $dinasAktif,
        ]);
    }

    /**
     * Menyimpan catatan dinas.
     * Dipakai oleh halaman Catatan Dinas (modal) dan
     * form cepat di halaman Realisasi KA.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'shift_id' => ['required', 'integer'],
            'catatan' => ['required', 'string', 'max:5000'],
            'redirect_to' => ['nullable', 'string', 'in:realisasi-ka'],
        ]);

        // Penulis tetap harus punya jadwal dinas pada tanggal dan shift tersebut.
        $dinas = Dinas::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('tanggal_dinas', $validated['tanggal'])
            ->where('shift_id', $validated['shift_id'])
            ->first();

        if (! $dinas) {
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

        if (($validated['redirect_to'] ?? null) === 'realisasi-ka') {
            return redirect()
                ->to(route('user.realisasi-ka') . '#catatanDinasQuick')
                ->with('success', 'Catatan dinas berhasil disimpan.');
        }

        return redirect()
            ->route('user.catatan-dinas')
            ->with('success', 'Catatan dinas berhasil disimpan.');
    }
}