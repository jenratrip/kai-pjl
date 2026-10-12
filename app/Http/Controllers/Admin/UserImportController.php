<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\UserImportTemplateExport;
use App\Imports\UsersImport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class UserImportController extends Controller
{
    /**
     * Download template Excel untuk import akun PJL.
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new UserImportTemplateExport(),
            'template-import-user-pjl.xlsx'
        );
    }

    /**
     * Membaca Excel dan memvalidasi data sebelum disimpan.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:5120',
            ],
        ]);

        $import = new UsersImport();

        Excel::import($import, $request->file('file'));

        $rows = $import->rows;

        if (empty($rows)) {
            return back()
                ->withInput()
                ->withErrors([
                    'file' => 'File Excel tidak memiliki data untuk diimpor.',
                ]);
        }

        $validRows = [];
        $errors = [];
        $seenNips = [];
        $seenEmails = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $messages = [];

            // Admin hanya boleh mengimpor role PJL.
            if ($row['role'] !== 'pjl') {
                $messages[] = 'Role harus pjl. Admin tidak boleh mengimpor role lain.';
            }

            $status = strtolower($row['status']);

            if (in_array($status, ['aktif', 'active'], true)) {
                $row['is_active'] = true;
            } elseif (in_array($status, ['nonaktif', 'inactive'], true)) {
                $row['is_active'] = false;
            } else {
                $messages[] = 'Status harus Aktif atau Nonaktif.';
            }

            $validator = Validator::make($row, [
                'nip' => ['required', 'string', 'max:50'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
            ]);

            if ($validator->fails()) {
                $messages = array_merge(
                    $messages,
                    $validator->errors()->all()
                );
            }

            if ($row['nip'] !== '') {
                if (isset($seenNips[$row['nip']])) {
                    $messages[] = 'NIP duplikat di dalam file.';
                }

                $seenNips[$row['nip']] = true;

                if (User::where('nip', $row['nip'])->exists()) {
                    $messages[] = 'NIP sudah terdaftar di database.';
                }
            }

            if ($row['email'] !== '') {
                $email = strtolower($row['email']);

                if (isset($seenEmails[$email])) {
                    $messages[] = 'Email duplikat di dalam file.';
                }

                $seenEmails[$email] = true;

                if (User::where('email', $email)->exists()) {
                    $messages[] = 'Email sudah terdaftar di database.';
                }
            }

            if (!empty($messages)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'nip' => $row['nip'],
                    'name' => $row['name'],
                    'messages' => array_values(array_unique($messages)),
                ];

                continue;
            }

            // Role tetap dipaksa menjadi pjl oleh backend.
            $row['role'] = 'pjl';

            $validRows[] = $row;
        }

        $token = (string) Str::uuid();

        Cache::put(
            'admin_user_import_' . $token,
            $validRows,
            now()->addMinutes(10)
        );

        return view('admin.users.import-preview', [
            'validRows' => $validRows,
            'errors' => $errors,
            'token' => $token,
        ]);
    }

    /**
     * Menyimpan data yang lolos validasi.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'token' => ['required', 'uuid'],
        ]);

        $cacheKey = 'admin_user_import_' . $request->input('token');
        $rows = Cache::get($cacheKey);

        if (!is_array($rows)) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Data preview sudah kedaluwarsa. Silakan unggah ulang file.');
        }

        if (empty($rows)) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Tidak ada data valid untuk diimpor.');
        }

        $role = Role::where('name', 'pjl')->first();

        if (!$role) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Role PJL tidak ditemukan di database.');
        }

        $created = 0;

        try {
            DB::transaction(function () use ($rows, $role, &$created) {
                foreach ($rows as $row) {
                    // Periksa ulang untuk menghindari data duplikat
                    // yang masuk setelah proses preview.
                    if (
                        User::where('nip', $row['nip'])->exists()
                        || User::where('email', $row['email'])->exists()
                    ) {
                        throw new \RuntimeException(
                            'NIP atau email ' . $row['nip']
                            . ' sudah terdaftar. Silakan unggah ulang file.'
                        );
                    }

                    User::create([
                        'nip' => $row['nip'],
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'phone' => $row['phone'] ?: null,
                        'role_id' => $role->id,
                        'is_active' => (bool) $row['is_active'],
                        // Password awal menggunakan NIP.
                        'password' => Hash::make($row['nip']),
                    ]);

                    $created++;
                }
            });
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', $exception->getMessage());
        }

        Cache::forget($cacheKey);

        return redirect()
            ->route('admin.users.index')
            ->with('success', $created . ' akun PJL berhasil diimpor.');
    }
}