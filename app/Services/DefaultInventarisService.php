<?php

namespace App\Services;

use App\Models\Inventaris;
use App\Models\Jpl;

class DefaultInventarisService
{
    /**
     * Daftar inventaris wajib PJL.
     * Jumlah berdasarkan formulir standar.
     */
    private const BARANG_DEFAULT = [
        ['nama' => 'Daftar perjalanan KA', 'jumlah' => 1],
        ['nama' => 'Buku realisasi KA yang lewat', 'jumlah' => 1],
        ['nama' => 'Buku gangguan', 'jumlah' => 1],
        ['nama' => 'Buku tamu/instruksi', 'jumlah' => 2],
        ['nama' => 'Gambar semboyan', 'jumlah' => 1],
        ['nama' => 'Lentera', 'jumlah' => 1],
        ['nama' => 'Lampu senter', 'jumlah' => 1],
        ['nama' => 'HT', 'jumlah' => 1],
        ['nama' => 'Bendera merah (semboyan 3)', 'jumlah' => 2],
        ['nama' => 'Bendera kuning (semboyan 2A)', 'jumlah' => 1],
        ['nama' => 'Bendera kuning (semboyan 2B)', 'jumlah' => 1],
        ['nama' => 'Rambu stop', 'jumlah' => 2],
        ['nama' => 'Pet PJL', 'jumlah' => 1],
        ['nama' => 'Peluit', 'jumlah' => 1],
        ['nama' => 'Jam dinding', 'jumlah' => 1],
        ['nama' => 'Kotak P3K', 'jumlah' => 1],
        ['nama' => 'APAR', 'jumlah' => 1],
    ];

    /**
     * Inisialisasi inventaris standar untuk satu JPL.
     *
     * - Tidak membuat duplikasi berdasarkan JPL + nama barang.
     * - Tidak mengubah jumlah inventaris yang sudah ada.
     * - Barang baru otomatis aktif.
     */
    public function initializeForJpl(Jpl $jpl): void
    {
        foreach (self::BARANG_DEFAULT as $barang) {
            Inventaris::firstOrCreate(
                [
                    'jpl_id' => $jpl->id,
                    'nama_barang' => $barang['nama'],
                ],
                [
                    'kategori' => 'inventaris_pjl',
                    'lokasi' => $jpl->nama_jpl,
                    'jumlah' => $barang['jumlah'],
                    'is_active' => true,
                    'kondisi_terakhir' => 'baik',
                    'status_tinjauan' => 'normal',
                ]
            );
        }
    }
}
