@extends('layouts.admin')

@section('title', 'Pengaturan Peringatan Realisasi KA')

@section('content')
<div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">

    {{-- =====================================================
         HEADER
    ====================================================== --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-800">
            Pengaturan Peringatan Realisasi KA
        </h1>

        <p class="mt-2 text-sm text-slate-600">
            Atur ambang batas dan aktifkan atau nonaktifkan setiap jenis
            peringatan Realisasi KA. Seluruh pengaturan disimpan di database
            dan dapat diubah melalui panel Admin.
        </p>
    </div>


    {{-- =====================================================
         NOTIFIKASI
    ====================================================== --}}
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
            <p class="font-semibold">
                Pengaturan belum tersimpan:
            </p>

            <ul class="mt-2 list-inside list-disc text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- =====================================================
         FORM PENGATURAN
    ====================================================== --}}
    <form
        action="{{ route('admin.realisasi-ka-settings.update') }}"
        method="POST"
        class="space-y-6"
    >
        @csrf
        @method('PUT')


        {{-- =================================================
             1. KETERLAMBATAN KA
        ================================================== --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <div>
                <h2 class="text-lg font-semibold text-slate-800">
                    1. Peringatan Keterlambatan KA
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Mengatur batas keterlambatan berdasarkan selisih
                    antara jadwal KA dan waktu aktual KA lewat.
                </p>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">

                {{-- TOLERANSI KETERLAMBATAN --}}
                <div>
                    <label
                        for="toleransi_keterlambatan_menit"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Toleransi keterlambatan (menit)
                    </label>

                    <input
                        id="toleransi_keterlambatan_menit"
                        type="number"
                        name="toleransi_keterlambatan_menit"
                        min="0"
                        max="1440"
                        required
                        value="{{ old('toleransi_keterlambatan_menit', $setting->toleransi_keterlambatan_menit) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        Batas toleransi sebelum KA dikategorikan terlambat.
                    </p>
                </div>


                {{-- KETERLAMBATAN TINGGI --}}
                <div>
                    <label
                        for="peringatan_keterlambatan_tinggi_menit"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Ambang keterlambatan tinggi (menit)
                    </label>

                    <input
                        id="peringatan_keterlambatan_tinggi_menit"
                        type="number"
                        name="peringatan_keterlambatan_tinggi_menit"
                        min="0"
                        max="1440"
                        required
                        value="{{ old('peringatan_keterlambatan_tinggi_menit', $setting->peringatan_keterlambatan_tinggi_menit) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        Harus sama dengan atau lebih besar dari toleransi keterlambatan.
                    </p>
                </div>

            </div>


            {{-- SAKELAR KETERLAMBATAN --}}
            <div class="mt-5 space-y-3 border-t border-slate-100 pt-5">

                <label class="flex items-start gap-3">
                    <input
                        type="hidden"
                        name="warning_keterlambatan_aktif"
                        value="0"
                    >

                    <input
                        type="checkbox"
                        name="warning_keterlambatan_aktif"
                        value="1"
                        @checked((bool) old('warning_keterlambatan_aktif', $setting->warning_keterlambatan_aktif))
                        class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span>
                        <span class="block text-sm font-medium text-slate-700">
                            Aktifkan peringatan keterlambatan biasa
                        </span>

                        <span class="text-xs text-slate-500">
                            Menampilkan peringatan ketika keterlambatan melewati toleransi.
                        </span>
                    </span>
                </label>


                <label class="flex items-start gap-3">
                    <input
                        type="hidden"
                        name="warning_keterlambatan_tinggi_aktif"
                        value="0"
                    >

                    <input
                        type="checkbox"
                        name="warning_keterlambatan_tinggi_aktif"
                        value="1"
                        @checked((bool) old('warning_keterlambatan_tinggi_aktif', $setting->warning_keterlambatan_tinggi_aktif))
                        class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >

                    <span>
                        <span class="block text-sm font-medium text-slate-700">
                            Aktifkan peringatan keterlambatan tinggi
                        </span>

                        <span class="text-xs text-slate-500">
                            Menampilkan indikator khusus ketika keterlambatan mencapai ambang tinggi.
                        </span>
                    </span>
                </label>

            </div>
        </section>



        {{-- =================================================
             2. KA LEBIH AWAL DARI JADWAL
        ================================================== --}}
        <section class="rounded-xl border border-blue-200 bg-white p-5 shadow-sm">

            <div>
                <h2 class="text-lg font-semibold text-slate-800">
                    2. Peringatan KA Lebih Awal dari Jadwal
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Mengatur batas selisih waktu ketika KA melewati JPL
                    lebih awal dibandingkan jadwal yang telah ditentukan.
                </p>
            </div>


            <div class="mt-5 max-w-md">
                <label
                    for="batas_lebih_awal_menit"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Batas lebih awal (menit)
                </label>

                <input
                    id="batas_lebih_awal_menit"
                    type="number"
                    name="batas_lebih_awal_menit"
                    min="1"
                    max="1440"
                    required
                    value="{{ old('batas_lebih_awal_menit', $setting->batas_lebih_awal_menit ?? 2) }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >

                <p class="mt-1 text-xs text-slate-500">
                    Contoh: nilai 2 berarti KA yang melewati JPL lebih awal
                    sekurang-kurangnya sesuai ambang yang ditentukan akan
                    masuk kategori lebih awal.
                </p>
            </div>


            {{-- SAKELAR KA LEBIH AWAL --}}
            <label class="mt-5 flex items-start gap-3 border-t border-slate-100 pt-5">

                <input
                    type="hidden"
                    name="warning_lebih_awal_aktif"
                    value="0"
                >

                <input
                    type="checkbox"
                    name="warning_lebih_awal_aktif"
                    value="1"
                    @checked((bool) old('warning_lebih_awal_aktif', $setting->warning_lebih_awal_aktif ?? true))
                    class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                >

                <span>
                    <span class="block text-sm font-medium text-slate-700">
                        Aktifkan peringatan KA lebih awal
                    </span>

                    <span class="text-xs text-slate-500">
                        Menampilkan indikator ketika KA melewati JPL lebih awal
                        sesuai ambang batas yang ditentukan.
                    </span>
                </span>

            </label>

        </section>



        {{-- =================================================
             3. JARAK ANTAR KA
        ================================================== --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <div>
                <h2 class="text-lg font-semibold text-slate-800">
                    3. Peringatan Jarak Antar KA
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Mengatur ambang minimum jarak waktu aktual antar KA
                    yang melewati JPL yang sama.
                </p>
            </div>


            <div class="mt-5 max-w-md">
                <label
                    for="jarak_minimum_antar_ka_menit"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Jarak minimum antar KA (menit)
                </label>

                <input
                    id="jarak_minimum_antar_ka_menit"
                    type="number"
                    name="jarak_minimum_antar_ka_menit"
                    min="0"
                    max="1440"
                    required
                    value="{{ old('jarak_minimum_antar_ka_menit', $setting->jarak_minimum_antar_ka_menit) }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >

                <p class="mt-1 text-xs text-slate-500">
                    Nilai 0 berarti tidak ada ambang jarak minimum yang ditetapkan.
                </p>
            </div>


            <label class="mt-5 flex items-start gap-3 border-t border-slate-100 pt-5">

                <input
                    type="hidden"
                    name="warning_antar_ka_aktif"
                    value="0"
                >

                <input
                    type="checkbox"
                    name="warning_antar_ka_aktif"
                    value="1"
                    @checked((bool) old('warning_antar_ka_aktif', $setting->warning_antar_ka_aktif))
                    class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                >

                <span>
                    <span class="block text-sm font-medium text-slate-700">
                        Aktifkan peringatan jarak antar KA
                    </span>

                    <span class="text-xs text-slate-500">
                        Menampilkan peringatan jika jarak waktu antar KA
                        berada di bawah batas minimum yang ditentukan.
                    </span>
                </span>

            </label>

        </section>



        {{-- =================================================
             4. AWAS 2 ARAH
        ================================================== --}}
        <section class="rounded-xl border border-pink-200 bg-white p-5 shadow-sm">

            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-semibold text-slate-800">
                        4. Peringatan AWAS 2 ARAH
                    </h2>

                    <span class="rounded-full bg-pink-100 px-2.5 py-1 text-xs font-semibold text-pink-700">
                        Indikator khusus
                    </span>
                </div>

                <p class="mt-1 text-sm text-slate-500">
                    Mengatur deteksi kondisi dua KA dari arah berlawanan
                    yang memiliki jarak waktu berdekatan pada JPL yang sama.
                </p>
            </div>


            <div class="mt-5 max-w-md">
                <label
                    for="batas_awas_2_arah_menit"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Ambang waktu AWAS 2 ARAH (menit)
                </label>

                <input
                    id="batas_awas_2_arah_menit"
                    type="number"
                    name="batas_awas_2_arah_menit"
                    min="0"
                    max="1440"
                    required
                    value="{{ old('batas_awas_2_arah_menit', $setting->batas_awas_2_arah_menit ?? 5) }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-pink-500 focus:outline-none focus:ring-2 focus:ring-pink-100"
                >

                <p class="mt-1 text-xs text-slate-500">
                    Nilai ini digunakan khusus untuk mendeteksi kedekatan
                    waktu KA dari arah berlawanan.
                </p>
            </div>


            {{-- SAKELAR AWAS 2 ARAH --}}
            <label class="mt-5 flex items-start gap-3 border-t border-slate-100 pt-5">

                <input
                    type="hidden"
                    name="warning_awas_2_arah_aktif"
                    value="0"
                >

                <input
                    type="checkbox"
                    name="warning_awas_2_arah_aktif"
                    value="1"
                    @checked((bool) old('warning_awas_2_arah_aktif', $setting->warning_awas_2_arah_aktif ?? false))
                    class="mt-1 rounded border-slate-300 text-pink-600 focus:ring-pink-500"
                >

                <span>
                    <span class="block text-sm font-medium text-slate-700">
                        Aktifkan peringatan AWAS 2 ARAH
                    </span>

                    <span class="text-xs text-slate-500">
                        Menampilkan indikator AWAS ketika kondisi dua KA
                        dari arah berlawanan memenuhi ambang yang ditentukan.
                    </span>
                </span>

            </label>


            <div class="mt-4 rounded-lg border border-pink-100 bg-pink-50 p-3 text-xs leading-relaxed text-pink-800">
                AWAS 2 ARAH merupakan indikator tambahan dan harus
                didasarkan pada informasi arah perjalanan yang valid.
                Indikator ini tidak menggantikan prosedur keselamatan
                dan keputusan operasional petugas.
            </div>

        </section>



        {{-- =================================================
             5. DURASI PENUTUPAN PALANG
        ================================================== --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <div>
                <h2 class="text-lg font-semibold text-slate-800">
                    5. Peringatan Durasi Penutupan Palang
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Mengatur batas durasi penutupan palang pada JPL.
                </p>
            </div>


            <div class="mt-5 max-w-md">
                <label
                    for="batas_penutupan_palang_menit"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Batas penutupan palang (menit)
                </label>

                <input
                    id="batas_penutupan_palang_menit"
                    type="number"
                    name="batas_penutupan_palang_menit"
                    min="0"
                    max="1440"
                    required
                    value="{{ old('batas_penutupan_palang_menit', $setting->batas_penutupan_palang_menit) }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >

                <p class="mt-1 text-xs text-slate-500">
                    Nilai 0 berarti tidak ada batas durasi yang ditetapkan.
                </p>
            </div>


            <label class="mt-5 flex items-start gap-3 border-t border-slate-100 pt-5">

                <input
                    type="hidden"
                    name="warning_penutupan_palang_aktif"
                    value="0"
                >

                <input
                    type="checkbox"
                    name="warning_penutupan_palang_aktif"
                    value="1"
                    @checked((bool) old('warning_penutupan_palang_aktif', $setting->warning_penutupan_palang_aktif))
                    class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                >

                <span>
                    <span class="block text-sm font-medium text-slate-700">
                        Aktifkan peringatan durasi penutupan palang
                    </span>

                    <span class="text-xs text-slate-500">
                        Menampilkan peringatan jika durasi penutupan palang
                        melewati batas yang telah ditentukan.
                    </span>
                </span>

            </label>


            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-800">
                Peringatan durasi penutupan hanya dapat dihitung apabila
                sistem telah mencatat waktu mulai penutupan dan waktu
                pembukaan palang secara aktual.
            </div>

        </section>



        {{-- =================================================
             CATATAN OPERASIONAL
        ================================================== --}}
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-relaxed text-amber-900">

            <p class="font-semibold">
                Catatan operasional
            </p>

            <p class="mt-1">
                Peringatan pada sistem merupakan indikator untuk membantu
                pemantauan dan verifikasi petugas. Peringatan bukan pengganti
                prosedur keselamatan maupun keputusan operasional resmi.
                Pastikan seluruh ambang ditetapkan sesuai ketentuan yang berlaku
                sebelum fitur peringatan diaktifkan.
            </p>

        </div>



        {{-- =================================================
             TOMBOL AKSI
        ================================================== --}}
        <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">

            <a
                href="{{ route('admin.realisasi-ka.index') }}"
                class="rounded-lg border border-slate-300 px-5 py-3 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Kembali
            </a>

            <button
                type="submit"
                class="rounded-lg bg-[#1d2d50] px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
            >
                Simpan Pengaturan
            </button>

        </div>

    </form>
</div>
@endsection
