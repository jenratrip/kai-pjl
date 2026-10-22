<section id="inventaris"
         class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
         data-url="{{ route('user.serah-terima-dinas.inventaris.store') }}"
         data-csrf="{{ csrf_token() }}">

    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#1d2d50] font-bold text-white">5</span>
        <h2 class="text-lg font-bold text-[#1d2d50]">Alat pendukung dan inventaris PJL</h2>
    </div>

    @forelse ($inventaris->sortBy(fn ($i) => $i->kode_barang ?: 'ZZZ' . $i->id)->values() as $item)
        @php
            $hasil = $realisasi->get($item->id);
            $sudahDiperiksa = (bool) $hasil;

            $jumlahMaster = (int) $item->jumlah;
            $jumlah = $hasil?->jumlah_aktual ?? $jumlahMaster;
            $kondisiTersimpan = $hasil?->kondisi;

            // Lengkap hanya jika sudah diperiksa, kondisi baik, dan jumlah sesuai.
            $lengkap = $sudahDiperiksa
                && $kondisiTersimpan === 'baik'
                && (int) $jumlah === $jumlahMaster;
            $tidakLengkap = $sudahDiperiksa && !$lengkap;

            $kondisiDetail = in_array($kondisiTersimpan, ['perlu_perbaikan', 'rusak', 'hilang'], true)
                ? $kondisiTersimpan
                : 'perlu_perbaikan';

            $catatan = $hasil?->catatan;
            $label = $loop->index < 26 ? chr(97 + $loop->index) : $loop->iteration;
        @endphp

        @if ($loop->first)
            <p class="mb-3 rounded-lg bg-blue-50 p-3 text-xs text-blue-900 print:hidden">
                Pilih <strong>Lengkap</strong> atau <strong>Tidak lengkap</strong> pada setiap barang.
                Hasilnya <strong>tersimpan otomatis</strong>, tidak perlu menekan tombol simpan.
                <span class="ml-1 font-semibold" data-progress></span>
            </p>
            <div class="grid gap-3 lg:grid-cols-2">
        @endif

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3"
             data-item
             data-inventaris-id="{{ $item->id }}"
             data-jumlah-master="{{ $jumlahMaster }}"
             data-state="{{ $sudahDiperiksa ? 'sudah' : 'belum' }}">

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
                    Lengkap
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
                <label class="flex items-center gap-2 text-xs text-slate-600">
                    Kondisi
                    <select data-kondisi
                            aria-label="Kondisi {{ $item->nama_barang }}"
                            class="h-9 rounded border border-slate-300 px-2 text-sm text-slate-800">
                        <option value="perlu_perbaikan" @selected($kondisiDetail === 'perlu_perbaikan')>Perlu perbaikan</option>
                        <option value="rusak" @selected($kondisiDetail === 'rusak')>Rusak</option>
                        <option value="hilang" @selected($kondisiDetail === 'hilang')>Hilang</option>
                    </select>
                </label>
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

    <script>
        (function () {
            const section = document.getElementById('inventaris');

            if (!section || section.dataset.bound === '1') {
                return;
            }

            section.dataset.bound = '1';

            const url = section.dataset.url;
            const csrf = section.dataset.csrf;
            const cards = Array.from(section.querySelectorAll('[data-item]'));
            const progress = section.querySelector('[data-progress]');

            const WARNA = {
                netral: ['text-slate-500'],
                sukses: ['text-green-700'],
                error: ['text-red-600'],
                proses: ['text-amber-700'],
            };

            function setStatus(card, teks, jenis) {
                const el = card.querySelector('[data-status]');
                el.textContent = teks;
                Object.values(WARNA).flat().forEach((c) => el.classList.remove(c));
                WARNA[jenis].forEach((c) => el.classList.add(c));
            }

            function perbaruiProgress() {
                if (!progress) {
                    return;
                }

                const sudah = cards.filter((c) => c.dataset.state === 'sudah').length;
                progress.textContent = '(' + sudah + ' dari ' + cards.length + ' sudah diperiksa)';
            }

            function ambilPesanError(data, status) {
                if (data && data.errors) {
                    const pertama = Object.values(data.errors)[0];
                    if (pertama && pertama[0]) {
                        return pertama[0];
                    }
                }

                if (data && data.message) {
                    return data.message;
                }

                return 'Gagal menyimpan (kode ' + status + '). Coba lagi.';
            }

            async function simpan(card) {
                const lengkapEl = card.querySelector('[data-lengkap]');
                const tidakEl = card.querySelector('[data-tidak]');

                // Belum memilih lengkap / tidak lengkap: belum ada yang disimpan.
                if (!lengkapEl.checked && !tidakEl.checked) {
                    return;
                }

                const master = parseInt(card.dataset.jumlahMaster, 10) || 0;
                const jumlahEl = card.querySelector('[data-jumlah]');
                const adaLengkap = lengkapEl.checked;

                let jumlah = adaLengkap ? master : parseInt(jumlahEl.value, 10);

                if (Number.isNaN(jumlah) || jumlah < 0) {
                    setStatus(card, 'Isi jumlah yang valid (0 atau lebih).', 'error');
                    return;
                }

                const kondisi = adaLengkap
                    ? 'baik'
                    : card.querySelector('[data-kondisi]').value;

                const seq = (card._seq = (card._seq || 0) + 1);

                setStatus(card, 'Menyimpan...', 'proses');

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            inventaris_id: card.dataset.inventarisId,
                            kondisi: kondisi,
                            jumlah_aktual: jumlah,
                            catatan: card.querySelector('[data-catatan]').value,
                        }),
                    });

                    const data = await res.json().catch(() => null);

                    // Abaikan respons lama jika sudah ada permintaan yang lebih baru.
                    if (seq !== card._seq) {
                        return;
                    }

                    if (!res.ok || !data || data.ok === false) {
                        setStatus(card, ambilPesanError(data, res.status), 'error');
                        return;
                    }

                    card.dataset.state = 'sudah';

                    if (data.ada_masalah) {
                        const tinjauan = card.querySelector('[data-tinjauan]');
                        tinjauan.textContent = '· Perlu tinjauan (menunggu tinjauan admin)';
                        tinjauan.classList.remove('hidden');
                    }

                    setStatus(
                        card,
                        data.ada_masalah
                            ? 'Tersimpan. Menunggu tinjauan admin.'
                            : 'Tersimpan',
                        'sukses'
                    );

                    perbaruiProgress();
                } catch (e) {
                    if (seq === card._seq) {
                        setStatus(card, 'Koneksi bermasalah. Perubahan belum tersimpan.', 'error');
                    }
                }
            }

            function tampilkanDetail(card, tampil) {
                card.querySelector('[data-detail]').classList.toggle('hidden', !tampil);
            }

            cards.forEach((card) => {
                const master = parseInt(card.dataset.jumlahMaster, 10) || 0;
                const lengkapEl = card.querySelector('[data-lengkap]');
                const tidakEl = card.querySelector('[data-tidak]');
                const jumlahEl = card.querySelector('[data-jumlah]');
                const kondisiEl = card.querySelector('[data-kondisi]');
                const catatanEl = card.querySelector('[data-catatan]');

                lengkapEl.addEventListener('change', () => {
                    if (!lengkapEl.checked) {
                        return;
                    }

                    jumlahEl.value = master;
                    tampilkanDetail(card, false);
                    simpan(card);
                });

                // Tidak lengkap: tampilkan pilihan kondisi, langsung simpan.
                tidakEl.addEventListener('change', () => {
                    if (!tidakEl.checked) {
                        return;
                    }

                    tampilkanDetail(card, true);
                    simpan(card);
                });

                // Jumlah berbeda dari seharusnya otomatis dianggap tidak lengkap.
                let timerJumlah;
                jumlahEl.addEventListener('input', () => {
                    const nilai = parseInt(jumlahEl.value, 10);

                    if (!Number.isNaN(nilai) && nilai !== master) {
                        tidakEl.checked = true;
                        tampilkanDetail(card, true);
                    }

                    clearTimeout(timerJumlah);
                    timerJumlah = setTimeout(() => simpan(card), 700);
                });

                kondisiEl.addEventListener('change', () => simpan(card));

                let timerCatatan;
                catatanEl.addEventListener('input', () => {
                    clearTimeout(timerCatatan);
                    timerCatatan = setTimeout(() => simpan(card), 700);
                });
            });

            perbaruiProgress();
        })();
    </script>
</section>