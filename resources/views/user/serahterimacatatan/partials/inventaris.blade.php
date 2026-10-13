<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1d2d50] text-sm font-bold text-white">5</span>
        <h2 class="font-bold text-[#1d2d50]">Alat pendukung dan inventaris PJL</h2>
    </div>

    @if ($inventaris->isEmpty())
        <p class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
            Belum ada inventaris aktif yang terdaftar untuk JPL dinas ini.
        </p>
    @else
        <div class="grid gap-3 lg:grid-cols-2">
            @foreach ($inventaris as $item)
                @php
                    $hasil = $realisasi->get($item->id);
                    $jumlahSebelumnya = $hasil?->jumlah_aktual
                        ?? $item->jumlah_aktual_terakhir
                        ?? $item->jumlah;
                    $kondisiSebelumnya = $hasil?->kondisi
                        ?? $item->kondisi_terakhir
                        ?? 'baik';
                @endphp

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-800">
                                {{ $item->kode_barang ?: 'INV-'.$item->id }}
                                · {{ $item->nama_barang }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Jumlah master: {{ $item->jumlah ?? 0 }}
                                @if ($item->status_tinjauan !== 'normal')
                                    <span class="ml-1 font-semibold text-red-600">
                                        · {{ str_replace('_', ' ', ucfirst($item->status_tinjauan)) }}
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <form method="POST"
                          action="{{ route('user.serah-terima-dinas.inventaris.store') }}"
                          class="grid grid-cols-2 gap-3">
                        @csrf

                        <input type="hidden" name="inventaris_id" value="{{ $item->id }}">

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">
                                Jumlah aktual
                            </label>
                            <input type="number" name="jumlah_aktual"
                                min="0" required
                                value="{{ $jumlahSebelumnya }}"
                                class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm">
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">
                                Kondisi
                            </label>
                            <select name="kondisi" required
                                class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm">
                                @foreach ([
                                    'baik' => 'Baik',
                                    'perlu_perbaikan' => 'Perlu perbaikan',
                                    'rusak' => 'Rusak',
                                    'hilang' => 'Hilang',
                                ] as $value => $text)
                                    <option value="{{ $value }}"
                                        @selected($kondisiSebelumnya === $value)>
                                        {{ $text }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-slate-600">
                                Catatan pemeriksaan
                            </label>
                            <input type="text" name="catatan"
                                value="{{ old('catatan', $hasil?->catatan) }}"
                                placeholder="Contoh: satu unit rusak atau jumlah kurang"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        </div>

                        <div class="col-span-2 flex items-center justify-between gap-2">
                            <span class="text-xs text-slate-500">
                                @if ($item->status_tinjauan !== 'normal')
                                    Masalah menunggu tinjauan admin
                                @else
                                    Diperiksa oleh petugas yang login
                                @endif
                            </span>
                            <button type="submit"
                                class="rounded-lg bg-[#1d2d50] px-3 py-2 text-xs font-bold text-white hover:bg-slate-700">
                                Simpan pemeriksaan
                            </button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</section>
