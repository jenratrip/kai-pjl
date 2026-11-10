<section id="inventaris"
         class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
         data-serah-terima-inventaris
         data-url="{{ route('user.serah-terima-dinas.inventaris.store') }}"
         data-csrf="{{ csrf_token() }}">

    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#1d2d50] font-bold text-white">5</span>
        <h2 class="text-lg font-bold text-[#1d2d50]">Alat pendukung dan inventaris PJL</h2>
    </div>

    @forelse ($inventaris->sortBy(fn ($i) => $i->kode_barang ?: 'ZZZ' . $i->id)->values() as $item)
        @php
            $hasil = $realisasi->get($item->id);
            $snapshotItem = collect(data_get($serahTerima?->ringkasan, 'inventaris', []))
                ->firstWhere('inventaris_id', (int) $item->id);
            $gunakanSnapshot = $modeLihat && is_array($snapshotItem);
            $jumlahMaster = (int) $item->jumlah;
            $jumlah = $gunakanSnapshot
                ? (int) ($snapshotItem['jumlah_aktual'] ?? $jumlahMaster)
                : (int) ($hasil?->jumlah_aktual ?? $jumlahMaster);
            $kondisiTersimpan = $gunakanSnapshot
                ? ($snapshotItem['kondisi'] ?? null)
                : $hasil?->kondisi;
            $sudahDiperiksa = $gunakanSnapshot
                ? (bool) (($snapshotItem['status'] ?? null) || $kondisiTersimpan)
                : (bool) $hasil;
            $lengkap = $sudahDiperiksa
                && $kondisiTersimpan === 'baik'
                && $jumlah === $jumlahMaster;
            $tidakLengkap = $sudahDiperiksa && ! $lengkap;
            $kondisiDetail = in_array($kondisiTersimpan, ['perlu_perbaikan', 'rusak', 'hilang'], true)
                ? $kondisiTersimpan
                : '';
            $catatan = $gunakanSnapshot
                ? ($snapshotItem['catatan'] ?? '')
                : ($hasil?->catatan ?? '');
            $buktiFotoPath = $gunakanSnapshot
                ? ($snapshotItem['bukti_foto_path'] ?? null)
                : $hasil?->bukti_foto_path;
            $buktiFotoUrl = null;

            if ($buktiFotoPath && $gunakanSnapshot && $serahTerima) {
                $buktiFotoUrl = route('user.serah-terima-dinas.inventaris.foto-bukti', [
                    $serahTerima,
                    $item->id,
                ]);
            } elseif ($buktiFotoPath && ! $modeLihat) {
                $buktiFotoUrl = route('user.inventaris.foto-bukti', $item);
            }

            $label = $loop->index < 26 ? chr(97 + $loop->index) : $loop->iteration;
        @endphp

        @if ($loop->first)
            <p class="mb-3 rounded-lg bg-blue-50 p-3 text-xs text-blue-900 print:hidden">
                Pilih <strong>Lengkap</strong> untuk pemeriksaan normal. Pilihan selain Baik akan meminta keterangan dan bukti sesuai kondisi barang.
                <span class="ml-1 font-semibold" data-progress></span>
            </p>
            <div class="grid gap-3 lg:grid-cols-2">
        @endif

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3"
             data-item
             data-item-name="{{ $item->nama_barang }}"
             data-inventaris-id="{{ $item->id }}"
             data-jumlah-master="{{ $jumlahMaster }}"
             data-state="{{ $sudahDiperiksa ? 'sudah' : 'belum' }}"
             data-saved-condition="{{ $sudahDiperiksa ? $kondisiTersimpan : '' }}"
             data-saved-quantity="{{ $jumlah }}"
             data-saved-note="{{ $catatan }}"
             data-saved-photo-url="{{ $buktiFotoUrl }}">

            <p class="text-sm font-semibold text-slate-800">
                {{ $label }}. {{ $item->nama_barang }}
                <span class="text-xs text-red-600 {{ ($item->status_tinjauan ?? 'normal') !== 'normal' ? '' : 'hidden' }}" data-tinjauan>
                    @if (($item->status_tinjauan ?? 'normal') !== 'normal')
                        · {{ str_replace('_', ' ', ucfirst($item->status_tinjauan)) }} (menunggu tinjauan admin)
                    @endif
                </span>
            </p>

            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm">
                <input type="number" min="0" value="{{ $jumlah }}" data-jumlah
                       aria-label="Jumlah {{ $item->nama_barang }}"
                       class="h-9 w-16 rounded border border-slate-300 text-center">
                <span class="-ml-1 text-xs text-slate-500">Buah</span>

                <label class="flex cursor-pointer items-center gap-1.5">
                    <input type="radio" name="kondisi_{{ $item->id }}" value="lengkap" data-lengkap
                           @checked($lengkap)>
                    Lengkap / Baik
                </label>
                <label class="flex cursor-pointer items-center gap-1.5">
                    <input type="radio" name="kondisi_{{ $item->id }}" value="tidak" data-tidak
                           @checked($tidakLengkap)>
                    Tidak lengkap
                </label>

                <input type="text" value="{{ $catatan }}" placeholder="Catatan" data-catatan
                       aria-label="Catatan {{ $item->nama_barang }}"
                       class="h-9 min-w-[8rem] flex-1 rounded border border-slate-300 px-2.5">
            </div>

            <div class="mt-2 {{ $tidakLengkap ? '' : 'hidden' }}" data-detail>
                <p class="text-xs text-slate-600" data-condition-label>
                    Kondisi tersimpan: {{ $kondisiTersimpan ? str_replace('_', ' ', ucfirst($kondisiTersimpan)) : 'belum dipilih' }}
                </p>
                <button type="button" data-edit-report
                        class="mt-1 text-xs font-semibold text-[#1d2d50] underline underline-offset-2">
                    {{ $sudahDiperiksa ? 'Ubah laporan kondisi' : 'Lengkapi laporan kondisi' }}
                </button>
            </div>

            <div class="mt-3 {{ $buktiFotoUrl ? '' : 'hidden' }}" data-saved-evidence>
                <p class="mb-1 text-xs font-semibold text-slate-600">Bukti foto tersimpan</p>
                <img data-saved-evidence-image src="{{ $buktiFotoUrl }}"
                     alt="Bukti foto {{ $item->nama_barang }}"
                     class="max-h-40 rounded-lg border border-slate-200 object-contain">
            </div>

            <p class="mt-2 text-xs print:hidden {{ $sudahDiperiksa ? 'text-green-700' : 'text-slate-500' }}"
               data-status role="status" aria-live="polite">
                {{ $sudahDiperiksa ? 'Tersimpan' : 'Belum diperiksa' }}
            </p>
        </div>

        @if ($loop->last)
            </div>
        @endif
    @empty
        <p class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
            Belum ada inventaris aktif yang terdaftar untuk JPL dinas ini.
        </p>
    @endforelse

    <dialog data-report-dialog
            aria-labelledby="report-dialog-title"
            class="fixed left-1/2 top-1/2 m-0 max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-xl p-0 shadow-xl backdrop:bg-slate-900/50">
        <div class="p-5 sm:p-6">
            <h3 id="report-dialog-title" class="text-lg font-bold text-[#1d2d50]" data-report-title>
                Laporan kondisi barang
            </h3>
            <p class="mt-1 text-sm text-slate-600" data-report-item></p>
            <p class="mt-2 text-sm text-slate-600" data-report-help></p>

            <label class="mt-4 block text-sm font-semibold text-slate-700" for="report-condition">
                Kondisi barang
            </label>
            <select id="report-condition" data-report-condition
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="perlu_perbaikan">Perlu Perbaikan</option>
                <option value="rusak">Rusak</option>
                <option value="hilang">Hilang</option>
            </select>

            <label class="mt-4 block text-sm font-semibold text-slate-700" for="report-description">
                Keterangan kondisi
            </label>
            <textarea id="report-description" data-report-description maxlength="2000" rows="3"
                      class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                      aria-describedby="report-description-error"></textarea>
            <p class="mt-1 text-xs text-red-600" data-report-description-error role="alert" hidden></p>

            <label class="mt-4 block text-sm font-semibold text-slate-700" for="report-photo">
                Foto bukti <span data-report-photo-requirement></span>
            </label>
            <input id="report-photo" data-report-photo type="file"
                   accept="image/jpeg,image/png,image/webp"
                   class="mt-1 block w-full rounded-lg border border-slate-300 text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2">
            <p class="mt-1 text-xs text-slate-500">Format JPEG, PNG, atau WebP; ukuran maksimal 5 MB.</p>
            <p class="mt-1 text-xs text-red-600" data-report-photo-error role="alert" hidden></p>

            <div class="mt-3" data-report-existing-photo-wrap hidden>
                <p class="text-xs font-semibold text-slate-600">Bukti foto yang tersimpan</p>
                <img data-report-existing-photo alt="Bukti foto yang tersimpan"
                     class="mt-1 max-h-40 rounded-lg border border-slate-200 object-contain">
            </div>
            <div class="mt-3" data-report-preview-wrap hidden>
                <p class="text-xs font-semibold text-slate-600">Preview foto baru</p>
                <img data-report-preview alt="Preview foto bukti"
                     class="mt-1 max-h-48 rounded-lg border border-slate-200 object-contain">
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" data-report-cancel
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                    Batal
                </button>
                <button type="button" data-report-confirm
                        class="rounded-lg bg-[#1d2d50] px-4 py-2 text-sm font-semibold text-white">
                    Simpan Laporan
                </button>
            </div>
        </div>
    </dialog>
</section>
