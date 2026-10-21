@extends('layouts.admin')

@section('title', 'Pengaturan Peringatan Realisasi KA')

@section('content')
<div class="mx-auto max-w-5xl space-y-6 p-4 md:p-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">
            Pengaturan Peringatan Realisasi KA
        </h1>
        <p class="mt-2 text-sm text-slate-600">
            Atur ambang batas dan aktifkan atau nonaktifkan setiap jenis
            peringatan. Nilai disimpan di database dan dapat diubah melalui panel ini.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
            <p class="font-semibold">Pengaturan belum tersimpan:</p>
            <ul class="mt-2 list-inside list-disc text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        action="{{ route('admin.realisasi-ka-settings.update') }}"
        method="POST"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">
                1. Peringatan keterlambatan KA
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Ambang dihitung berdasarkan selisih antara jadwal dan waktu aktual KA lewat.
            </p>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <label for="toleransi_keterlambatan_menit"
                           class="mb-2 block text-sm font-medium text-slate-700">
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
                        class="w-full rounded-lg border border-slate-300 px-3 py-2"
                    >
                    <p class="mt-1 text-xs text-slate-500">
                        Peringatan keterlambatan biasa menggunakan ambang ini.
                    </p>
                </div>

                <div>
                    <label for="peringatan_keterlambatan_tinggi_menit"
                           class="mb-2 block text-sm font-medium text-slate-700">
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
                        class="w-full rounded-lg border border-slate-300 px-3 py-2"
                    >
                    <p class="mt-1 text-xs text-slate-500">
                        Harus sama dengan atau lebih besar dari toleransi keterlambatan.
                    </p>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                <label class="flex items-center gap-3">
                    <input type="hidden" name="warning_keterlambatan_aktif" value="0">
                    <input
                        type="checkbox"
                        name="warning_keterlambatan_aktif"
                        value="1"
                        @checked((bool) old('warning_keterlambatan_aktif', $setting->warning_keterlambatan_aktif))
                        class="rounded border-slate-300"
                    >
                    <span class="text-sm text-slate-700">
                        Aktifkan peringatan keterlambatan biasa
                    </span>
                </label>

                <label class="flex items-center gap-3">
                    <input type="hidden" name="warning_keterlambatan_tinggi_aktif" value="0">
                    <input
                        type="checkbox"
                        name="warning_keterlambatan_tinggi_aktif"
                        value="1"
                        @checked((bool) old('warning_keterlambatan_tinggi_aktif', $setting->warning_keterlambatan_tinggi_aktif))
                        class="rounded border-slate-300"
                    >
                    <span class="text-sm text-slate-700">
                        Aktifkan peringatan keterlambatan tinggi
                    </span>
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">
                2. Jarak antar KA di JPL yang sama
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Ambang minimum untuk mendeteksi jarak waktu aktual yang terlalu dekat.
            </p>

            <div class="mt-5 max-w-md">
                <label for="jarak_minimum_antar_ka_menit"
                       class="mb-2 block text-sm font-medium text-slate-700">
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
                    class="w-full rounded-lg border border-slate-300 px-3 py-2"
                >
            </div>

            <label class="mt-5 flex items-center gap-3">
                <input type="hidden" name="warning_antar_ka_aktif" value="0">
                <input
                    type="checkbox"
                    name="warning_antar_ka_aktif"
                    value="1"
                    @checked((bool) old('warning_antar_ka_aktif', $setting->warning_antar_ka_aktif))
                    class="rounded border-slate-300"
                >
                <span class="text-sm text-slate-700">
                    Aktifkan peringatan jarak antar KA
                </span>
            </label>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-800">
                3. Durasi penutupan palang
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Peringatan durasi baru dapat dihitung setelah waktu palang mulai
                ditutup dan waktu palang dibuka benar-benar dicatat.
            </p>

            <div class="mt-5 max-w-md">
                <label for="batas_penutupan_palang_menit"
                       class="mb-2 block text-sm font-medium text-slate-700">
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
                    class="w-full rounded-lg border border-slate-300 px-3 py-2"
                >
            </div>

            <label class="mt-5 flex items-center gap-3">
                <input type="hidden" name="warning_penutupan_palang_aktif" value="0">
                <input
                    type="checkbox"
                    name="warning_penutupan_palang_aktif"
                    value="1"
                    @checked((bool) old('warning_penutupan_palang_aktif', $setting->warning_penutupan_palang_aktif))
                    class="rounded border-slate-300"
                >
                <span class="text-sm text-slate-700">
                    Aktifkan peringatan durasi penutupan palang
                </span>
            </label>
        </section>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Peringatan merupakan indikator untuk diverifikasi petugas, bukan pengganti
            prosedur atau keputusan operasional resmi. Tetapkan ambang sesuai ketentuan
            yang berlaku sebelum mengaktifkannya.
        </div>

        <div class="flex justify-end">
            <button
                type="submit"
                class="rounded-lg bg-[#1d2d50] px-5 py-3 font-semibold text-white hover:bg-slate-700"
            >
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
