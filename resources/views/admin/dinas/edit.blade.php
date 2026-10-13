
@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                Edit Jadwal Dinas
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Perbarui penugasan petugas PJL, lokasi, shift, dan tanggal dinas.
            </p>
        </div>

        <a href="{{ route('admin.dinas.index') }}"
           class="inline-flex items-center justify-center rounded-xl border border-slate-300
                  bg-white px-4 py-2.5 text-sm font-semibold text-slate-700
                  hover:bg-slate-50">
            Kembali
        </a>
    </div>

    {{-- Notifikasi error validasi --}}
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Periksa kembali data yang dimasukkan:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-5">
            <h2 class="text-lg font-bold text-slate-800">
                Informasi Penugasan
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                ID jadwal: {{ $dina->id }}
            </p>
        </div>

        <form action="{{ route('admin.dinas.update', $dina->id) }}"
              method="POST"
              class="space-y-6 p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">

                {{-- Petugas --}}
                <div>
                    <label for="user_id"
                           class="mb-2 block text-sm font-semibold text-slate-700">
                        Petugas PJL <span class="text-red-500">*</span>
                    </label>

                    <select id="user_id" name="user_id" required
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3
                                   text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="">Pilih petugas</option>
                        @foreach ($petugas as $item)
                            <option value="{{ $item->id }}"
                                @selected(old('user_id', $dina->user_id) == $item->id)>
                                {{ $item->name }} — {{ $item->nip }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lokasi JPL --}}
                <div>
                    <label for="jpl_id"
                           class="mb-2 block text-sm font-semibold text-slate-700">
                        Lokasi JPL <span class="text-red-500">*</span>
                    </label>

                    <select id="jpl_id" name="jpl_id" required
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3
                                   text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="">Pilih lokasi JPL</option>
                        @foreach ($jpl as $lokasi)
                            <option value="{{ $lokasi->id }}"
                                @selected(old('jpl_id', $dina->jpl_id) == $lokasi->id)>
                                {{ $lokasi->kode_jpl }} — {{ $lokasi->nama_jpl }}
                            </option>
                        @endforeach
                    </select>
                    @error('jpl_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Shift --}}
                <div>
                    <label for="shift_id"
                           class="mb-2 block text-sm font-semibold text-slate-700">
                        Shift <span class="text-red-500">*</span>
                    </label>

                    <select id="shift_id" name="shift_id" required
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3
                                   text-sm text-slate-700 focus:border-blue-500 focus:outline-none">
                        <option value="">Pilih shift</option>
                        @foreach ($shifts as $shift)
                            <option value="{{ $shift->id }}"
                                @selected(old('shift_id', $dina->shift_id) == $shift->id)>
                                {{ $shift->nama_shift }}
                                ({{ substr($shift->jam_mulai, 0, 5) }}
                                – {{ substr($shift->jam_selesai, 0, 5) }})
                            </option>
                        @endforeach
                    </select>
                    @error('shift_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tanggal --}}
                <div>
                    <label for="tanggal_dinas"
                           class="mb-2 block text-sm font-semibold text-slate-700">
                        Tanggal Dinas <span class="text-red-500">*</span>
                    </label>

                    <input type="date"
                           id="tanggal_dinas"
                           name="tanggal_dinas"
                           required
                           value="{{ old('tanggal_dinas', $dina->tanggal_dinas?->format('Y-m-d')) }}"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3
                                  text-sm focus:border-blue-500 focus:outline-none">
                    @error('tanggal_dinas')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Pengganti --}}
                <div class="rounded-xl border border-slate-200 p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox"
                               id="is_pengganti"
                               name="is_pengganti"
                               value="1"
                               @checked(old('is_pengganti', $dina->is_pengganti))
                               class="mt-1 rounded border-slate-300 text-blue-600">

                        <span>
                            <span class="block text-sm font-semibold text-slate-800">
                                Petugas pengganti
                            </span>
                            <span class="mt-1 block text-sm text-slate-500">
                                Tandai jika petugas menggantikan petugas lain.
                            </span>
                        </span>
                    </label>
                </div>

                {{-- Petugas yang digantikan --}}
                <div id="pengganti-wrapper"
                     class="{{ old('is_pengganti', $dina->is_pengganti) ? '' : 'hidden' }}">
                    <label for="user_digantikan_id"
                           class="mb-2 block text-sm font-semibold text-slate-700">
                        Petugas yang Digantikan
                    </label>

                    <select id="user_digantikan_id"
                            name="user_digantikan_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3
                                   text-sm focus:border-blue-500 focus:outline-none">
                        <option value="">Pilih petugas</option>
                        @foreach ($petugas as $item)
                            <option value="{{ $item->id }}"
                                @selected(old('user_digantikan_id', $dina->user_digantikan_id) == $item->id)>
                                {{ $item->name }} — {{ $item->nip }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_digantikan_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                <p>
                    <strong>Waktu dinas:</strong>
                    {{ $dina->jam_mulai?->format('d/m/Y H:i') ?? '-' }}
                    sampai
                    {{ $dina->jam_selesai?->format('d/m/Y H:i') ?? '-' }}
                </p>
                <p class="mt-1">
                    Jam mulai dan selesai akan dihitung kembali berdasarkan shift
                    dan tanggal yang dipilih.
                </p>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.dinas.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-slate-300
                          px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Batal
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl
                               bg-[#1d2d50] px-5 py-3 text-sm font-semibold text-white
                               hover:bg-[#15223e]">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('is_pengganti');
    const wrapper = document.getElementById('pengganti-wrapper');
    const select = document.getElementById('user_digantikan_id');

    function togglePengganti() {
        wrapper.classList.toggle('hidden', !checkbox.checked);
        select.required = checkbox.checked;

        if (!checkbox.checked) {
            select.value = '';
        }
    }

    checkbox.addEventListener('change', togglePengganti);
    togglePengganti();
});
</script>
@endsection
