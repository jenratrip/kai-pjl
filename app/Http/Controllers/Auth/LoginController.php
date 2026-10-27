<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\Jpl;
use App\Models\Shift;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SKEMA YANG DIPAKAI
    |--------------------------------------------------------------------------
    | - Nomor PJL = tabel jpl, kolom kode_jpl (hanya yang is_active = 1)
    | - Dinasan   = tabel shifts, kolom nama_shift
    | - Jenis petugas dipilih saat login (tidak perlu kolom di tabel users)
    */
    private const JPL_NOMOR_COLUMN  = 'kode_jpl';
    private const SHIFT_NAMA_COLUMN = 'nama_shift';

    /*
     * true  => petugas REGULER hanya boleh login jika admin sudah membuat
     *          jadwal dinas (tabel dinas) untuk hari ini, PJL, dan dinasan itu.
     * false => petugas REGULER bebas memilih PJL & dinasan (dinas dibuat otomatis).
     */
    private const REGULER_HARUS_TERJADWAL = false;

    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Login Page
    |--------------------------------------------------------------------------
    */

    public function showLoginForm()
    {
        // [id_shift => nama] untuk pilihan Dinasan
        $dinasanList = Shift::orderBy('id')
            ->pluck(self::SHIFT_NAMA_COLUMN, 'id')
            ->toArray();

        return view('auth.login', compact('dinasanList'));
    }

    /*
    |--------------------------------------------------------------------------
    | Login Process (dispatcher)
    |--------------------------------------------------------------------------
    | login_type kosong / 'admin' => alur lama (Admin & Superadmin)
    | login_type 'petugas'        => alur Petugas PJL (Reguler / Wakilan)
    */

    public function login(Request $request)
    {
        return $request->input('login_type') === 'petugas'
            ? $this->loginPetugas($request)
            : $this->loginAdmin($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Login Admin / Superadmin  (alur lama dipertahankan)
    |--------------------------------------------------------------------------
    */

    protected function loginAdmin(Request $request)
    {
        $credentials = $request->validate([
            'nip'      => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('nip', $credentials['nip'])->first();

        if (!$user || !$user->is_active) {
            return $this->gagal($request, 'nip', 'NIP atau password tidak valid.', 'admin');
        }

        // Cek password tanpa login dulu
        if (!Auth::validate([
            'nip'      => $credentials['nip'],
            'password' => $credentials['password'],
        ])) {
            return $this->gagal($request, 'nip', 'NIP atau password tidak valid.', 'admin');
        }

        // Akun PJL harus lewat tab "Petugas PJL"
        if ($user->hasRole('pjl')) {
            return $this->gagal(
                $request,
                'nip',
                'Akun petugas PJL silakan login melalui tab "Petugas PJL".',
                'admin'
            );
        }

        if (!$user->isSuperadmin() && !$user->hasRole('admin')) {
            return $this->gagal($request, 'nip', 'Role pengguna tidak dikenali.', 'admin');
        }

        Auth::login($user);
        $request->session()->regenerate();

        $this->activityLogService->log(
            'login',
            'auth',
            'User berhasil login',
            $user
        );

        return $user->isSuperadmin()
            ? redirect()->route('superadmin.dashboard')
            : redirect()->route('admin.dashboard');
    }

    /*
    |--------------------------------------------------------------------------
    | Login Petugas PJL (Reguler / Wakilan)
    |--------------------------------------------------------------------------
    | NIPP / NIPKWT disimpan di kolom users.nip. Jenis petugas (reguler /
    | wakilan) dipilih saat login, tidak terikat pada akun.
    */

    protected function loginPetugas(Request $request)
    {
        $data = $request->validate([
            'jenis_petugas' => ['required', 'in:reguler,wakilan'],
            'nip'           => ['required', 'string'],
            'password'      => ['required', 'string'],
            'nomor_pjl'     => ['required', 'string'],
            'dinasan'       => ['required', 'integer'],
        ], [
            'jenis_petugas.required' => 'Pilih jenis petugas (Reguler / Wakilan).',
            'nip.required'           => $request->input('jenis_petugas') === 'wakilan'
                ? 'NIPKWT wajib diisi.'
                : 'NIPP wajib diisi.',
            'nomor_pjl.required'     => 'Nomor PJL wajib diisi.',
            'dinasan.required'       => 'Dinasan wajib dipilih.',
        ]);

        $label = $data['jenis_petugas'] === 'wakilan' ? 'NIPKWT' : 'NIPP';
        $pesanUmum = "$label atau password tidak valid.";

        // 1) User & status
        $user = User::where('nip', $data['nip'])->first();

        if (!$user || !$user->is_active) {
            return $this->gagal($request, 'nip', $pesanUmum, 'petugas');
        }

        // 2) Password (tanpa login dulu)
        if (!Auth::validate([
            'nip'      => $data['nip'],
            'password' => $data['password'],
        ])) {
            return $this->gagal($request, 'nip', $pesanUmum, 'petugas');
        }

        // 3) Hanya role PJL yang boleh lewat jalur ini
        if (!$user->hasRole('pjl')) {
            return $this->gagal(
                $request,
                'nip',
                'Akun Admin / Superadmin silakan login melalui tab "Admin / Superadmin".',
                'petugas'
            );
        }

        // 4) Jenis petugas (Reguler / Wakilan) bebas dipilih saat login.
        //    Akun PJL yang sama bisa masuk sebagai reguler maupun wakilan,
        //    tergantung dinas hari itu. Pilihan disimpan di session (langkah 7).

        // 5) Nomor PJL harus ada dan aktif
        $jpl = Jpl::where(self::JPL_NOMOR_COLUMN, $data['nomor_pjl'])
            ->where('is_active', true)
            ->first();

        if (!$jpl) {
            return $this->gagal($request, 'nomor_pjl', 'Nomor PJL tidak ditemukan.', 'petugas');
        }

        // 6) Dinasan (shift) harus ada
        $shift = Shift::find($data['dinasan']);

        if (!$shift) {
            return $this->gagal($request, 'dinasan', 'Dinasan tidak valid.', 'petugas');
        }

        // 7) Siapkan dinas aktif hari ini sesuai pilihan login.
        //    Halaman realisasi KA, catatan dinas, dan serah terima semuanya
        //    membaca dinas berstatus "berlangsung" milik user.
        $isWakilan = $data['jenis_petugas'] === 'wakilan';
        $tanggal   = now('Asia/Jakarta')->toDateString();

        $dinas = $this->siapkanDinas($user, $jpl, $shift, $tanggal, $isWakilan);

        // 8) Login & simpan konteks dinas
        Auth::login($user);
        $request->session()->regenerate();

        $request->session()->put('petugas_login', [
            'jenis'    => $data['jenis_petugas'],
            'jpl_id'   => $jpl->id,
            'shift_id' => $shift->id,
            'dinas_id' => $dinas->id,
        ]);

        $this->activityLogService->log(
            'login',
            'auth',
            sprintf(
                'Petugas %s berhasil login (PJL %s, dinasan %s)',
                $data['jenis_petugas'],
                $data['nomor_pjl'],
                $shift->{self::SHIFT_NAMA_COLUMN}
            ),
            $user
        );

        return redirect()->route('user.realisasi-ka');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: siapkan dinas aktif untuk petugas yang login
    |--------------------------------------------------------------------------
    | - Hanya ada SATU dinas "berlangsung" per user (dinas lain ditutup).
    | - Wakilan => is_pengganti = true, user_digantikan_id diisi bila ada
    |   petugas lain yang dijadwalkan di PJL + dinasan + hari yang sama.
    */

    protected function siapkanDinas(
        User $user,
        Jpl $jpl,
        Shift $shift,
        string $tanggal,
        bool $isWakilan
    ): Dinas {
        return DB::transaction(function () use ($user, $jpl, $shift, $tanggal, $isWakilan) {
            $dinas = Dinas::where('user_id', $user->id)
                ->where('jpl_id', $jpl->id)
                ->where('shift_id', $shift->id)
                ->whereDate('tanggal_dinas', $tanggal)
                ->latest('id')
                ->first();

            if ($dinas && $dinas->status === 'selesai') {
                throw ValidationException::withMessages([
                    'dinasan' => 'Dinas pada PJL dan dinasan ini hari ini sudah selesai.',
                ]);
            }

            if (!$dinas) {
                if (!$isWakilan && self::REGULER_HARUS_TERJADWAL) {
                    throw ValidationException::withMessages([
                        'nomor_pjl' => 'Anda tidak dijadwalkan pada PJL dan dinasan ini hari ini.',
                    ]);
                }

                $digantikan = null;

                if ($isWakilan) {
                    $digantikan = Dinas::where('jpl_id', $jpl->id)
                        ->where('shift_id', $shift->id)
                        ->whereDate('tanggal_dinas', $tanggal)
                        ->where('user_id', '!=', $user->id)
                        ->where('is_pengganti', false)
                        ->value('user_id');
                }

                $jamMulai = now();

                $jamSelesai = \Carbon\Carbon::parse(
                    $tanggal . ' ' . $shift->jam_selesai
                );

                if ($jamSelesai->lte($jamMulai)) {
                    $jamSelesai->addDay();
                }

                $dinas = Dinas::create([
                    'user_id'            => $user->id,
                    'jpl_id'             => $jpl->id,
                    'shift_id'           => $shift->id,
                    'tanggal_dinas'      => $tanggal,
                    'jam_mulai'          => $jamMulai,
                    'jam_selesai'        => $jamSelesai,
                    'status'             => 'berlangsung',
                    'is_pengganti'       => $isWakilan,
                    'user_digantikan_id' => $digantikan,
                ]);
            } else {
                // Dinas sudah ada (mis. dijadwalkan admin): sesuaikan jenisnya
                $dinas->is_pengganti = $isWakilan;

                if (!$isWakilan) {
                    $dinas->user_digantikan_id = null;
                }

                if (!$dinas->jam_mulai) {
                    $dinas->jam_mulai = now();
                }

                $dinas->save();
            }

            // Tutup dinas lain milik user yang masih berlangsung,
            // agar halaman user hanya membaca dinas yang baru dipilih.
            Dinas::where('user_id', $user->id)
                ->where('status', 'berlangsung')
                ->where('id', '!=', $dinas->id)
                ->update(['status' => 'selesai']);

            return $dinas;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: gagal login (kembali ke tab yang sama)
    |--------------------------------------------------------------------------
    */

    protected function gagal(Request $request, string $field, string $pesan, string $tab)
    {
        return back()
            ->withErrors([$field => $pesan])
            ->withInput($request->only(
                'nip',
                'jenis_petugas',
                'nomor_pjl',
                'dinasan'
            ) + ['login_type' => $tab]);
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $this->activityLogService->log(
                'logout',
                'auth',
                'User berhasil logout',
                $user
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}