@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Tambah Resort
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Tambahkan data resort dan hubungkan dengan wilayah operasional.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <form action="{{ route('admin.resort.store') }}" method="POST">
            @csrf

            <div class="mb-5">
                <label for="wilayah_id"
                       class="mb-2 block text-sm font-medium text-gray-700">
                    Wilayah <span class="text-red-500">*</span>
                </label>

                <select
                    name="wilayah_id"
                    id="wilayah_id"
                    required
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none"
                >
                    <option value="">Pilih wilayah</option>

                    @foreach ($wilayahs as $wilayah)
                        <option
                            value="{{ $wilayah->id }}"
                            @selected(old('wilayah_id') == $wilayah->id)
                        >
                            {{ $wilayah->nama_wilayah }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-5">
                <label for="nama_resort"
                       class="mb-2 block text-sm font-medium text-gray-700">
                    Nama Resort <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="nama_resort"
                    id="nama_resort"
                    value="{{ old('nama_resort') }}"
                    required
                    maxlength="255"
                    placeholder="Masukkan nama resort"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none"
                >
            </div>

            <div class="mb-6">
                <label for="keterangan"
                       class="mb-2 block text-sm font-medium text-gray-700">
                    Keterangan
                </label>

                <textarea
                    name="keterangan"
                    id="keterangan"
                    rows="4"
                    placeholder="Keterangan tambahan (opsional)"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none"
                >{{ old('keterangan') }}</textarea>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <a
                    href="{{ route('admin.resort.index') }}"
                    class="rounded-lg border border-gray-300 px-4 py-2.5 text-gray-700 hover:bg-gray-50"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-blue-700 px-5 py-2.5 font-medium text-white hover:bg-blue-800"
                >
                    Simpan Resort
                </button>
            </div>
        </form>
    </div>
</div>
@endsection