
<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1d2d50] text-sm font-bold text-white">6</span>
        <h2 class="font-bold text-[#1d2d50]">Catatan penting dan warta dinas</h2>
    </div>

    <form method="POST"
          action="{{ route('user.serah-terima-dinas.catatan.store') }}"
          class="space-y-3">
        @csrf

        <div class="grid gap-3 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Judul</label>
                <input name="judul" required maxlength="255"
                    value="{{ old('judul') }}"
                    placeholder="Judul catatan"
                    class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Kategori</label>
                <select name="kategori" required
                    class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm">
                    <option value="operasional">Operasional</option>
                    <option value="inventaris">Inventaris</option>
                    <option value="gangguan">Gangguan</option>
                    <option value="instruksi">Instruksi</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700">Isi catatan</label>
            <textarea name="isi_catatan" required rows="4" maxlength="10000"
                placeholder="Catat kondisi operasional, instruksi khusus, dan informasi penting untuk shift berikutnya."
                class="w-full rounded-lg border border-slate-300 bg-slate-50 p-3 text-sm">{{ old('isi_catatan') }}</textarea>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700">Tindak lanjut</label>
            <textarea name="tindak_lanjut" rows="2" maxlength="5000"
                placeholder="Tindak lanjut yang perlu dilakukan"
                class="w-full rounded-lg border border-slate-300 bg-slate-50 p-3 text-sm">{{ old('tindak_lanjut') }}</textarea>
        </div>

        <button type="submit"
            class="rounded-lg border border-[#1d2d50] px-4 py-2 text-sm font-semibold text-[#1d2d50] hover:bg-slate-100">
            Simpan catatan
        </button>
    </form>

    <div class="mt-5 space-y-3">
        <h3 class="font-bold text-slate-700">Catatan pada dinas ini</h3>

        @forelse ($catatan as $item)
            <article class="rounded-lg border border-slate-200 p-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <h4 class="font-semibold text-slate-800">{{ $item->judul }}</h4>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-600">
                        {{ ucfirst($item->status) }}
                    </span>
                </div>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $item->isi_catatan }}</p>
                @if ($item->tindak_lanjut)
                    <p class="mt-2 text-sm text-slate-700">
                        <strong>Tindak lanjut:</strong> {{ $item->tindak_lanjut }}
                    </p>
                @endif
                <p class="mt-2 text-xs text-slate-400">
                    {{ $item->waktu_catatan?->format('d/m/Y H:i') }}
                    · {{ $item->user?->name }}
                </p>

                @if ($item->attachments->isNotEmpty())
                    <div class="mt-2 text-xs text-slate-500">
                        Lampiran: {{ $item->attachments->count() }} file
                    </div>
                @endif
            </article>
        @empty
            <p class="text-sm text-slate-500">Belum ada catatan pada dinas ini.</p>
        @endforelse
    </div>
</section>
