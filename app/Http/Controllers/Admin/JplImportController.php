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
     * Halaman upload JPL.
     */
    public function create()
    {
        $wilayahs = Wilayah::where('is_active', true)
            ->orderBy('nama_wilayah')
            ->get();

        return view('admin.jpl.import', compact('wilayahs'));
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
         * Validasi setiap baris.
         */
        $previewRows = $rows
            ->map(function ($row) use (
                $existingResorts,
                $normalize
            ) {
                $row['status'] = 'valid';
                $row['errors'] = [];
                $row['notes'] = [];

                /*
                 * Validasi Resort.
                 *
                 * Resort yang belum ada BUKAN error.
                 * Resort tersebut akan dibuat otomatis
                 * ketika import dikonfirmasi.
                 */
                if (
                    empty($row['resort'])
                ) {
                    $row['status'] = 'error';

                    $row['errors'][] =
                        'RESORT kosong.';
                } else {
                    $resortName =
                        $normalize($row['resort']);

                    if (
                        ! $existingResorts->has(
                            $resortName
                        )
                    ) {
                        $row['status'] = 'baru';

                        $row['notes'][] =
                            'Resort belum tersedia dan akan dibuat otomatis.';
                    }
                }

                /*
                 * Nama JPL wajib.
                 */
                if (
                    empty($row['nama_jpl'])
                ) {
                    $row['status'] = 'error';

                    $row['errors'][] =
                        'NAMA JLN / DAERAH kosong.';
                }

                /*
                 * NO JPL wajib.
                 *
                 * "Liar" tetap dianggap valid.
                 */
                if (
                    empty($row['kode_jpl'])
                ) {
                    $row['status'] = 'error';

                    $row['errors'][] =
                        'NO JPL kosong.';
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
        session([
            'jpl_import' => [
                'wilayah_id' =>
                    $wilayah->id,

                'wilayah_name' =>
                    $wilayah->nama_wilayah,

                'rows' =>
                    $previewRows->all(),

                'total' =>
                    $total,

                'error_count' =>
                    $errorCount,

                'new_resort_names' =>
                    $newResortNames->all(),

                'existing_resort_names' =>
                    $existingResortNames->all(),
            ],
        ]);

        return redirect()
            ->route('admin.jpl.import.preview');
    }

    /**
     * Menampilkan halaman preview.
     */
    public function showPreview()
    {
        $import = session('jpl_import');

        if (! $import) {
            return redirect()
                ->route('admin.jpl.import')
                ->with(
                    'error',
                    'Data preview import tidak ditemukan. Silakan upload file kembali.'
                );
        }

        return view(
            'admin.jpl.import-preview',
            [
                'wilayahName' =>
                    $import['wilayah_name'] ?? '-',

                'wilayahId' =>
                    $import['wilayah_id'] ?? null,

                'rows' =>
                    $import['rows'] ?? [],

                'total' =>
                    $import['total'] ?? 0,

                'errorCount' =>
                    $import['error_count'] ?? 0,

                'newResortNames' =>
                    $import['new_resort_names'] ?? [],

                'existingResortNames' =>
                    $import['existing_resort_names'] ?? [],
            ]
        );
    }

    /**
     * Konfirmasi import.
     */
    public function confirm()
    {
        $import = session('jpl_import');

        if (
            ! $import ||
            empty($import['rows']) ||
            empty($import['wilayah_id'])
        ) {
            return redirect()
                ->route('admin.jpl.import')
                ->with(
                    'error',
                    'Data preview import tidak ditemukan.'
                );
        }

        $rows = collect(
            $import['rows']
        );

        /*
         * Jangan import jika masih ada error.
         */
        $errorRows = $rows->filter(
            fn ($row) =>
                ($row['status'] ?? 'error') === 'error'
        );

        if ($errorRows->isNotEmpty()) {
            return redirect()
                ->route(
                    'admin.jpl.import.preview'
                )
                ->with(
                    'error',
                    'Import tidak dapat dilakukan karena masih terdapat data yang tidak valid.'
                );
        }

        $wilayahId =
            $import['wilayah_id'];

        /*
         * Normalisasi nama Resort.
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
         * Transaction:
         *
         * 1. Cari/buat Resort berdasarkan Wilayah.
         * 2. Buat JPL.
         * 3. JPL otomatis terhubung ke Resort.
         * 4. Resort otomatis terhubung ke Wilayah.
         */
        DB::transaction(function () use (
            $rows,
            $wilayahId,
            $normalize
        ) {
            /*
             * Cache Resort supaya kalau ada
             * 20 JPL dari Resort yang sama,
             * query database tidak perlu dilakukan
             * berkali-kali.
             */
            $resortCache = [];

            foreach ($rows as $row) {
                $resortName =
                    $normalize($row['resort']);

                /*
                 * Gunakan nama normalisasi sebagai
                 * key cache.
                 */
                if (
                    isset(
                        $resortCache[$resortName]
                    )
                ) {
                    $resort =
                        $resortCache[$resortName];
                } else {
                    /*
                     * Cari Resort pada Wilayah yang dipilih.
                     *
                     * Jika belum ada:
                     * buat otomatis.
                     */
                    $resort = Resort::firstOrCreate(
                        [
                            'wilayah_id' =>
                                $wilayahId,

                            'nama_resort' =>
                                $resortName,
                        ],
                        [
                            'is_active' =>
                                true,
                        ]
                    );

                    $resortCache[$resortName] =
                        $resort;
                }

                /*
                 * Buat JPL.
                 */
                Jpl::create([
                    'kode_jpl' =>
                        $row['kode_jpl'],

                    'nama_jpl' =>
                        $row['nama_jpl'],

                    'resort_id' =>
                        $resort->id,

                    'antara' =>
                        $row['antara'],

                    'km_hm' =>
                        $row['km_hm'],

                    'kota_kabupaten' =>
                        $row['kota_kabupaten'],

                    'provinsi' =>
                        $row['provinsi'],

                    'jenis_jalur_ka' =>
                        $row['jenis_jalur_ka'],

                    'titik_koordinat' =>
                        $row['titik_koordinat'],

                    'status_penjagaan' =>
                        $row['status_penjagaan'],

                    'is_active' =>
                        true,
                ]);
            }
        });

        /*
         * Hapus data preview.
         */
        session()->forget(
            'jpl_import'
        );

        return redirect()
            ->route('admin.jpl.index')
            ->with(
                'success',
                'Data JPL berhasil diimport. Resort yang belum tersedia telah dibuat dan seluruh JPL telah terhubung ke Wilayah dan Resort.'
            );
    }
}