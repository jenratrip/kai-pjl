<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\JplImport;
use App\Models\Jpl;
use App\Models\Resort;
use App\Models\Wilayah;
use App\Services\DefaultInventarisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class JplImportController extends Controller
{
    /**
     * Menentukan prefix route berdasarkan role.
     */
    private function routePrefix(Request $request): string
    {
        return $request->routeIs('superadmin.*')
            ? 'superadmin'
            : 'admin';
    }

    /**
     * Session import dipisahkan untuk Admin dan Superadmin.
     */
    private function sessionKey(Request $request): string
    {
        return 'jpl_import_' . $this->routePrefix($request);
    }

    /**
     * Mengambil Wilayah default dari konfigurasi aplikasi.
     */
    private function defaultWilayah(): ?Wilayah
    {
        $wilayahId = config('kai.default_wilayah_id');

        if (! is_numeric($wilayahId)) {
            return null;
        }

        return Wilayah::query()
            ->whereKey((int) $wilayahId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Normalisasi teks untuk pencocokan.
     */
    private function normalize($value): string
    {
        return trim(
            preg_replace('/\s+/', ' ', (string) $value)
        );
    }

    /**
     * Normalisasi teks untuk identitas.
     */
    private function normalizeKey($value): string
    {
        return mb_strtoupper($this->normalize($value));
    }

    /**
     * Membentuk identitas gabungan JPL.
     *
     * NO JPL bukan satu-satunya identitas karena nilainya
     * dapat kosong atau berulang.
     */
    private function identityKey(
        array $row,
        string $resortName
    ): string {
        return json_encode([
            $this->normalizeKey($resortName),
            $this->normalizeKey($row['kode_jpl'] ?? null),
            $this->normalizeKey($row['km_hm'] ?? null),
            $this->normalizeKey($row['antara'] ?? null),
            $this->normalizeKey($row['nama_jpl'] ?? null),
            $this->normalizeKey($row['kota_kabupaten'] ?? null),
        ], JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * Halaman upload Excel JPL.
     *
     * Wilayah tidak dipilih secara manual.
     */
    public function create(Request $request)
    {
        $prefix = $this->routePrefix($request);
        $wilayah = $this->defaultWilayah();

        return view("{$prefix}.jpl.import", [
            'wilayahName' => $wilayah?->nama_wilayah,
        ]);
    }

    /**
     * Membaca Excel dan membuat preview.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
        ], [
            'file.required' => 'File Excel harus dipilih.',
            'file.mimes' => 'File harus berupa Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        /*
         * Wilayah diambil otomatis dari konfigurasi.
         */
        $wilayah = $this->defaultWilayah();

        if (! $wilayah) {
            return redirect()
                ->route($this->routePrefix($request) . '.jpl.import')
                ->with(
                    'error',
                    'Wilayah default belum tersedia atau tidak aktif. '
                    . 'Periksa KAI_DEFAULT_WILAYAH_ID dan data Wilayah.'
                );
        }

        /*
         * Baca Excel menggunakan parser JplImport.
         */
        $import = new JplImport();

        Excel::import(
            $import,
            $request->file('file')
        );

        $rows = collect($import->rows);

        if ($rows->isEmpty()) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Tidak ditemukan data JPL dalam file Excel.'
                );
        }

        /*
         * Ambil Resort yang tersedia di Wilayah tujuan.
         */
        $existingResorts = Resort::query()
            ->where('wilayah_id', $wilayah->id)
            ->get()
            ->mapWithKeys(function ($resort) {
                return [
                    $this->normalizeKey($resort->nama_resort)
                        => $resort->id,
                ];
            });

        /*
         * Ambil identitas JPL yang sudah ada di Wilayah tujuan.
         */
        $existingIdentities = [];

        $existingJpls = Jpl::with('resort')
            ->whereHas('resort', function ($query) use ($wilayah) {
                $query->where('wilayah_id', $wilayah->id);
            })
            ->get();

        foreach ($existingJpls as $jpl) {
            $identity = $this->identityKey(
                $jpl->toArray(),
                $jpl->resort?->nama_resort ?? ''
            );

            $existingIdentities[$identity] = true;
        }

        $seenIdentities = [];

        /*
         * Validasi setiap baris dan deteksi duplikat.
         *
         * valid    = siap disimpan
         * baru     = Resort baru akan dibuat otomatis
         * error    = dilewati
         * duplikat = dilewati
         */
        $previewRows = $rows
            ->map(function ($row) use (
                $existingResorts,
                $existingIdentities,
                &$seenIdentities
            ) {
                $row['status'] = 'valid';
                $row['errors'] = [];
                $row['notes'] = [];

                $resortName = $this->normalize(
                    $row['resort'] ?? ''
                );

                $resortKey = $this->normalizeKey($resortName);

                if ($resortName === '') {
                    $row['status'] = 'error';
                    $row['errors'][] =
                        'RESORT kosong. Baris ini akan dilewati.';
                } elseif (! $existingResorts->has($resortKey)) {
                    $row['status'] = 'baru';
                    $row['notes'][] =
                        'Resort belum tersedia dan akan dibuat otomatis.';
                }

                if (
                    $this->normalize($row['kode_jpl'] ?? '') === ''
                ) {
                    $row['notes'][] =
                        'NO JPL kosong, tetapi diperbolehkan.';
                }

                if (
                    $this->normalize($row['nama_jpl'] ?? '') === ''
                ) {
                    $row['notes'][] =
                        'NAMA JLN / DAERAH kosong, tetapi diperbolehkan.';
                }

                $identity = $this->identityKey(
                    $row,
                    $resortName
                );

                if (isset($existingIdentities[$identity])) {
                    $row['notes'][] =
                        'Data identik sudah ada di database. '
                        . 'Baris akan dilewati.';

                    if ($row['status'] !== 'error') {
                        $row['status'] = 'duplikat';
                    }
                } elseif (isset($seenIdentities[$identity])) {
                    $row['notes'][] =
                        'Data identik muncul lebih dari sekali dalam Excel. '
                        . 'Baris akan dilewati.';

                    if ($row['status'] !== 'error') {
                        $row['status'] = 'duplikat';
                    }
                } else {
                    $seenIdentities[$identity] = true;
                }

                return $row;
            })
            ->values();

        /*
         * Statistik preview.
         */
        $total = $previewRows->count();

        $errorCount = $previewRows
            ->where('status', 'error')
            ->count();

        $duplicateCount = $previewRows
            ->where('status', 'duplikat')
            ->count();

        $validCount = $previewRows
            ->whereIn('status', ['valid', 'baru'])
            ->count();

        /*
         * Daftar Resort baru dari baris yang dapat diimport.
         */
        $newResortNames = $previewRows
            ->whereIn('status', ['valid', 'baru'])
            ->filter(function ($row) use ($existingResorts) {
                $name = $this->normalize($row['resort'] ?? '');

                return $name !== ''
                    && ! $existingResorts->has(
                        $this->normalizeKey($name)
                    );
            })
            ->pluck('resort')
            ->filter()
            ->map(fn ($name) => $this->normalize($name))
            ->unique()
            ->values();

        /*
         * Daftar Resort yang sudah tersedia.
         */
        $existingResortNames = $previewRows
            ->pluck('resort')
            ->filter()
            ->map(fn ($name) => $this->normalize($name))
            ->filter(function ($name) use ($existingResorts) {
                return $existingResorts->has(
                    $this->normalizeKey($name)
                );
            })
            ->unique()
            ->values();

        $prefix = $this->routePrefix($request);
        $sessionKey = $this->sessionKey($request);

        /*
         * Simpan seluruh baris, termasuk error dan duplikat,
         * supaya semua data tetap terlihat pada halaman preview.
         */
        session([
            $sessionKey => [
                'wilayah_id' => $wilayah->id,
                'wilayah_name' => $wilayah->nama_wilayah,
                'rows' => $previewRows->all(),
                'total' => $total,
                'valid_count' => $validCount,
                'error_count' => $errorCount,
                'duplicate_count' => $duplicateCount,
                'new_resort_names' => $newResortNames->all(),
                'existing_resort_names' => $existingResortNames->all(),
            ],
        ]);

        return redirect()->route(
            "{$prefix}.jpl.import.preview"
        );
    }

    /**
     * Menampilkan halaman preview import.
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
                    'Data preview import tidak ditemukan. '
                    . 'Silakan upload file kembali.'
                );
        }

        return view(
            "{$prefix}.jpl.import-preview",
            [
                'wilayahName' => $import['wilayah_name'] ?? '-',
                'wilayahId' => $import['wilayah_id'] ?? null,
                'rows' => $import['rows'] ?? [],
                'total' => $import['total'] ?? 0,
                'validCount' => $import['valid_count'] ?? 0,
                'errorCount' => $import['error_count'] ?? 0,
                'duplicateCount' => $import['duplicate_count'] ?? 0,
                'newResortNames' => $import['new_resort_names'] ?? [],
                'existingResortNames' =>
                    $import['existing_resort_names'] ?? [],
            ]
        );
    }

    /**
     * Konfirmasi partial import JPL.
     *
     * Hanya baris valid dan baris dengan Resort baru yang disimpan.
     * Baris error dan duplikat dilewati.
     */
    public function confirm(
        Request $request,
        DefaultInventarisService $inventarisService
    ) {
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

        /*
         * Pastikan Wilayah default masih tersedia dan sama
         * dengan Wilayah yang digunakan saat preview.
         */
        $wilayah = $this->defaultWilayah();

        if (
            ! $wilayah ||
            (int) ($import['wilayah_id'] ?? 0) !== (int) $wilayah->id
        ) {
            session()->forget($sessionKey);

            return redirect()
                ->route("{$prefix}.jpl.import")
                ->with(
                    'error',
                    'Wilayah default berubah atau tidak tersedia. '
                    . 'Silakan upload ulang file Excel.'
                );
        }

        $rows = collect($import['rows']);

        /*
         * Hanya ambil baris valid dan baris Resort baru.
         */
        $validRows = $rows
            ->filter(function ($row) {
                return in_array(
                    $row['status'] ?? 'error',
                    ['valid', 'baru'],
                    true
                );
            })
            ->values();

        $skippedErrors = $rows
            ->where('status', 'error')
            ->count();

        $skippedDuplicates = $rows
            ->where('status', 'duplikat')
            ->count();

        if ($validRows->isEmpty()) {
            return redirect()
                ->route("{$prefix}.jpl.import.preview")
                ->with(
                    'error',
                    'Tidak ada data valid yang dapat diimport. '
                    . 'Seluruh baris berstatus error atau duplikat.'
                );
        }

        $wilayahId = $wilayah->id;

        /*
         * Simpan data valid dalam satu transaksi.
         */
        $result = DB::transaction(function () use (
            $validRows,
            $wilayahId,
            $inventarisService
        ) {
            $resortCache = [];

            $jumlahJpl = 0;
            $jumlahDuplikatTambahan = 0;

            /*
             * Ambil ulang identitas terkini untuk menghindari
             * duplikat yang mungkin masuk setelah preview.
             */
            $existingIdentities = [];

            $existingJpls = Jpl::with('resort')
                ->whereHas('resort', function ($query) use ($wilayahId) {
                    $query->where('wilayah_id', $wilayahId);
                })
                ->get();

            foreach ($existingJpls as $existingJpl) {
                $identity = $this->identityKey(
                    $existingJpl->toArray(),
                    $existingJpl->resort?->nama_resort ?? ''
                );

                $existingIdentities[$identity] = true;
            }

            foreach ($validRows as $row) {
                $resortName = $this->normalize(
                    $row['resort'] ?? ''
                );

                if ($resortName === '') {
                    continue;
                }

                $identity = $this->identityKey(
                    $row,
                    $resortName
                );

                /*
                 * Periksa ulang duplikat sebelum menyimpan.
                 */
                if (isset($existingIdentities[$identity])) {
                    $jumlahDuplikatTambahan++;
                    continue;
                }

                $resortKey = $this->normalizeKey($resortName);

                /*
                 * Cari Resort di Wilayah default.
                 * Jika belum ada, buat otomatis.
                 */
                if (isset($resortCache[$resortKey])) {
                    $resort = $resortCache[$resortKey];
                } else {
                    $resort = Resort::query()
                        ->where('wilayah_id', $wilayahId)
                        ->get()
                        ->first(function ($item) use ($resortKey) {
                            return $this->normalizeKey(
                                $item->nama_resort
                            ) === $resortKey;
                        });

                    if (! $resort) {
                        $resort = Resort::create([
                            'wilayah_id' => $wilayahId,
                            'nama_resort' => $resortName,
                            'is_active' => true,
                        ]);
                    }

                    $resortCache[$resortKey] = $resort;
                }

                /*
                 * Simpan atribut JPL dari Excel.
                 * Field kosong tetap NULL.
                 */
                $jpl = Jpl::create([
                    'kode_jpl' => $row['kode_jpl'] ?? null,
                    'nama_jpl' => $row['nama_jpl'] ?? null,
                    'resort_id' => $resort->id,
                    'antara' => $row['antara'] ?? null,
                    'km_hm' => $row['km_hm'] ?? null,
                    'status_penjagaan' =>
                        $row['status_penjagaan'] ?? null,
                    'jenis_jalur_ka' =>
                        $row['jenis_jalur_ka'] ?? null,
                    'konst_jalan' => $row['konst_jalan'] ?? null,
                    'titik_koordinat' =>
                        $row['titik_koordinat'] ?? null,
                    'lebar_jln' => $row['lebar_jln'] ?? null,
                    'kota_kabupaten' =>
                        $row['kota_kabupaten'] ?? null,
                    'provinsi' => $row['provinsi'] ?? null,
                    'is_active' => true,
                ]);

                /*
                 * Buat inventaris default untuk JPL baru.
                 */
                $inventarisService->initializeForJpl($jpl);

                $existingIdentities[$identity] = true;

                $jumlahJpl++;
            }

            return [
                'imported' => $jumlahJpl,
                'additional_duplicates' => $jumlahDuplikatTambahan,
            ];
        });

        $totalSkippedDuplicates = $skippedDuplicates
            + $result['additional_duplicates'];

        /*
         * Hapus session hanya setelah transaksi berhasil.
         */
        session()->forget($sessionKey);

        $message = 'Import selesai. '
            . "{$result['imported']} JPL berhasil disimpan beserta inventaris default. "
            . "{$skippedErrors} baris error dilewati. "
            . "{$totalSkippedDuplicates} baris duplikat dilewati. "
            . 'Resort baru dibuat otomatis jika diperlukan.';

        return redirect()
            ->route("{$prefix}.jpl.index")
            ->with('success', $message);
    }
}