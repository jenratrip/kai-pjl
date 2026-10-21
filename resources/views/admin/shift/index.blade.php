@extends('layouts.admin')

@section('title', 'Manajemen Shift - Sistem PJL')

@section('content')
<div
    class="p-4 md:p-6 lg:p-8"
    x-data="{
        showAdd: false,
        showEdit: false,
        showDelete: false,
        editData: {
            id: null,
            nama_shift: '',
            jam_mulai: '',
            jam_selesai: '',
            dinas_count: 0
        },
        deleteData: {
            id: null,
            nama_shift: ''
        },
        openEdit(data) {
            this.editData = { ...data };
            this.showEdit = true;
        },
        openDelete(data) {
            this.deleteData = { ...data };
            this.showDelete = true;
        },
        closeModals() {
            this.showAdd = false;
            this.showEdit = false;
            this.showDelete = false;
        }
    }"
    @keydown.escape.window="closeModals()"
>

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Manajemen Shift
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Kelola konfigurasi waktu shift petugas penjaga perlintasan.
            </p>
        </div>

        <button
            type="button"
            @click="showAdd = true"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#1d2d50] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#2e4674]"
        >
            <span class="text-lg leading-none">+</span>
            Tambah Shift
        </button>
    </div>

    {{-- NOTIFIKASI --}}
    @if(session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Data belum berhasil disimpan.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- STATISTIK --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Shift</p>
            <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                {{ $shifts->count() }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Shift Digunakan</p>
            <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                {{ $shifts->filter(fn ($shift) => $shift->dinas_count > 0)->count() }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Jadwal Terkait</p>
            <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                {{ $shifts->sum('dinas_count') }}
            </p>
        </div>

    </div>

    {{-- TABEL SHIFT --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-semibold text-[#1d2d50]">
                Daftar Shift
            </h2>
            <p class="mt-1 text-xs text-slate-500">
                Jam yang ditampilkan merupakan konfigurasi master shift.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4 font-semibold">No.</th>
                        <th class="px-5 py-4 font-semibold">Nama Shift</th>
                        <th class="px-5 py-4 font-semibold">Jam Mulai</th>
                        <th class="px-5 py-4 font-semibold">Jam Selesai</th>
                        <th class="px-5 py-4 font-semibold">Jadwal Terkait</th>
                        <th class="px-5 py-4 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($shifts as $shift)
                        <tr class="transition hover:bg-slate-50">

                            <td class="px-5 py-4 text-slate-500">
                                {{ $loop->iteration }}
                            </td>

                            <td class="px-5 py-4">
                                <span class="font-semibold text-[#1d2d50]">
                                    {{ $shift->nama_shift }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="rounded-lg bg-blue-50 px-3 py-1.5 font-medium text-blue-700">
                                    {{ substr($shift->jam_mulai, 0, 5) }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="rounded-lg bg-slate-100 px-3 py-1.5 font-medium text-slate-700">
                                    {{ substr($shift->jam_selesai, 0, 5) }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                @if($shift->dinas_count > 0)
                                    <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                                        {{ $shift->dinas_count }} jadwal
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500">
                                        Belum digunakan
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">

                                    <button
                                        type="button"
                                        @click="openEdit({
                                            id: {{ $shift->id }},
                                            nama_shift: @js($shift->nama_shift),
                                            jam_mulai: @js(substr($shift->jam_mulai, 0, 5)),
                                            jam_selesai: @js(substr($shift->jam_selesai, 0, 5)),
                                            dinas_count: {{ $shift->dinas_count }}
                                        })"
                                        class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-50"
                                    >
                                        Edit
                                    </button>

                                    @if($shift->dinas_count == 0)
                                        <button
                                            type="button"
                                            @click="openDelete({
                                                id: {{ $shift->id }},
                                                nama_shift: @js($shift->nama_shift)
                                            })"
                                            class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                                        >
                                            Hapus
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            disabled
                                            title="Shift sudah digunakan dalam jadwal dinas"
                                            class="cursor-not-allowed rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-400"
                                        >
                                            Hapus
                                        </button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                Belum ada data shift. Klik Tambah Shift untuk membuat konfigurasi pertama.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- INFORMASI --}}
    <div class="mt-5 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
        <p class="font-semibold">Informasi Pengaturan Shift</p>
        <p class="mt-1 leading-relaxed">
            Perubahan konfigurasi shift berlaku saat membuat atau menghitung ulang jadwal dinas.
            Jam dinas yang sudah tersimpan tidak berubah otomatis.
            Shift yang sudah memiliki jadwal dinas tidak dapat dihapus.
        </p>
    </div>

    {{-- MODAL TAMBAH --}}
    <div
        x-show="showAdd"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Tambah Shift"
    >
        <div
            @click.outside="showAdd = false"
            class="w-full max-w-lg rounded-2xl bg-white shadow-xl"
        >
            <form action="{{ route('admin.shift.store') }}" method="POST">
                @csrf

                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                    <h3 class="text-lg font-bold text-[#1d2d50]">
                        Tambah Shift
                    </h3>
                    <button type="button" @click="showAdd = false" class="text-2xl text-slate-400 hover:text-slate-700">
                        &times;
                    </button>
                </div>

                <div class="space-y-5 p-6">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Nama Shift
                        </label>
                        <input
                            type="text"
                            name="nama_shift"
                            value="{{ old('nama_shift') }}"
                            maxlength="255"
                            required
                            placeholder="Contoh: Pagi"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Jam Mulai
                            </label>
                            <input
                                type="time"
                                name="jam_mulai"
                                value="{{ old('jam_mulai') }}"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Jam Selesai
                            </label>
                            <input
                                type="time"
                                name="jam_selesai"
                                value="{{ old('jam_selesai') }}"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                        </div>
                    </div>

                    <p class="text-xs leading-relaxed text-slate-500">
                        Shift lintas tengah malam didukung, misalnya pukul 22.00 sampai 06.00.
                    </p>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button
                        type="button"
                        @click="showAdd = false"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#2e4674]"
                    >
                        Simpan Shift
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT --}}
    <div
        x-show="showEdit"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Edit Shift"
    >
        <div
            @click.outside="showEdit = false"
            class="w-full max-w-lg rounded-2xl bg-white shadow-xl"
        >
            <form
                :action="'{{ url('/admin/shift') }}/' + editData.id"
                method="POST"
            >
                @csrf
                @method('PUT')

                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                    <h3 class="text-lg font-bold text-[#1d2d50]">
                        Edit Shift
                    </h3>
                    <button type="button" @click="showEdit = false" class="text-2xl text-slate-400 hover:text-slate-700">
                        &times;
                    </button>
                </div>

                <div class="space-y-5 p-6">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Nama Shift
                        </label>
                        <input
                            type="text"
                            name="nama_shift"
                            x-model="editData.nama_shift"
                            maxlength="255"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Jam Mulai
                            </label>
                            <input
                                type="time"
                                name="jam_mulai"
                                x-model="editData.jam_mulai"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Jam Selesai
                            </label>
                            <input
                                type="time"
                                name="jam_selesai"
                                x-model="editData.jam_selesai"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                        </div>
                    </div>

                    <template x-if="editData.dinas_count > 0">
                        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-xs leading-relaxed text-blue-800">
                            Shift ini sudah digunakan dalam jadwal dinas.
                            Perubahan konfigurasi tidak otomatis mengubah jam pada jadwal yang tersimpan.
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button
                        type="button"
                        @click="showEdit = false"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#2e4674]"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL HAPUS --}}
    <div
        x-show="showDelete"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Hapus Shift"
    >
        <div
            @click.outside="showDelete = false"
            class="w-full max-w-md rounded-2xl bg-white shadow-xl"
        >
            <form
                :action="'{{ url('/admin/shift') }}/' + deleteData.id"
                method="POST"
            >
                @csrf
                @method('DELETE')

                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-900">
                        Hapus Shift?
                    </h3>

                    <p class="mt-3 text-sm leading-relaxed text-slate-600">
                        Kamu akan menghapus shift
                        <span class="font-semibold" x-text="deleteData.nama_shift"></span>.
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button
                        type="button"
                        @click="showDelete = false"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
