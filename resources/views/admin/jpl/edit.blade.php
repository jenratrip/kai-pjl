@extends('layouts.admin')

@section('title', 'Edit JPL')

@section('content')
<div class="p-4 md:p-6">

    <div class="mb-5">
        <a href="{{ route('admin.jpl.show', $jpl->id) }}"
           class="text-sm font-semibold text-blue-600 hover:underline">
            ← Kembali ke Detail JPL
        </a>
    </div>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-[#1d2d50]">
            Edit Data JPL
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Perbarui identitas dan informasi operasional JPL.
        </p>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="mb-2 font-semibold">Periksa kembali data berikut:</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('admin.jpl.update', $jpl->id) }}"
          class="space-y-6">
        @csrf
        @method('PUT')

        {{-- IDENTITAS --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm md:p-6">
            <h2 class="mb-5 text-lg font-bold text-[#1d2d50]">
                Identitas JPL
            </h2>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="kode_jpl" class="mb-2 block text-sm font-semibold text-gray-700">
                        No JPL
                    </label>

                    <input id="kode_jpl"
                           name="kode_jpl"
                           type="text"
                           value="{{ old('kode_jpl', $jpl->kode_jpl) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="nama_jpl" class="mb-2 block text-sm font-semibold text-gray-700">
                        Nama JPL <span class="text-red-500">*</span>
                    </label>

                    <input id="nama_jpl"
                           name="nama_jpl"
                           type="text"
                           required
                           value="{{ old('nama_jpl', $jpl->nama_jpl) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="resort_id" class="mb-2 block text-sm font-semibold text-gray-700">
                        Resort <span class="text-red-500">*</span>
                    </label>

                    <select id="resort_id"
                            name="resort_id"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                        <option value="">Pilih Resort</option>

                        @foreach($resorts as $resort)
                            <option value="{{ $resort->id }}"
                                @selected((string) old('resort_id', $jpl->resort_id) === (string) $resort->id)>
                                {{ $resort->nama_resort }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="km_hm" class="mb-2 block text-sm font-semibold text-gray-700">
                        KM / HM
                    </label>

                    <input id="km_hm"
                           name="km_hm"
                           type="text"
                           value="{{ old('km_hm', $jpl->km_hm) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="antara" class="mb-2 block text-sm font-semibold text-gray-700">
                        Antara
                    </label>

                    <input id="antara"
                           name="antara"
                           type="text"
                           value="{{ old('antara', $jpl->antara) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="status_penjagaan" class="mb-2 block text-sm font-semibold text-gray-700">
                        Status Penjagaan
                    </label>

                    <input id="status_penjagaan"
                           name="status_penjagaan"
                           type="text"
                           value="{{ old('status_penjagaan', $jpl->status_penjagaan) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- KONDISI JALAN --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm md:p-6">
            <h2 class="mb-5 text-lg font-bold text-[#1d2d50]">
                Informasi Jalur dan Lokasi
            </h2>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="jenis_jalur_ka" class="mb-2 block text-sm font-semibold text-gray-700">
                        Jenis Jalur KA
                    </label>

                    <input id="jenis_jalur_ka"
                           name="jenis_jalur_ka"
                           type="text"
                           value="{{ old('jenis_jalur_ka', $jpl->jenis_jalur_ka) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="konst_jalan" class="mb-2 block text-sm font-semibold text-gray-700">
                        Konst Jalan
                    </label>

                    <input id="konst_jalan"
                           name="konst_jalan"
                           type="text"
                           value="{{ old('konst_jalan', $jpl->konst_jalan) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="lebar_jln" class="mb-2 block text-sm font-semibold text-gray-700">
                        Lebar Jalan (m)
                    </label>

                    <input id="lebar_jln"
                           name="lebar_jln"
                           type="number"
                           min="0"
                           step="any"
                           value="{{ old('lebar_jln', $jpl->lebar_jln) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="titik_koordinat" class="mb-2 block text-sm font-semibold text-gray-700">
                        Titik Koordinat
                    </label>

                    <input id="titik_koordinat"
                           name="titik_koordinat"
                           type="text"
                           value="{{ old('titik_koordinat', $jpl->titik_koordinat) }}"
                           placeholder="-7.xxxxxx, 110.xxxxxx"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="lokasi" class="mb-2 block text-sm font-semibold text-gray-700">
                        Nama Jalan / Daerah
                    </label>

                    <input id="lokasi"
                           name="lokasi"
                           type="text"
                           value="{{ old('lokasi', $jpl->lokasi) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="kota_kabupaten" class="mb-2 block text-sm font-semibold text-gray-700">
                        Kota / Kabupaten
                    </label>

                    <input id="kota_kabupaten"
                           name="kota_kabupaten"
                           type="text"
                           value="{{ old('kota_kabupaten', $jpl->kota_kabupaten) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="provinsi" class="mb-2 block text-sm font-semibold text-gray-700">
                        Provinsi
                    </label>

                    <input id="provinsi"
                           name="provinsi"
                           type="text"
                           value="{{ old('provinsi', $jpl->provinsi) }}"
                           class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="mt-5">
                <label for="keterangan" class="mb-2 block text-sm font-semibold text-gray-700">
                    Keterangan
                </label>

                <textarea id="keterangan"
                          name="keterangan"
                          rows="4"
                          class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">{{ old('keterangan', $jpl->keterangan) }}</textarea>
            </div>
        </div>

        {{-- STATUS --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <label class="flex items-start gap-3">
                <input type="checkbox"
                       name="is_active"
                       value="1"
                       @checked((bool) old('is_active', $jpl->is_active))
                       class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">

                <span>
                    <span class="block text-sm font-semibold text-gray-800">
                        JPL Aktif
                    </span>
                    <span class="mt-1 block text-xs text-gray-500">
                        Nonaktifkan jika JPL tidak lagi digunakan. Data tidak dihapus permanen.
                    </span>
                </span>
            </label>
        </div>

        {{-- TOMBOL --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.jpl.index') }}"
               class="rounded-xl border border-gray-300 px-5 py-3 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Batal
            </a>

            <button type="submit"
                    class="rounded-xl bg-[#1d2d50] px-6 py-3 text-sm font-semibold text-white hover:bg-[#14213d]">
                Simpan Perubahan
            </button>
        </div>
    </form>

</div>
@endsection
