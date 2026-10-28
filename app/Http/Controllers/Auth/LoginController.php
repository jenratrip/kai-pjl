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
    private const JPL_NOMOR_COLUMN  = 'kode_jpl';
    private const SHIFT_NAMA_COLUMN = 'nama_shift';

    /*
     * true  => petugas REGULER hanya boleh login jika admin sudah membuat
     *          jadwal dinas untuk hari ini, PJL, dan dinasan itu.
     * false => REGULER bebas memilih PJL & dinasan (dinas dibuat otomatis).
     */
    private const REGULER_HARUS_TERJADWAL = false;

    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function showLoginForm()
    {
        $dinasanList = Shift::orderBy('id')
            ->pluck(self::SHIFT_NAMA_COLUMN, 'id')
            ->toArray();

        return view('auth.login', compact('dinasanList'));
    }

    /*
    |--------------------------------------------------------------------------
    | Cek kredensial & role (dipanggil via fetch dari form login)
    |--------------------------------------------------------------------------
    | Dipakai form untuk menentukan apakah field PJL (nomor PJL, dinasan,
    | jenis petugas) perlu ditampilkan. Role hanya dibuka SETELAH NIP dan
    | password terbukti benar, jadi tidak bisa dipakai menebak NIP orang lain.
    | Method ini TIDAK melakukan login.
    */

    public function cek(Request $request)
    {
        $credentials = $request->validate([
            'nip'      => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('nip', $credentials['nip'])->first();

        if (!$user || !$user->is_active || !Auth::validate($credentials)) {
            return response()->json([
                'message' => 'NIPP / NIPKWT atau password tidak valid.',
            ], 422);
        }

        if ($user->isSuperadmin() || $user->hasRole('admin')) {
            return response()->json(['role' => 'admin']);
        }

        if ($user->hasRole('pjl')) {
            return response()->json(['role' => 'pjl']);
        }

        return response()->json([
            'message' => 'Role pengguna tidak dikenali.',
        ], 422);
    }

    /*
    |--------------------------------------------------------------------------
    | Login (satu pintu untuk User/PJL, Admin, dan Superadmin)
    |--------------------------------------------------------------------------
    | 1. Semua role: NIPP/NIPKWT (kolom users.nip) + password
    | 2. Role pjl   : + nomor PJL, dinasan, jenis petugas (reguler/wakilan)
    |
    | Validasi akhir selalu di sini, jadi menembak langsung ke route login
    | tanpa melewati endpoint cek() tidak menghindari aturan apa pun.
    */

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nip'      => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'nip.required'      => 'NIPP / NIPKWT wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $pesanUmum = 'NIPP / NIPKWT atau password tidak valid.';

        // 1) User & status
        $user = User::where('nip', $credentials['nip'])->first();

        if (!$user || !$user->is_active) {
            return $this->gagal($request, 'nip', $pesanUmum);
        }

        // 2) Password (tanpa login dulu)
        if (!Auth::validate($credentials)) {
            return $this->gagal($request, 'nip', $pesanUmum);
        }

        // 3) Cabang berdasarkan role
        if ($user->isSuperadmin() || $user->hasRole('admin')) {
            return $this->loginAdmin($request, $user);
        }

        if ($user->hasRole('pjl')) {
            return $this->loginPetugas($request, $user);
        }

        return $this->gagal($request, 'nip', 'Role pengguna tidak dikenali.');
    }

    /*
    |--------------------------------------------------------------------------
    | Admin / Superadmin
    |--------------------------------------------------------------------------
    */

    protected function loginAdmin(Request $request, User $user)
    {
        Auth::login($user);
        $request->session()->regenerate();

        $this->activityLogService->log('login', 'auth', 'User berhasil login', $user);

        return $user->isSuperadmin()
            ? redirect()->route('superadmin.dashboard')
            : redirect()->route('admin.dashboard');
    }

    /*
    |--------------------------------------------------------------------------
    | Petugas PJL (Reguler / Wakilan)
    |--------------------------------------------------------------------------
    */

    protected function loginPetugas(Request $request, User $user)
    {
        // Field tambahan baru divalidasi setelah kredensial terbukti benar
        $data = $request->validate([
            'nomor_pjl'     => ['required', 'string'],
            'dinasan'       => ['required', 'integer'],
            'jenis_petugas' => ['required', 'in:reguler,wakilan'],
        ], [
            'nomor_pjl.required'     => 'Nomor PJL wajib diisi untuk petugas PJL.',
            'dinasan.required'       => 'Dinasan wajib dipilih untuk petugas PJL.',
            'jenis_petugas.required' => 'Pilih jenis petugas (Reguler / Wakilan).',
        ]);

        $jpl = Jpl::where(self::JPL_NOMOR_COLUMN, $data['nomor_pjl'])
            ->where('is_active', true)
            ->first();

        if (!$jpl) {
            return $this->gagal($request, 'nomor_pjl', 'Nomor PJL tidak ditemukan.');
        }

        $shift = Shift::find($data['dinasan']);

        if (!$shift) {
            return $this->gagal($request, 'dinasan', 'Dinasan tidak valid.');
        }

        $isWakilan = $data['jenis_petugas'] === 'wakilan';
        $tanggal   = now('Asia/Jakarta')->toDateString();

        $dinas = $this->siapkanDinas($user, $jpl, $shift, $tanggal, $isWakilan);

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
    | Siapkan dinas aktif (tidak berubah dari versi sebelumnya)
    |--------------------------------------------------------------------------
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
                $dinas->is_pengganti = $isWakilan;

                if (!$isWakilan) {
                    $dinas->user_digantikan_id = null;
                }

                if (!$dinas->jam_mulai) {
                    $dinas->jam_mulai = now();
                }

                $dinas->save();
            }

            Dinas::where('user_id', $user->id)
                ->where('status', 'berlangsung')
                ->where('id', '!=', $dinas->id)
                ->update(['status' => 'selesai']);

            return $dinas;
        });
    }

    protected function gagal(Request $request, string $field, string $pesan)
    {
        return back()
            ->withErrors([$field => $pesan])
            ->withInput($request->only('nip', 'jenis_petugas', 'nomor_pjl', 'dinasan'));
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $this->activityLogService->log('logout', 'auth', 'User berhasil logout', $user);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}