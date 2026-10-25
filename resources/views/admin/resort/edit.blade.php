@extends('layouts.admin')

@section('title', 'Edit Resort')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Edit Resort
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Perbarui informasi resort dan wilayah operasionalnya.
            </p>
        </div>

        <a
            href="{{ route('admin.resort.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
        >
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </a>
    </div>

    {{-- Error Validasi --}}
    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Edit --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

        <div class="mb-6 border-b border-gray-100 pb-4">
            <h2 class="text-lg font-semibold text-[#1d2d50]">
                Informasi Resort
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Ubah data di bawah ini, lalu simpan perubahan.
            </p>
        </div>

        <form
            action="{{ route('admin.resort.update', $resort) }}"
            method="POST"
        >
            @csrf
            @method('PUT')

            {{-- Wilayah --}}
            <div class="mb-5">
                <label for="wilayah_id"
                    class="mb-2 block text-sm font-medium text-gray-700">
                    Wilayah <span class="text-red-500">*</span>
                </label>

                <select
                    id="wilayah_id"
                    name="wilayah_id"
                    required
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                >
                    <option value="">Pilih Wilayah</option>

                    @foreach ($wilayahs as $wilayah)
                        <option
                            value="{{ $wilayah->id }}"
                            @selected((string) old('wilayah_id', $resort->wilayah_id) === (string) $wilayah->id)
                        >
                            {{ $wilayah->nama_wilayah }}
                            @if (!$wilayah->is_active)
                                (Nonaktif)
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Nama Resort --}}
            <div class="mb-5">
                <label for="nama_resort"
                    class="mb-2 block text-sm font-medium text-gray-700">
                    Nama Resort <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="nama_resort"
                    name="nama_resort"
                    value="{{ old('nama_resort', $resort->nama_resort) }}"
                    maxlength="255"
                    required
                    placeholder="Masukkan nama resort"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                >
            </div>

            {{-- Keterangan --}}
            <div class="mb-6">
                <label for="keterangan"
                    class="mb-2 block text-sm font-medium text-gray-700">
                    Keterangan
                </label>

                <textarea
                    id="keterangan"
                    name="keterangan"
                    rows="4"
                    placeholder="Masukkan keterangan tambahan (opsional)"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                >{{ old('keterangan', $resort->keterangan) }}</textarea>
            </div>

            {{-- Informasi Status --}}
            <div class="mb-6 rounded-lg bg-gray-50 p-4">
                <p class="text-sm font-medium text-gray-700">
                    Status Resort Saat Ini
                </p>

                <div class="mt-2">
                    @if ($resort->is_active)
                        <span class="inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                            Aktif
                        </span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold text-gray-600">
                            Nonaktif
                        </span>
                    @endif
                </div>

                <p class="mt-2 text-xs text-gray-500">
                    Status dapat diubah melalui tombol aksi pada halaman Data Resort.
                </p>
            </div>

            {{-- Buttons --}}
            <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-5">
                <a
                    href="{{ route('admin.resort.index') }}"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#f58220] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#d96f12]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
