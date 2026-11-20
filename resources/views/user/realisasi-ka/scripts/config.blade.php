{{-- =====================================================
     KONFIGURASI DATA REALISASI KA DAN PENGATURAN ADMIN
===================================================== --}}

<script>
    /*
    |--------------------------------------------------------------------------
    | KONFIGURASI UTAMA REALISASI KA
    |--------------------------------------------------------------------------
    */

    window.realisasiKaConfig = {
        /*
        |--------------------------------------------------------------------------
        | 1. DATA REALISASI KA
        |--------------------------------------------------------------------------
        */

        data: @json($dataKA ?? []),

        /*
        |--------------------------------------------------------------------------
        | 2. URL PENYIMPANAN REALISASI
        |--------------------------------------------------------------------------
        */

        storeUrl: @json(route('user.realisasi-ka.store')),

        /*
        |--------------------------------------------------------------------------
        | 3. INFORMASI SHIFT DINAS
        |--------------------------------------------------------------------------
        */

        shiftStart: @json(data_get($dinasAktif, 'shift.jam_mulai')),

        shiftEnd: @json(data_get($dinasAktif, 'shift.jam_selesai')),

        /*
        |--------------------------------------------------------------------------
        | 4. LOKALISASI
        |--------------------------------------------------------------------------
        */

        timeZone: 'Asia/Jakarta',

        locale: 'id-ID',

        /*
        |--------------------------------------------------------------------------
        | 5. PENGATURAN PERINGATAN DARI DATABASE
        |--------------------------------------------------------------------------
        |
        | Seluruh nilai ambang batas dan sakelar peringatan
        | bersumber dari pengaturan yang disimpan oleh admin.
        |
        | Tidak ada nilai konfigurasi alternatif di frontend.
        |
        */

        settings: {
            /*
            |--------------------------------------------------------------------------
            | KETERLAMBATAN KA
            |--------------------------------------------------------------------------
            */

            toleransiKeterlambatanMenit: @json(
                (int) $realisasiKaSetting->toleransi_keterlambatan_menit
            ),

            peringatanKeterlambatanTinggiMenit: @json(
                (int) $realisasiKaSetting->peringatan_keterlambatan_tinggi_menit
            ),

            warningKeterlambatanAktif: @json(
                (bool) $realisasiKaSetting->warning_keterlambatan_aktif
            ),

            warningKeterlambatanTinggiAktif: @json(
                (bool) $realisasiKaSetting->warning_keterlambatan_tinggi_aktif
            ),

            /*
            |--------------------------------------------------------------------------
            | KA LEBIH CEPAT DARI JADWAL
            |--------------------------------------------------------------------------
            */

            batasLebihCepatMenit: @json(
                (int) $realisasiKaSetting->batas_lebih_awal_menit
            ),

            warningLebihAwalAktif: @json(
                (bool) $realisasiKaSetting->warning_lebih_awal_aktif
            ),

            /*
            |--------------------------------------------------------------------------
            | JARAK MINIMUM ANTAR KA
            |--------------------------------------------------------------------------
            */

            jarakMinimumAntarKaMenit: @json(
                (int) $realisasiKaSetting->jarak_minimum_antar_ka_menit
            ),

            warningAntarKaAktif: @json(
                (bool) $realisasiKaSetting->warning_antar_ka_aktif
            ),

            /*
            |--------------------------------------------------------------------------
            | AWAS 2 ARAH
            |--------------------------------------------------------------------------
            */

            batasAwas2ArahMenit: @json(
                (int) $realisasiKaSetting->batas_awas_2_arah_menit
            ),

            warningAwas2ArahAktif: @json(
                (bool) $realisasiKaSetting->warning_awas_2_arah_aktif
            ),

            /*
            |--------------------------------------------------------------------------
            | DURASI PENUTUPAN PALANG
            |--------------------------------------------------------------------------
            */

            batasPenutupanPalangMenit: @json(
                (int) $realisasiKaSetting->batas_penutupan_palang_menit
            ),

            warningPenutupanPalangAktif: @json(
                (bool) $realisasiKaSetting->warning_penutupan_palang_aktif
            )
        }
    };
</script>
