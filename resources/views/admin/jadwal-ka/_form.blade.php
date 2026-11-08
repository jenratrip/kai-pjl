@csrf

@if (isset($jadwalKa))
    @method('PUT')
@endif

<div class="space-y-5 p-6">

    {{-- Kereta Api --}}
    <div>
        <label for="kereta_api_id" class="mb-2 block text-sm font-semibold text-gray-700">
            Kereta Api <span class="text-red-500">*</span>
        </label>

        <select id="kereta_api_id"
                name="kereta_api_id"
                required
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10 @error('kereta_api_id') border-red-500 @enderror">
            <option value="">Pilih Kereta Api</option>

            @foreach ($keretaApi as $ka)
                <option value="{{ $ka->id }}"
                    @selected((string) old('kereta_api_id', $jadwalKa->kereta_api_id ?? '') === (string) $ka->id)>
                    {{ $ka->nomor_ka }}
                    @if ($ka->nama_ka)
                        — {{ $ka->nama_ka }}
                    @endif
                    @if ($ka->jenis)
                        ({{ ucfirst($ka->jenis) }})
                    @endif
                </option>
            @endforeach
        </select>

        @error('kereta_api_id')
            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    {{-- JPL --}}
    <div>
        <label for="jpl_id" class="mb-2 block text-sm font-semibold text-gray-700">
            JPL <span class="text-red-500">*</span>
        </label>

        <select id="jpl_id"
                name="jpl_id"
                required
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10 @error('jpl_id') border-red-500 @enderror">
            <option value="">Pilih JPL</option>

            @foreach ($jpls as $jpl)
                <option value="{{ $jpl->id }}"
                    @selected((string) old('jpl_id', $jadwalKa->jpl_id ?? '') === (string) $jpl->id)>
                    {{ $jpl->kode_jpl }} — {{ $jpl->nama_jpl }}
                </option>
            @endforeach
        </select>

        @error('jpl_id')
            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    {{-- Jam Lewat --}}
    <div>
        <label for="jam_lewat" class="mb-2 block text-sm font-semibold text-gray-700">
            Jam Lewat <span class="text-red-500">*</span>
        </label>

        <input type="time"
               id="jam_lewat"
               name="jam_lewat"
               value="{{ old('jam_lewat', isset($jadwalKa) && $jadwalKa->jam_lewat ? substr((string) $jadwalKa->jam_lewat, 0, 5) : '') }}"
               required
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10 @error('jam_lewat') border-red-500 @enderror">

        @error('jam_lewat')
            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>


    {{-- Hari Berlaku --}}
    @php
        $daftarHari = [
            'Senin', 'Selasa', 'Rabu', 'Kamis',
            'Jumat', 'Sabtu', 'Minggu'
        ];

        $hariTerpilih = old(
            'hari_berlaku',
            isset($jadwalKa) && is_array($jadwalKa->hari_berlaku)
                ? $jadwalKa->hari_berlaku
                : []
        );

        if (!is_array($hariTerpilih)) {
            $hariTerpilih = [];
        }
    @endphp

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Hari Berlaku
        </label>

        <p class="text-xs text-gray-500 mb-3">
            Pilih satu atau beberapa hari operasional KA.
        </p>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach ($daftarHari as $hari)
                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="hari_berlaku[]"
                        value="{{ $hari }}"
                        @checked(in_array($hari, $hariTerpilih, true))
                        class="rounded border-gray-300 text-blue-600"
                    >
                    <span class="text-sm text-gray-700">{{ $hari }}</span>
                </label>
            @endforeach
        </div>

        @error('hari_berlaku')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror

        @error('hari_berlaku.*')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>


    {{-- Tanggal Berlaku --}}
    <div>
        <label for="tanggal_berlaku" class="mb-2 block text-sm font-semibold text-gray-700">
            Tanggal Berlaku
        </label>

        <input type="date"
               id="tanggal_berlaku"
               name="tanggal_berlaku"
               value="{{ old('tanggal_berlaku', isset($jadwalKa) && $jadwalKa->tanggal_berlaku ? $jadwalKa->tanggal_berlaku->format('Y-m-d') : '') }}"
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10 @error('tanggal_berlaku') border-red-500 @enderror">

        @error('tanggal_berlaku')
            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
        @enderror

        <p class="mt-1.5 text-xs text-gray-400">
            Kosongkan jika jadwal tidak dibatasi tanggal tertentu.
        </p>
    </div>

    {{-- Keterangan --}}
    <div>
        <label for="keterangan" class="mb-2 block text-sm font-semibold text-gray-700">
            Keterangan
        </label>

        <textarea id="keterangan"
                  name="keterangan"
                  rows="3"
                  placeholder="Keterangan tambahan jika diperlukan..."
                  class="w-full resize-y rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10 @error('keterangan') border-red-500 @enderror">{{ old('keterangan', $jadwalKa->keterangan ?? '') }}</textarea>

        @error('keterangan')
            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    {{-- Status --}}
    <div>
        <label for="is_active" class="mb-2 block text-sm font-semibold text-gray-700">
            Status Jadwal <span class="text-red-500">*</span>
        </label>

        <select id="is_active"
                name="is_active"
                required
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10 @error('is_active') border-red-500 @enderror">
            <option value="1" @selected((string) old('is_active', isset($jadwalKa) ? (int) $jadwalKa->is_active : 1) === '1')>
                Aktif
            </option>
            <option value="0" @selected((string) old('is_active', isset($jadwalKa) ? (int) $jadwalKa->is_active : 1) === '0')>
                Tidak Aktif
            </option>
        </select>

        @error('is_active')
            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

</div>

<div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
    <a href="{{ route('admin.jadwal-ka.index') }}"
       class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
        Batal
    </a>

    <button type="submit"
            class="rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#16233f]">
        {{ isset($jadwalKa) ? 'Simpan Perubahan' : 'Simpan Jadwal' }}
    </button>
</div>