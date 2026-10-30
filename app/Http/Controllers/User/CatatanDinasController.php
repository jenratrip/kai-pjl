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
     * Mengambil dinas aktif pengguna untuk kebutuhan header.
     */
    private function dinasAktif(): ?Dinas
    {
        return Dinas::with([
            'user',
            'userDigantikan',
            'jpl.resort.wilayah',
            'shift',
        ])
            ->where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->latest('id')
            ->first();
    }

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

        // Mengambil dinas aktif untuk header.
        $dinasAktif = $this->dinasAktif();

        // Mengambil catatan milik pengguna yang sedang login.
        $catatan = CatatanDinas::with([
            'user',
            'dinas.user',
            'dinas.userDigantikan',
            'dinas.shift',
            'dinas.jpl.resort.wilayah',
        ])
            ->where('user_id', $user->id)
            ->latest('waktu_catatan')
            ->get();

        // Mengambil jadwal dinas beserta data shift dari database.
        $daftarDinas = Dinas::with([
            'user',
            'userDigantikan',
            'jpl.resort.wilayah',
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
            'dinas_aktif_id' => $dinasAktif?->id,
            'dinas_aktif_status' => $dinasAktif?->status,
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
                    'is_pengganti' => $dinas->is_pengganti,
                    'nama_jpl' => $dinas->jpl?->nama_jpl,
                    'nama_petugas' => $dinas->user?->name,
                    'nama_petugas_digantikan' => $dinas->userDigantikan?->name,
                ];
            })->values()->all(),
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
            'redirect_to' => [
                'nullable',
                'string',
                'in:realisasi-ka',
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

        // Kembali ke halaman Realisasi KA jika form dikirim dari sana.
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
