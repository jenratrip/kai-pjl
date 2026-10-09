<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\WilayahImport;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class WilayahImportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Upload & Preview
    |--------------------------------------------------------------------------
    */

    public function preview(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:5120',
            ],
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.file' => 'File yang dikirim tidak valid.',
            'file.mimes' => 'File harus berformat .xlsx atau .xls.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        $file = $request->file('file');

        $path = $file->store('imports/wilayah');

        try {

            /*
            |--------------------------------------------------------------------------
            | Baca Excel
            |--------------------------------------------------------------------------
            */

            $import = new WilayahImport();

            Excel::import(
                $import,
                $path
            );

            /*
            |--------------------------------------------------------------------------
            | Ambil hasil pembacaan
            |--------------------------------------------------------------------------
            */

            $rows = $import->rows;

            /*
            |--------------------------------------------------------------------------
            | Cek apakah Excel kosong
            |--------------------------------------------------------------------------
            */

            if (empty($rows)) {

                Storage::delete($path);

                return redirect()
                    ->route('admin.wilayah.index')
                    ->with(
                        'error',
                        'File Excel tidak memiliki data wilayah.'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Validasi Data
            |--------------------------------------------------------------------------
            */

            $preview = $this->validateRows($rows);

            /*
            |--------------------------------------------------------------------------
            | Simpan sementara ke Session
            |--------------------------------------------------------------------------
            */

            session([
                'wilayah_import' => [
                    'path' => $path,
                    'rows' => $preview,
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Redirect ke Preview
            |--------------------------------------------------------------------------
            */

            return redirect()->route(
                'admin.wilayah.import.preview'
            );

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Hapus file jika gagal
            |--------------------------------------------------------------------------
            */

            Storage::delete($path);

            /*
            |--------------------------------------------------------------------------
            | Untuk sementara tampilkan error sebenarnya
            |--------------------------------------------------------------------------
            */

            return redirect()
                ->route('admin.wilayah.index')
                ->with(
                    'error',
                    'File Excel gagal diproses: ' .
                    $e->getMessage()
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Show Preview
    |--------------------------------------------------------------------------
    */

    public function showPreview()
    {
        $import = session('wilayah_import');

        if (! $import) {

            return redirect()
                ->route('admin.wilayah.index')
                ->with(
                    'error',
                    'Tidak ada data import untuk ditampilkan.'
                );
        }

        return view(
            'admin.wilayah.import-preview'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Confirm Import
    |--------------------------------------------------------------------------
    */

    public function confirm(Request $request)
    {
        $import = session('wilayah_import');

        if (
            ! $import ||
            empty($import['rows'])
        ) {

            return redirect()
                ->route('admin.wilayah.index')
                ->with(
                    'error',
                    'Preview import sudah tidak tersedia. Silakan upload ulang file.'
                );
        }

        $rows = $import['rows'];

        /*
        |--------------------------------------------------------------------------
        | Pastikan Tidak Ada Error
        |--------------------------------------------------------------------------
        */

        $hasError = collect($rows)->contains(
            function ($row) {
                return $row['status'] !== 'valid';
            }
        );

        if ($hasError) {

            return redirect()
                ->route('admin.wilayah.import.preview')
                ->with(
                    'error',
                    'Import tidak dapat dilakukan karena masih terdapat data yang tidak valid.'
                );
        }

        try {

            DB::transaction(function () use ($rows) {

                foreach ($rows as $row) {

                    Wilayah::create([
                        'nama_wilayah' => $row['nama_wilayah'],

                        'keterangan' => $row['keterangan']
                            ?: null,

                        'is_active' => (bool) $row['is_active'],
                    ]);
                }
            });

            /*
            |--------------------------------------------------------------------------
            | Hapus File Temporary
            |--------------------------------------------------------------------------
            */

            if (
                ! empty($import['path'])
            ) {
                Storage::delete(
                    $import['path']
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Hapus Session
            |--------------------------------------------------------------------------
            */

            session()->forget(
                'wilayah_import'
            );

            return redirect()
                ->route('admin.wilayah.index')
                ->with(
                    'success',
                    count($rows) .
                    ' data wilayah berhasil diimport.'
                );

        } catch (Throwable $e) {

            return redirect()
                ->route('admin.wilayah.import.preview')
                ->with(
                    'error',
                    'Import gagal disimpan: ' .
                    $e->getMessage()
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Rows
    |--------------------------------------------------------------------------
    */

    private function validateRows(array $rows): array
    {
        $preview = [];

        $uploadedNames = [];

        foreach ($rows as $row) {

            $errors = [];

            $nama = trim(
                (string) $row['nama_wilayah']
            );

            /*
            |--------------------------------------------------------------------------
            | Normalize Name
            |--------------------------------------------------------------------------
            */

            $normalizedName = mb_strtolower(
                $nama
            );


            /*
            |--------------------------------------------------------------------------
            | Required Nama Wilayah
            |--------------------------------------------------------------------------
            */

            if ($nama === '') {

                $errors[] =
                    'Nama wilayah wajib diisi.';
            }


            /*
            |--------------------------------------------------------------------------
            | Max Length
            |--------------------------------------------------------------------------
            */

            if (
                mb_strlen($nama) > 255
            ) {

                $errors[] =
                    'Nama wilayah maksimal 255 karakter.';
            }


            /*
            |--------------------------------------------------------------------------
            | Duplicate dalam Excel
            |--------------------------------------------------------------------------
            */

            if (
                $normalizedName !== ''
            ) {

                if (
                    isset(
                        $uploadedNames[$normalizedName]
                    )
                ) {

                    $errors[] =
                        'Nama wilayah duplikat dengan baris Excel ' .
                        $uploadedNames[$normalizedName] .
                        '.';

                } else {

                    $uploadedNames[$normalizedName] =
                        $row['row'];
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Duplicate Database
            |--------------------------------------------------------------------------
            */

            if (
                $nama !== '' &&
                Wilayah::where(
                    'nama_wilayah',
                    $nama
                )->exists()
            ) {

                $errors[] =
                    'Nama wilayah sudah terdapat di database.';
            }


            /*
            |--------------------------------------------------------------------------
            | is_active
            |--------------------------------------------------------------------------
            */

            $isActive = $row['is_active'];

            /*
            | Excel terkadang membaca 1/0 sebagai integer,
            | string, atau float.
            */

            if (
                $isActive === null ||
                $isActive === ''
            ) {

                $errors[] =
                    'is_active wajib diisi dengan 1 atau 0.';

            } else {

                $isActiveString =
                    trim(
                        (string) $isActive
                    );

                if (
                    ! in_array(
                        $isActiveString,
                        ['0', '1'],
                        true
                    )
                ) {

                    $errors[] =
                        'is_active hanya boleh bernilai 1 atau 0.';
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Preview Row
            |--------------------------------------------------------------------------
            */

            $preview[] = [

                'row' =>
                    $row['row'],

                'nama_wilayah' =>
                    $nama,

                'keterangan' =>
                    $row['keterangan'],

                'is_active' =>
                    $isActive,

                'status' =>
                    empty($errors)
                        ? 'valid'
                        : 'error',

                'errors' =>
                    $errors,
            ];
        }

        return $preview;
    }
}