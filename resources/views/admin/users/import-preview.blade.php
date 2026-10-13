@extends('layouts.admin')

@section('title', 'Preview Import User')

@section('content')
<div class="p-6">

    {{-- Header --}}
    <div class="mb-6">
        <a
            href="{{ route('admin.users.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-gray-500 hover:text-[#1d2d50]"
        >
            ← Kembali ke Manajemen User
        </a>

        <h1 class="text-2xl font-bold text-[#1d2d50]">
            Preview Import User
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Periksa data sebelum menyimpan akun PJL ke database.
        </p>
    </div>

    {{-- Ringkasan --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">

        <div class="rounded-xl border border-green-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Data Valid</p>
            <p class="mt-1 text-2xl font-bold text-green-600">
                {{ count($validRows) }}
            </p>
        </div>

        <div class="rounded-xl border border-red-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Data Bermasalah</p>
            <p class="mt-1 text-2xl font-bold text-red-600">
                {{ count($errors) }}
            </p>
        </div>

    </div>

    {{-- Data valid --}}
    <div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="border-b border-gray-200 p-5">
            <h2 class="font-bold text-[#1d2d50]">
                Data yang Siap Diimpor
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Hanya data yang lolos validasi yang akan disimpan.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">

                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-4">No.</th>
                        <th class="px-5 py-4">NIP</th>
                        <th class="px-5 py-4">Nama</th>
                        <th class="px-5 py-4">Email</th>
                        <th class="px-5 py-4">No. HP</th>
                        <th class="px-5 py-4">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($validRows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">{{ $loop->iteration }}</td>
                            <td class="whitespace-nowrap px-5 py-4 font-medium">
                                {{ $row['nip'] }}
                            </td>
                            <td class="px-5 py-4">{{ $row['name'] }}</td>
                            <td class="px-5 py-4">{{ $row['email'] }}</td>
                            <td class="px-5 py-4">{{ $row['phone'] ?: '-' }}</td>
                            <td class="px-5 py-4">
                                @if ($row['is_active'])
                                    <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500">
                                Tidak ada data valid untuk diimpor.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    {{-- Data bermasalah --}}
    @if (count($errors) > 0)
        <div class="mb-6 overflow-hidden rounded-xl border border-red-200 bg-white shadow-sm">

            <div class="border-b border-red-100 p-5">
                <h2 class="font-bold text-red-700">
                    Data yang Perlu Diperbaiki
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Perbaiki kesalahan pada file Excel, lalu unggah kembali.
                </p>
            </div>

            <div class="space-y-4 p-5">
                @foreach ($errors as $error)
                    <div class="rounded-lg border border-red-100 bg-red-50 p-4">
                        <p class="font-semibold text-gray-800">
                            Baris {{ $error['row'] }} —
                            {{ $error['name'] ?: 'Nama kosong' }}
                            (NIP: {{ $error['nip'] ?: '-' }})
                        </p>

                        <ul class="mt-2 list-inside list-disc text-sm text-red-700">
                            @foreach ($error['messages'] as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Konfirmasi --}}
    <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">

        <a
            href="{{ route('admin.users.index') }}"
            class="rounded-lg border border-gray-300 px-5 py-3 text-center text-sm font-semibold text-gray-600 hover:bg-gray-50"
        >
            Batal
        </a>

        @if (count($validRows) > 0)
            <form
                action="{{ route('admin.users.import.confirm') }}"
                method="POST"
                onsubmit="return confirm('Simpan {{ count($validRows) }} akun PJL yang valid ke database?')"
            >
                @csrf

                <input
                    type="hidden"
                    name="token"
                    value="{{ $token }}"
                >

                <button
                    type="submit"
                    class="w-full rounded-lg bg-[#1d2d50] px-5 py-3 text-sm font-semibold text-white hover:bg-[#16233f] sm:w-auto"
                >
                    Konfirmasi dan Simpan
                </button>
            </form>
        @endif

    </div>

</div>
@endsection