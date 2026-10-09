@extends('layouts.admin')

@section('title', 'Tambah Wilayah')

@section('content')

<div class="p-6">

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('admin.wilayah.index') }}"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50"
            >
                ←
            </a>

            <div>
                <h2 class="text-xl font-bold text-[#1d2d50]">
                    Tambah Wilayah
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Tambahkan wilayah operasional baru.
                </p>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="max-w-3xl rounded-xl border border-gray-200 bg-white shadow-sm">

        <form
            action="{{ route('admin.wilayah.store') }}"
            method="POST"
        >

            @csrf

            <div class="p-6">

                {{-- Nama Wilayah --}}
                <div class="mb-5">
                    <label
                        for="nama_wilayah"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Nama Wilayah
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="nama_wilayah"
                        name="nama_wilayah"
                        value="{{ old('nama_wilayah') }}"
                        placeholder="Contoh: Wilayah 6.6 Klaten"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10 @error('nama_wilayah') border-red-500 @enderror"
                    >

                    @error('nama_wilayah')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Keterangan --}}
                <div>
                    <label
                        for="keterangan"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Keterangan
                    </label>

                    <textarea
                        id="keterangan"
                        name="keterangan"
                        rows="4"
                        placeholder="Masukkan keterangan wilayah jika diperlukan..."
                        class="w-full resize-none rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10 @error('keterangan') border-red-500 @enderror"
                    >{{ old('keterangan') }}</textarea>

                    @error('keterangan')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            {{-- Footer Form --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">

                <a
                    href="{{ route('admin.wilayah.index') }}"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#f5a400] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#df9400]"
                >
                    Simpan Wilayah
                </button>

            </div>

        </form>

    </div>

</div>

@endsection