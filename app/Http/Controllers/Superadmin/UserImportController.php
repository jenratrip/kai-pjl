<?php

namespace App\Http\Controllers\Superadmin;

use App\Exports\UserImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\UsersImport;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
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
     * Activity Log Service.
     */
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Download Template
    |--------------------------------------------------------------------------
    */

    /**
     * Download template Excel untuk import user.
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new UserImportTemplateExport,
            'template-import-user.xlsx'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Preview Import
    |--------------------------------------------------------------------------
    */

    /**
     * Upload dan preview data Excel.
     *
     * Data belum masuk database.
     */
    public function preview(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validasi File
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx',
                'max:5120',
            ],
        ], [
            'file.required' => 'File Excel wajib dipilih.',

            'file.file' => 'File yang dikirim tidak valid.',

            'file.mimes' => 'File harus berformat .xlsx.',

            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Baca Excel
        |--------------------------------------------------------------------------
        */

        $import = new UsersImport;

        Excel::import(
            $import,
            $request->file('file')
        );

        $rows = $import->rows;

        /*
        |--------------------------------------------------------------------------
        | Hapus Baris Kosong
        |--------------------------------------------------------------------------
        */

        $rows = array_values(
            array_filter(
                $rows,
                function ($row) {
                    return
                        $row['nip'] !== '' ||
                        $row['name'] !== '' ||
                        $row['email'] !== '' ||
                        $row['phone'] !== '' ||
                        $row['role'] !== '' ||
                        $row['status'] !== '';
                }
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Jika Tidak Ada Data
        |--------------------------------------------------------------------------
        */

        if (count($rows) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'File Excel tidak memiliki data pengguna.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Persiapan Validasi
        |--------------------------------------------------------------------------
        */

        $validRows = [];

        $errors = [];

        /*
        | NIP yang sudah ditemukan dalam file.
        */
        $nipInFile = [];

        /*
        |--------------------------------------------------------------------------
        | Validasi Setiap Baris
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $index => $row) {

            /*
            |--------------------------------------------------------------------------
            | Nomor Baris Excel
            |--------------------------------------------------------------------------
            |
            | Baris pertama adalah heading.
            | Data pertama dimulai dari baris 2.
            |
            */

            $excelRow = $index + 2;

            /*
            |--------------------------------------------------------------------------
            | Normalisasi Data
            |--------------------------------------------------------------------------
            */

            $row['nip'] = trim(
                (string) ($row['nip'] ?? '')
            );

            $row['name'] = trim(
                (string) ($row['name'] ?? '')
            );

            $row['email'] = trim(
                (string) ($row['email'] ?? '')
            );

            $row['phone'] = trim(
                (string) ($row['phone'] ?? '')
            );

            $row['role'] = strtolower(
                trim(
                    (string) ($row['role'] ?? '')
                )
            );

            $row['status'] = strtolower(
                trim(
                    (string) ($row['status'] ?? '')
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Validator
            |--------------------------------------------------------------------------
            */

            $validator = Validator::make(
                $row,
                [
                    'nip' => [
                        'required',
                        'string',
                        'max:50',
                    ],

                    'name' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'email' => [
                        'nullable',
                        'email',
                        'max:255',
                    ],

                    'phone' => [
                        'nullable',
                        'string',
                        'max:20',
                    ],

                    'role' => [
                        'required',
                        'in:superadmin,admin,pjl',
                    ],

                    'status' => [
                        'required',
                        'in:aktif,nonaktif',
                    ],
                ],
                [
                    'nip.required' => 'NIP wajib diisi.',

                    'nip.max' => 'NIP maksimal 50 karakter.',

                    'name.required' => 'Nama wajib diisi.',

                    'name.max' => 'Nama maksimal 255 karakter.',

                    'email.email' => 'Format email tidak valid.',

                    'email.max' => 'Email maksimal 255 karakter.',

                    'phone.max' => 'Nomor HP maksimal 20 karakter.',

                    'role.required' => 'Role wajib diisi.',

                    'role.in' => 'Role harus superadmin, admin, atau pjl.',

                    'status.required' => 'Status wajib diisi.',

                    'status.in' => 'Status harus Aktif atau Nonaktif.',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Cek Duplikat NIP Dalam Excel
            |--------------------------------------------------------------------------
            */

            if ($row['nip'] !== '') {

                if (isset($nipInFile[$row['nip']])) {

                    $validator->after(
                        function ($validator) {
                            $validator->errors()->add(
                                'nip',
                                'NIP duplikat dengan baris lain dalam file Excel.'
                            );
                        }
                    );

                } else {

                    $nipInFile[$row['nip']] = $excelRow;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Cek NIP di Database
            |--------------------------------------------------------------------------
            */

            if (
                $row['nip'] !== '' &&
                User::where('nip', $row['nip'])->exists()
            ) {

                $validator->after(
                    function ($validator) {
                        $validator->errors()->add(
                            'nip',
                            'NIP sudah terdaftar di database.'
                        );
                    }
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Simpan Hasil Validasi
            |--------------------------------------------------------------------------
            */

            if ($validator->fails()) {

                $errors[] = [
                    'row' => $excelRow,

                    'data' => $row,

                    'errors' => $validator->errors()->all(),
                ];

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Data Valid
            |--------------------------------------------------------------------------
            */

            $validRows[] = [
                'row' => $excelRow,

                'data' => $row,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan Preview ke Cache
        |--------------------------------------------------------------------------
        |
        | Data valid disimpan sementara selama 10 menit.
        |
        */

        $previewToken = (string) Str::uuid();

        Cache::put(
            'user_import_preview:'.$previewToken,
            [
                'rows' => $validRows,
                'failed_count' => count($errors),
            ],
            now()->addMinutes(10)
        );

        /*
        |--------------------------------------------------------------------------
        | Response Preview
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' => 'File berhasil diperiksa.',

            'token' => $previewToken,

            'total' => count($rows),

            'valid' => count($validRows),

            'invalid' => count($errors),

            'valid_rows' => $validRows,

            'errors' => $errors,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Confirm Import
    |--------------------------------------------------------------------------
    */

    /**
     * Memproses data yang sebelumnya sudah di-preview.
     *
     * Password awal user = NIP.
     */
    public function confirm(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validasi Token
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'token' => [
                'required',
                'string',
            ],
        ], [
            'token.required' => 'Token preview import tidak ditemukan.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Ambil Data Preview
        |--------------------------------------------------------------------------
        */

        $cacheKey =
            'user_import_preview:'.
            $validated['token'];

        $preview = Cache::get($cacheKey);

        /*
        |--------------------------------------------------------------------------
        | Cek Preview
        |--------------------------------------------------------------------------
        */

        if ($preview === null) {

            return response()->json([
                'success' => false,

                'message' => 'Data preview sudah kedaluwarsa atau tidak ditemukan. '.
                    'Silakan upload file Excel kembali.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Pastikan Data Berbentuk Array
        |--------------------------------------------------------------------------
        */

        if (! is_array($preview)) {

            Cache::forget($cacheKey);

            return response()->json([
                'success' => false,

                'message' => 'Data preview tidak valid. '.
                    'Silakan upload file Excel kembali.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Pastikan Ada Data Valid
        |--------------------------------------------------------------------------
        */

        $validRows = array_key_exists('rows', $preview)
            ? $preview['rows']
            : $preview;
        $previewFailedCount = array_key_exists('rows', $preview)
            ? (int) ($preview['failed_count'] ?? 0)
            : 0;

        if (! is_array($validRows) || count($validRows) === 0) {

            Cache::forget($cacheKey);

            return response()->json([
                'success' => false,

                'message' => 'Tidak ada data valid yang dapat diimport.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Proses Import
        |--------------------------------------------------------------------------
        |
        | Semua proses database berada dalam transaction.
        |
        */

        try {

            $result = DB::transaction(
                function () use ($validRows, $previewFailedCount) {

                    $imported = 0;

                    $failed = [];

                    /*
                    |--------------------------------------------------------------------------
                    | Proses Setiap User
                    |--------------------------------------------------------------------------
                    */

                    foreach ($validRows as $item) {

                        $rowNumber =
                            $item['row'] ?? null;

                        $data =
                            $item['data'] ?? [];

                        /*
                        |--------------------------------------------------------------------------
                        | Normalisasi Data
                        |--------------------------------------------------------------------------
                        */

                        $nip = trim(
                            (string) ($data['nip'] ?? '')
                        );

                        $name = trim(
                            (string) ($data['name'] ?? '')
                        );

                        $email = trim(
                            (string) ($data['email'] ?? '')
                        );

                        $phone = trim(
                            (string) ($data['phone'] ?? '')
                        );

                        $roleName = strtolower(
                            trim(
                                (string) ($data['role'] ?? '')
                            )
                        );

                        $status = strtolower(
                            trim(
                                (string) ($data['status'] ?? '')
                            )
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Validasi Ulang
                        |--------------------------------------------------------------------------
                        |
                        | Data dapat berubah antara Preview
                        | dan Confirm.
                        |
                        */

                        $validator = Validator::make(
                            [
                                'nip' => $nip,
                                'name' => $name,
                                'email' => $email,
                                'phone' => $phone,
                                'role' => $roleName,
                                'status' => $status,
                            ],
                            [
                                'nip' => [
                                    'required',
                                    'string',
                                    'max:50',
                                ],

                                'name' => [
                                    'required',
                                    'string',
                                    'max:255',
                                ],

                                'email' => [
                                    'nullable',
                                    'email',
                                    'max:255',
                                ],

                                'phone' => [
                                    'nullable',
                                    'string',
                                    'max:20',
                                ],

                                'role' => [
                                    'required',
                                    'in:superadmin,admin,pjl',
                                ],

                                'status' => [
                                    'required',
                                    'in:aktif,nonaktif',
                                ],
                            ]
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Jika Validasi Gagal
                        |--------------------------------------------------------------------------
                        */

                        if ($validator->fails()) {

                            $failed[] = [
                                'row' => $rowNumber,

                                'nip' => $nip,

                                'name' => $name,

                                'message' => implode(
                                    ' ',
                                    $validator
                                        ->errors()
                                        ->all()
                                ),
                            ];

                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Cek NIP Terbaru
                        |--------------------------------------------------------------------------
                        */

                        if (
                            User::where(
                                'nip',
                                $nip
                            )->exists()
                        ) {

                            $failed[] = [
                                'row' => $rowNumber,

                                'nip' => $nip,

                                'name' => $name,

                                'message' => 'NIP sudah terdaftar di database.',
                            ];

                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Cari Role
                        |--------------------------------------------------------------------------
                        */

                        $role = Role::where(
                            'name',
                            $roleName
                        )->first();

                        /*
                        |--------------------------------------------------------------------------
                        | Role Tidak Ditemukan
                        |--------------------------------------------------------------------------
                        */

                        if (! $role) {

                            $failed[] = [
                                'row' => $rowNumber,

                                'nip' => $nip,

                                'name' => $name,

                                'message' => 'Role "'.
                                    $roleName.
                                    '" tidak ditemukan di database.',
                            ];

                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Status User
                        |--------------------------------------------------------------------------
                        */

                        $isActive =
                            $status === 'aktif';

                        /*
                        |--------------------------------------------------------------------------
                        | Password Awal
                        |--------------------------------------------------------------------------
                        |
                        | Password awal = NIP.
                        |
                        | Contoh:
                        |
                        | NIP      : 0000000003
                        | Password : 0000000003
                        |
                        | Password tetap di-hash sebelum
                        | disimpan ke database.
                        |
                        */

                        $initialPassword = $nip;

                        /*
                        |--------------------------------------------------------------------------
                        | Buat User
                        |--------------------------------------------------------------------------
                        */

                        $user = User::create([
                            'nip' => $nip,

                            'name' => $name,

                            'email' => $email !== ''
                                    ? $email
                                    : null,

                            'phone' => $phone !== ''
                                    ? $phone
                                    : null,

                            'role_id' => $role->id,

                            'is_active' => $isActive,

                            'password' => Hash::make(
                                $initialPassword
                            ),
                        ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Counter
                        |--------------------------------------------------------------------------
                        */

                        $imported++;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Return Hasil
                    |--------------------------------------------------------------------------
                    */

                    $this->activityLogService->log(
                        'import',
                        'users',
                        'Superadmin mengimpor akun pengguna melalui Excel.',
                        null,
                        null,
                        [
                            'imported_count' => $imported,
                            'failed_count' => $previewFailedCount + count($failed),
                            'import_source' => 'excel',
                        ]
                    );

                    return [
                        'imported' => $imported,

                        'failed' => $failed,
                    ];
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Hapus Preview dari Cache
            |--------------------------------------------------------------------------
            |
            | Token tidak bisa digunakan kembali
            | setelah confirm.
            |
            */

            Cache::forget($cacheKey);

            /*
            |--------------------------------------------------------------------------
            | Response Berhasil
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,

                'message' => 'Import pengguna berhasil diproses.',

                'imported' => $result['imported'],

                'failed' => count($result['failed']),

                'errors' => $result['failed'],
            ]);

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Catat Error ke Laravel Log
            |--------------------------------------------------------------------------
            */

            report($e);

            /*
            |--------------------------------------------------------------------------
            | Response Error
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => false,

                'message' => 'Terjadi kesalahan saat melakukan import. '.
                    'Tidak ada data yang disimpan.',
            ], 500);
        }
    }
}
