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
        */

        settings: {

            // -------------------------------------------------
            // KETERLAMBATAN KA
            // -------------------------------------------------

            toleransiKeterlambatanMenit: @json(
                (int) data_get($realisasiKaSetting, 'toleransi_keterlambatan_menit', 5)
            ),

            peringatanKeterlambatanTinggiMenit: @json(
                (int) data_get($realisasiKaSetting, 'peringatan_keterlambatan_tinggi_menit', 10)
            ),

            warningKeterlambatanAktif: @json(
                (bool) data_get($realisasiKaSetting, 'warning_keterlambatan_aktif', false)
            ),

            warningKeterlambatanTinggiAktif: @json(
                (bool) data_get($realisasiKaSetting, 'warning_keterlambatan_tinggi_aktif', false)
            ),

            // -------------------------------------------------
            // KA LEBIH CEPAT DARI JADWAL
            // -------------------------------------------------

            batasLebihCepatMenit: @json(
                (int) data_get($realisasiKaSetting, 'batas_lebih_awal_menit', 2)
            ),

            warningLebihAwalAktif: @json(
                (bool) data_get($realisasiKaSetting, 'warning_lebih_awal_aktif', true)
            ),

            // -------------------------------------------------
            // JARAK MINIMUM ANTAR KA
            // -------------------------------------------------

            jarakMinimumAntarKaMenit: @json(
                (int) data_get($realisasiKaSetting, 'jarak_minimum_antar_ka_menit', 0)
            ),

            warningAntarKaAktif: @json(
                (bool) data_get($realisasiKaSetting, 'warning_antar_ka_aktif', false)
            ),

            // -------------------------------------------------
            // AWAS 2 ARAH
            // -------------------------------------------------

            batasAwas2ArahMenit: @json(
                (int) data_get($realisasiKaSetting, 'batas_awas_2_arah_menit', 5)
            ),

            warningAwas2ArahAktif: @json(
                (bool) data_get($realisasiKaSetting, 'warning_awas_2_arah_aktif', false)
            ),

            // -------------------------------------------------
            // DURASI PENUTUPAN PALANG
            // -------------------------------------------------

            batasPenutupanPalangMenit: @json(
                (int) data_get($realisasiKaSetting, 'batas_penutupan_palang_menit', 0)
            ),

            warningPenutupanPalangAktif: @json(
                (bool) data_get($realisasiKaSetting, 'warning_penutupan_palang_aktif', false)
            )
        }
    };
</script>
