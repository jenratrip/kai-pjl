<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\Jpl;
use App\Models\Shift;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\DinasJadwalSnapshotService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const JPL_NOMOR_COLUMN = 'kode_jpl';
    private const SHIFT_NAMA_COLUMN = 'nama_shift';

    public function __construct(
        protected ActivityLogService $activityLogService,
        protected DinasJadwalSnapshotService $dinasJadwalSnapshotService
    ) {}

    public function showLoginForm()
    {
        $dinasanList = Shift::orderBy('id')
            ->pluck(self::SHIFT_NAMA_COLUMN, 'id')
            ->toArray();

        return view('auth.login', compact('dinasanList'));
    }

    /*
    |--------------------------------------------------------------------------
    | Cek kredensial dan role
    |--------------------------------------------------------------------------
    | Dipanggil melalui fetch dari form login untuk menentukan apakah
    | field tambahan PJL perlu ditampilkan.
    | Method ini hanya memeriksa kredensial dan tidak melakukan login.
    */

    public function cek(Request $request)
    {
        $credentials = $request->validate([
            'nip' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('nip', $credentials['nip'])->first();

        if (! $user || ! $user->is_active || ! Auth::validate($credentials)) {
            return response()->json([
                'message' => 'NIPP / NIPKWT atau password tidak valid.',
            ], 422);
        }

        if ($user->isSuperadmin() || $user->hasRole('admin')) {
            return response()->json([
                'role' => 'admin',
            ]);
        }

        if ($user->hasRole('pjl')) {
            return response()->json([
                'role' => 'pjl',
            ]);
        }

        return response()->json([
            'message' => 'Role pengguna tidak dikenali.',
        ], 422);
    }

    /*
    |--------------------------------------------------------------------------
    | Login utama
    |--------------------------------------------------------------------------
    | Admin dan Superadmin cukup menggunakan NIPP/NIPKWT dan password.
    | Petugas PJL juga wajib mengisi nomor PJL, dinasan, dan jenis petugas.
    | Validasi akhir tetap dilakukan di method ini.
    */

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nip' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'nip.required' => 'NIPP / NIPKWT wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $pesanUmum = 'NIPP / NIPKWT atau password tidak valid.';

        $user = User::where('nip', $credentials['nip'])->first();

        if (! $user || ! $user->is_active) {
            return $this->gagal($request, 'nip', $pesanUmum);
        }

        if (! Auth::validate($credentials)) {
            return $this->gagal($request, 'nip', $pesanUmum);
        }

        if ($user->isSuperadmin() || $user->hasRole('admin')) {
            return $this->loginAdmin($request, $user);
        }

        if ($user->hasRole('pjl')) {
            return $this->loginPetugas($request, $user);
        }

        return $this->gagal(
            $request,
            'nip',
            'Role pengguna tidak dikenali.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Login Admin dan Superadmin
    |--------------------------------------------------------------------------
    */

    protected function loginAdmin(Request $request, User $user)
    {
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
    */

    protected function loginPetugas(Request $request, User $user)
    {
        $data = $request->validate([
            'nomor_pjl' => ['required', 'string'],
            'dinasan' => ['required', 'integer'],
            'jenis_petugas' => ['required', 'in:reguler,wakilan'],
        ], [
            'nomor_pjl.required' => 'Nomor PJL wajib diisi untuk petugas PJL.',
            'dinasan.required' => 'Dinasan wajib dipilih untuk petugas PJL.',
            'jenis_petugas.required' => 'Pilih jenis petugas (Reguler / Wakilan).',
        ]);

        $jpl = Jpl::where(self::JPL_NOMOR_COLUMN, $data['nomor_pjl'])
            ->where('is_active', true)
            ->first();

        if (! $jpl) {
            return $this->gagal(
                $request,
                'nomor_pjl',
                'Nomor PJL tidak ditemukan.'
            );
        }

        $shift = Shift::find($data['dinasan']);

        if (! $shift) {
            return $this->gagal(
                $request,
                'dinasan',
                'Dinasan tidak valid.'
            );
        }

        $isWakilan = $data['jenis_petugas'] === 'wakilan';

        $sekarang = now('Asia/Jakarta');
        $tanggalHariIni = $sekarang->toDateString();

        $jamMulaiHariIni = Carbon::parse(
            $tanggalHariIni . ' ' . $shift->jam_mulai,
            'Asia/Jakarta'
        );

        $jamSelesaiHariIni = Carbon::parse(
            $tanggalHariIni . ' ' . $shift->jam_selesai,
            'Asia/Jakarta'
        );

        if ($jamSelesaiHariIni->lessThanOrEqualTo($jamMulaiHariIni)) {
            // Shift melewati tengah malam.
            $tanggal = $sekarang->lt($jamSelesaiHariIni)
                ? $sekarang->copy()->subDay()->toDateString()
                : $tanggalHariIni;
        } else {
            $tanggal = $tanggalHariIni;
        }

        $dinas = $this->siapkanDinas(
            $user,
            $jpl,
            $shift,
            $tanggal,
            $isWakilan
        );

        Auth::login($user);

        $request->session()->regenerate();

        $request->session()->put('petugas_login', [
            'jenis' => $data['jenis_petugas'],
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'dinas_id' => $dinas->id,
        ]);

        $request->session()->put('dinas_id', $dinas->id);

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
    | Menyiapkan Dinas Aktif
    |--------------------------------------------------------------------------
    */

    protected function siapkanDinas(
        User $user,
        Jpl $jpl,
        Shift $shift,
        string $tanggal,
        bool $isWakilan
    ): Dinas {
        return DB::transaction(function () use (
            $user,
            $jpl,
            $shift,
            $tanggal,
            $isWakilan
        ) {
            $lockedJpl = Jpl::query()
                ->whereKey($jpl->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedJpl || ! $lockedJpl->is_active) {
                throw ValidationException::withMessages([
                    'nomor_pjl' => 'Nomor PJL tidak ditemukan atau sudah tidak aktif.',
                ]);
            }

            $lockedUser = User::query()
                ->whereKey($user->id)
                ->where('is_active', true)
                ->whereHas('role', fn ($query) => $query->where('name', 'pjl'))
                ->lockForUpdate()
                ->first();

            if (! $lockedUser) {
                throw ValidationException::withMessages([
                    'nip' => 'Akun PJL sudah tidak aktif atau tidak memiliki akses PJL.',
                ]);
            }

            $dinasTerkunciDenganJenisBerbeda = Dinas::query()
                ->where('user_id', $lockedUser->id)
                ->where('jpl_id', $lockedJpl->id)
                ->where('shift_id', $shift->id)
                ->whereDate('tanggal_dinas', $tanggal)
                ->where('is_pengganti', '!=', $isWakilan)
                ->whereNotNull('jadwal_ka_snapshot')
                ->lockForUpdate()
                ->exists();

            if ($dinasTerkunciDenganJenisBerbeda) {
                throw ValidationException::withMessages([
                    'jenis_petugas' => 'Jenis petugas tidak sesuai dengan Dinas yang sudah dimulai dan terkunci oleh snapshot Jadwal KA.',
                ]);
            }

            if ($isWakilan) {
                $dinasWakilanTerkunciPenggunaLain = Dinas::query()
                    ->where('jpl_id', $lockedJpl->id)
                    ->where('shift_id', $shift->id)
                    ->whereDate('tanggal_dinas', $tanggal)
                    ->where('is_pengganti', true)
                    ->where('user_id', '!=', $lockedUser->id)
                    ->whereNotNull('jadwal_ka_snapshot')
                    ->lockForUpdate()
                    ->exists();

                if ($dinasWakilanTerkunciPenggunaLain) {
                    throw ValidationException::withMessages([
                        'jenis_petugas' => 'Dinas wakilan untuk JPL, shift, dan tanggal ini sudah terkunci pada akun petugas lain.',
                    ]);
                }
            }

            $dinas = Dinas::query()
                ->lockForUpdate()
                ->where('user_id', $lockedUser->id)
                ->where('jpl_id', $lockedJpl->id)
                ->where('shift_id', $shift->id)
                ->whereDate('tanggal_dinas', $tanggal)
                ->where('is_pengganti', $isWakilan)
                ->latest('id')
                ->first();

            $dinasBaru = $dinas === null;

            if ($dinas && $dinas->status === 'selesai') {
                throw ValidationException::withMessages([
                    'dinasan' => 'Dinas pada PJL dan dinasan ini hari ini sudah selesai.',
                ]);
            }

            // Petugas reguler harus memiliki jadwal dari admin.
            if (! $isWakilan && ! $dinas) {
                throw ValidationException::withMessages([
                    'nomor_pjl' => 'Anda tidak dijadwalkan pada PJL dan dinasan ini hari ini.',
                ]);
            }

            // Validasi identitas dan konteks Dinas yang sudah terkunci.
            if (
                $dinas
                && $dinas->jadwal_ka_snapshot !== null
                && (
                    (int) $dinas->user_id !== (int) $lockedUser->id
                    || (int) $dinas->jpl_id !== (int) $lockedJpl->id
                    || (int) $dinas->shift_id !== (int) $shift->id
                    || Carbon::parse($dinas->tanggal_dinas)->toDateString() !== $tanggal
                    || (bool) $dinas->is_pengganti !== $isWakilan
                )
            ) {
                throw ValidationException::withMessages([
                    'dinasan' => 'Identitas atau konteks Dinas tidak sesuai dengan Dinas yang sudah terkunci oleh snapshot Jadwal KA.',
                ]);
            }

            // Cari petugas reguler yang akan digantikan.
            $digantikanId = $dinas?->user_digantikan_id;

            if ($isWakilan && (! $dinas || $dinas->jadwal_ka_snapshot === null)) {
                $digantikanId = Dinas::query()
                    ->where('jpl_id', $lockedJpl->id)
                    ->where('shift_id', $shift->id)
                    ->whereDate('tanggal_dinas', $tanggal)
                    ->where('is_pengganti', false)
                    ->where('user_id', '!=', $lockedUser->id)
                    ->value('user_id');
            }

            // Buat Dinas baru untuk petugas wakilan jika belum tersedia.
            if (! $dinas) {
                $jamMulai = now('Asia/Jakarta');

                $jamSelesai = Carbon::parse(
                    $tanggal . ' ' . $shift->jam_selesai,
                    'Asia/Jakarta'
                );

                if ($jamSelesai->lte($jamMulai)) {
                    $jamSelesai->addDay();
                }

                $dinas = new Dinas();

                $dinas->user_id = $lockedUser->id;
                $dinas->jpl_id = $lockedJpl->id;
                $dinas->shift_id = $shift->id;
                $dinas->tanggal_dinas = $tanggal;
                $dinas->jam_mulai = $jamMulai;
                $dinas->jam_selesai = $jamSelesai;
                $dinas->status = 'berlangsung';
            } elseif (
                $dinas->jadwal_ka_snapshot === null
                && ! $dinas->jam_mulai
            ) {
                $dinas->jam_mulai = now('Asia/Jakarta');
                $dinas->status = 'berlangsung';
            }

            if ($isWakilan && $dinas->jadwal_ka_snapshot === null) {
                $dinas->is_pengganti = true;
                $dinas->user_digantikan_id = $digantikanId;
            }

            $dinas->save();

            // Tutup Dinas aktif lain milik user setelah Dinas baru tersimpan.
            Dinas::query()
                ->where('user_id', $lockedUser->id)
                ->where('status', 'berlangsung')
                ->where('id', '!=', $dinas->id)
                ->update([
                    'status' => 'selesai',
                ]);

            logger()->info('Pelacakan petugas pengganti saat login', [
                'user_id' => $lockedUser->id,
                'jpl_id' => $lockedJpl->id,
                'shift_id' => $shift->id,
                'tanggal' => $tanggal,
                'is_wakilan' => $isWakilan,
                'dinas_id' => $dinas->id,
                'user_digantikan_id' => $dinas->user_digantikan_id,
            ]);

            if (
                $dinasBaru
                || (
                    is_array($dinas->jadwal_ka_snapshot)
                    && $dinas->jadwal_ka_snapshot !== []
                )
            ) {
                $this->dinasJadwalSnapshotService->resolveAndStore(
                    (int) $dinas->id
                );
            }

            return $dinas;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Login Gagal
    |--------------------------------------------------------------------------
    */

    protected function gagal(
        Request $request,
        string $field,
        string $pesan
    ) {
        return back()
            ->withErrors([
                $field => $pesan,
            ])
            ->withInput(
                $request->only(
                    'nip',
                    'jenis_petugas',
                    'nomor_pjl',
                    'dinasan'
                )
            );
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
