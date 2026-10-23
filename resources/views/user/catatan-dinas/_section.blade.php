{{-- resources/views/user/catatan-dinas/_section.blade.php --}}
{{-- Variabel: $catatan, $daftarDinas, $redirectTo (opsional) --}}

<div class="cd-root">

    <section class="catatan-dinas-section" id="catatanDinasSection">
        <div class="page-header">
            <div class="page-heading">
                <h1 class="page-title">Catatan Dinas</h1>
                <p class="page-description">
                    Kelola dan lihat catatan selama pelaksanaan dinas.
                </p>
            </div>

            <button class="add-button" type="button" onclick="openAddModal()">
                <span class="add-icon" aria-hidden="true">+</span>
                <span>Tambah Catatan</span>
            </button>
        </div>

        {{-- Notifikasi sukses --}}
        @if (session('success'))
            <div class="flash-success" role="status">
                {{ session('success') }}
            </div>
        @endif

        <div class="table-card" aria-label="Daftar catatan dinas">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">PJL</th>
                            <th scope="col">Shift</th>
                            <th scope="col">Catatan</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="catatanTable">
                        @forelse ($catatan as $item)
                            @php
                                $tanggalMentahCatatan = $item->dinas?->getRawOriginal('tanggal_dinas');
                                $tanggalCatatan = $tanggalMentahCatatan
                                    ? \Carbon\Carbon::parse($tanggalMentahCatatan)->locale('id')->translatedFormat('d M Y')
                                    : '-';
                                $namaPjl = $item->dinas?->user?->name ?? $item->user?->name ?? '-';
                                $namaShift = $item->dinas?->shift?->nama_shift ?? '-';
                            @endphp

                            <tr>
                                <td data-label="No">{{ $loop->iteration }}</td>
                                <td class="date" data-label="Tanggal">{{ $tanggalCatatan }}</td>
                                <td class="pjl" data-label="PJL">{{ $namaPjl }}</td>
                                <td data-label="Shift">
                                    <span class="shift-pill">{{ $namaShift }}</span>
                                </td>
                                <td class="note" data-label="Catatan" title="{{ $item->isi_catatan }}">
                                    <div class="note-text">{{ $item->isi_catatan }}</div>
                                </td>
                                <td data-label="Aksi">
                                    <button class="view-button" type="button"
                                        aria-label="Lihat detail catatan tanggal {{ $tanggalCatatan }}"
                                        onclick="viewDetail(
                                            @js($tanggalCatatan),
                                            @js($namaPjl),
                                            @js($namaShift),
                                            @js($item->isi_catatan)
                                        )">
                                        Lihat
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <span class="empty-icon" aria-hidden="true">▤</span>
                                    <strong>Belum ada catatan dinas.</strong><br>
                                    Klik tombol Tambah Catatan untuk membuat catatan baru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div class="showing">Menampilkan {{ $catatan->count() }} catatan dinas</div>
            </div>
        </div>
    </section>

    {{-- MODAL TAMBAH CATATAN --}}
    <div class="modal-overlay" id="addModal" onclick="closeOutside(event)" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true"
             aria-labelledby="addModalTitle" onclick="event.stopPropagation()">

            <div class="modal-header">
                <h2 class="modal-title" id="addModalTitle">Tambah Catatan Dinas</h2>
                <button class="close" type="button" aria-label="Tutup modal" onclick="closeAddModal()">×</button>
            </div>

            <form id="catatanForm" action="{{ route('user.catatan-dinas.store') }}" method="POST">
                @csrf
                {{-- Supaya setelah simpan kembali ke halaman asal --}}
                <input type="hidden" name="redirect_to" value="{{ $redirectTo ?? '' }}">

                <div class="form-group">
                    <label class="form-label" for="catatanTanggal">Tanggal</label>
                    <input type="date" class="form-input" id="catatanTanggal"
                           name="tanggal" value="{{ old('tanggal') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="catatanPjl">PJL</label>
                    <input type="text" class="form-input" id="catatanPjl"
                           value="{{ auth()->user()->name ?? '' }}" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label" for="catatanShift">Shift</label>
                    <select class="form-select" id="catatanShift" name="shift_id" required>
                        <option value="">Pilih tanggal terlebih dahulu</option>

                        @foreach ($daftarDinas as $dinas)
                            @php
                                $tanggalMentah = $dinas->getRawOriginal('tanggal_dinas');
                                $tanggalDinas = $tanggalMentah ? substr(trim((string) $tanggalMentah), 0, 10) : '';
                                $namaShiftOption = $dinas->shift?->nama_shift;
                            @endphp

                            @if ($dinas->shift && $tanggalDinas !== '')
                                <option value="{{ $dinas->shift_id }}"
                                        data-tanggal="{{ $tanggalDinas }}"
                                        @selected((string) old('shift_id') === (string) $dinas->shift_id
                                                  && old('tanggal') === $tanggalDinas)>
                                    {{ $namaShiftOption }} — {{ $tanggalDinas }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <div class="form-help" id="shiftHelp" aria-live="polite">
                        Pilih tanggal dinas untuk menampilkan shift yang tersedia.
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="catatan">Catatan</label>
                    <textarea class="form-textarea" id="catatan" name="catatan"
                              maxlength="5000" required
                              placeholder="Tuliskan catatan selama pelaksanaan dinas...">{{ old('catatan') }}</textarea>
                    <div class="form-help">Maksimal 5.000 karakter.</div>
                </div>

                @if ($errors->any())
                    <div class="form-error" role="alert">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="modal-footer">
                    <button class="modal-button cancel" type="button" onclick="closeAddModal()">Batal</button>
                    <button class="modal-button save" type="submit" id="catatanSubmit">Simpan Catatan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DETAIL CATATAN --}}
    <div class="modal-overlay" id="detailModal" onclick="closeDetailOutside(event)" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true"
             aria-labelledby="detailModalTitle" onclick="event.stopPropagation()">

            <div class="modal-header">
                <h2 class="modal-title" id="detailModalTitle">Detail Catatan Dinas</h2>
                <button class="close" type="button" aria-label="Tutup modal" onclick="closeDetailModal()">×</button>
            </div>

            <div class="detail-row"><div class="detail-label">Tanggal</div><div class="detail-value" id="detailTanggal"></div></div>
            <div class="detail-row"><div class="detail-label">PJL</div><div class="detail-value" id="detailPjl"></div></div>
            <div class="detail-row"><div class="detail-label">Shift</div><div class="detail-value" id="detailShift"></div></div>
            <div class="detail-row"><div class="detail-label">Catatan</div><div class="detail-value" id="detailCatatan"></div></div>

            <div class="modal-footer">
                <button class="modal-button cancel" type="button" onclick="closeDetailModal()">Tutup</button>
            </div>
        </div>
    </div>

</div>