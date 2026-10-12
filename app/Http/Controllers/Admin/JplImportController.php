<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\JplImport;
use App\Models\Jpl;
use App\Models\Resort;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class JplImportController extends Controller
{    
    /**
     * Menentukan prefix berdasarkan route yang diakses.
     */
    private function routePrefix(Request $request): string
    {
        return $request->routeIs('superadmin.*')
            ? 'superadmin'
            : 'admin';
    }

    /**
     * Menentukan session key berdasarkan role.
     */
    private function sessionKey(Request $request): string
    {
        return 'jpl_import_' . $this->routePrefix($request);
    }

    /**
     * Halaman upload JPL.
     */

    public function create(Request $request)
    {
        $wilayahs = Wilayah::where('is_active', true)
            ->orderBy('nama_wilayah')
            ->get();

        $prefix = $this->routePrefix($request);

        return view(
            "{$prefix}.jpl.import",
            compact('wilayahs')
        );
    }

    /**
     * Upload Excel dan membuat preview.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'wilayah_id' => [
                'required',
                'integer',
                'exists:wilayah,id',
            ],

            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
        ], [
            'wilayah_id.required' =>
                'Wilayah harus dipilih.',

            'wilayah_id.exists' =>
                'Wilayah yang dipilih tidak ditemukan.',

            'file.required' =>
                'File Excel harus dipilih.',

            'file.mimes' =>
                'File harus berupa Excel (.xlsx atau .xls).',

            'file.max' =>
                'Ukuran file maksimal 10 MB.',
        ]);

        $wilayah = Wilayah::findOrFail(
            $request->wilayah_id
        );

        /*
         * Baca Excel.
         */
        $import = new JplImport();

        Excel::import(
            $import,
            $request->file('file')
        );

        $rows = collect($import->rows);

        /*
         * Jika tidak ada data.
         */
        if ($rows->isEmpty()) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Tidak ditemukan data JPL yang dapat diimport dari file Excel.'
                );
        }

        /*
         * Normalisasi nama Resort.
         *
         * Contoh:
         * "6.1 Jenar"
         * " 6.1 Jenar "
         * "6.1   Jenar"
         *
         * akan dianggap sama.
         */
        $normalize = function ($value): string {
            return trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    (string) $value
                )
            );
        };

        /*
         * Ambil Resort yang sudah ada
         * pada Wilayah yang dipilih.
         */
        $existingResorts = Resort::where(
            'wilayah_id',
            $wilayah->id
        )
            ->get()
            ->mapWithKeys(function ($resort) use ($normalize) {
                return [
                    $normalize($resort->nama_resort)
                    => $resort->id,
                ];
            });
        
        
        /*
        * Ambil kode JPL yang sudah tersimpan di database.
        */
        $existingKodeJpl = Jpl::pluck('kode_jpl')
            ->map(fn ($kode) => strtoupper(trim((string) $kode)))
            ->filter()
            ->flip();

        /*
        * Validasi setiap baris dan deteksi duplikat.
        */
        $seenKodeJpl = [];

        $previewRows = $rows
            ->map(function ($row) use (
                $existingResorts,
                $existingKodeJpl,
                &$seenKodeJpl,
                $normalize
            ) {
                $row['status'] = 'valid';
                $row['errors'] = [];
                $row['notes'] = [];

                $kodeJpl = strtoupper(
                    trim((string) ($row['kode_jpl'] ?? ''))
                );

                $resortName = $normalize(
                    $row['resort'] ?? ''
                );

                // Validasi Resort.
                if ($resortName === '') {
                    $row['status'] = 'error';
                    $row['errors'][] = 'RESORT kosong.';
                } elseif (! $existingResorts->has($resortName)) {
                    $row['status'] = 'baru';
                    $row['notes'][] =
                        'Resort belum tersedia dan akan dibuat otomatis.';
                }

                // Validasi nama JPL.
                if (trim((string) ($row['nama_jpl'] ?? '')) === '') {
                    $row['status'] = 'error';
                    $row['errors'][] = 'NAMA JLN / DAERAH kosong.';
                }

                // Validasi dan deteksi duplikat kode JPL.
                if ($kodeJpl === '') {
                    $row['status'] = 'error';
                    $row['errors'][] = 'NO JPL kosong.';
                } elseif ($row['status'] !== 'error') {
                    if ($existingKodeJpl->has($kodeJpl)) {
                        $row['status'] = 'duplikat';
                        $row['notes'][] =
                            'Kode JPL sudah ada di database dan tidak akan diimpor.';
                    } elseif (isset($seenKodeJpl[$kodeJpl])) {
                        $row['status'] = 'duplikat';
                        $row['notes'][] =
                            'Kode JPL muncul lebih dari sekali dalam file Excel.';
                    } else {
                        $seenKodeJpl[$kodeJpl] = true;
                    }
                }

                return $row;
            })
            ->values();

        /*
         * Hitung statistik preview.
         */
        $total = $previewRows->count();

        $errorCount = $previewRows
            ->where('status', 'error')
            ->count();

        $duplicateCount = $previewRows
            ->where('status', 'duplikat')
            ->count();

        $newResortNames = $previewRows
            ->where('status', 'baru')
            ->pluck('resort')
            ->filter()
            ->map($normalize)
            ->unique()
            ->values();

        $existingResortNames = $previewRows
            ->pluck('resort')
            ->filter()
            ->map($normalize)
            ->filter(
                fn ($name) =>
                    $existingResorts->has($name)
            )
            ->unique()
            ->values();

        /*
         * Simpan preview ke session.
         */

        $prefix = $this->routePrefix($request);
        $sessionKey = $this->sessionKey($request);

        session([
            $sessionKey => [
                'wilayah_id' => $wilayah->id,
                'wilayah_name' => $wilayah->nama_wilayah,
                'rows' => $previewRows->all(),
                'total' => $total,
                'error_count' => $errorCount,
                'duplicate_count' => $duplicateCount,
                'new_resort_names' => $newResortNames->all(),
                'existing_resort_names' => $existingResortNames->all(),
            ],
        ]);

        return redirect()->route("{$prefix}.jpl.import.preview");
    }


    /**
     * Menampilkan halaman preview.
     */
    public function showPreview(Request $request)
    {
        $prefix = $this->routePrefix($request);
        $sessionKey = $this->sessionKey($request);

        $import = session($sessionKey);

        if (! $import) {
            return redirect()
                ->route("{$prefix}.jpl.import")
                ->with(
                    'error',
                    'Data preview import tidak ditemukan. Silakan upload file kembali.'
                );
        }

        return view(
            "{$prefix}.jpl.import-preview",
            [
                'wilayahName' => $import['wilayah_name'] ?? '-',
                'wilayahId' => $import['wilayah_id'] ?? null,
                'rows' => $import['rows'] ?? [],
                'total' => $import['total'] ?? 0,
                'errorCount' => $import['error_count'] ?? 0,
                'duplicateCount' => $import['duplicate_count'] ?? 0,
                'newResortNames' => $import['new_resort_names'] ?? [],
                'existingResortNames' => $import['existing_resort_names'] ?? [],
            ]
        );
    }



    /**
     * Konfirmasi import.
     */
    public function confirm(Request $request)
    {
        $prefix = $this->routePrefix($request);
        $sessionKey = $this->sessionKey($request);

        $import = session($sessionKey);

        if (
            ! $import ||
            empty($import['rows']) ||
            empty($import['wilayah_id'])
        ) {
            return redirect()
                ->route("{$prefix}.jpl.import")
                ->with(
                    'error',
                    'Data preview import tidak ditemukan.'
                );
        }

        $rows = collect($import['rows']);

        $errorRows = $rows->filter(
            fn ($row) => ($row['status'] ?? 'error') === 'error'
        );

        if ($errorRows->isNotEmpty()) {
            return redirect()
                ->route("{$prefix}.jpl.import.preview")
                ->with(
                    'error',
                    'Import tidak dapat dilakukan karena masih terdapat data yang tidak valid.'
                );
        }

        $wilayahId = $import['wilayah_id'];

        $normalize = function ($value): string {
            return trim(
                preg_replace('/\s+/', ' ', (string) $value)
            );
        };

        DB::transaction(function () use (
            $rows,
            $wilayahId,
            $normalize
        ) {
            $resortCache = [];

            foreach ($rows as $row) {
                // Lewati JPL yang sudah ada atau duplikat dalam file.
                if (($row['status'] ?? '') === 'duplikat') {
                    continue;
                }
                $resortName = $normalize($row['resort']);

                if (isset($resortCache[$resortName])) {
                    $resort = $resortCache[$resortName];
                } else {
                    $resort = Resort::firstOrCreate(
                        [
                            'wilayah_id' => $wilayahId,
                            'nama_resort' => $resortName,
                        ],
                        [
                            'is_active' => true,
                        ]
                    );

                    $resortCache[$resortName] = $resort;
                }

                Jpl::create([
                    'kode_jpl' => $row['kode_jpl'],
                    'nama_jpl' => $row['nama_jpl'],
                    'resort_id' => $resort->id,
                    'antara' => $row['antara'],
                    'km_hm' => $row['km_hm'],
                    'kota_kabupaten' => $row['kota_kabupaten'],
                    'provinsi' => $row['provinsi'],
                    'jenis_jalur_ka' => $row['jenis_jalur_ka'],
                    'titik_koordinat' => $row['titik_koordinat'],
                    'status_penjagaan' => $row['status_penjagaan'],
                    'is_active' => true,
                ]);
            }
        });

        session()->forget($sessionKey);

        return redirect()
            ->route("{$prefix}.jpl.index")
            ->with(
                'success',
                'Data JPL berhasil diimport. Resort yang belum tersedia telah dibuat dan seluruh JPL telah terhubung ke Wilayah dan Resort.'
            );
    }

}