@php
    $petugas = auth()->user();

    $namaPetugas = $petugas->name ?? 'Petugas';

    // Dinas aktif: pakai yang dikirim layout ($dinasHeader) supaya tidak
    // query dua kali. Jika header dipakai tanpa layout, cari sendiri.
    $dinasSaatIni = $dinasHeader
        ?? \App\Models\Dinas::with(['jpl', 'shift'])
            ->where('user_id', $petugas?->id)
            ->where('status', 'berlangsung')
            ->latest('id')
            ->first();

    $nomorPjl = $dinasSaatIni?->jpl?->nomor_jpl
        ?? $petugas?->nomor_jpl
        ?? $petugas?->nip
        ?? '-';

    // Jenis petugas mengikuti pilihan saat login (dinas.is_pengganti).
    // Urutan: nilai dari layout -> dinas aktif -> data akun.
    if (isset($jenisPetugas)) {
        $jenisFinal = $jenisPetugas;
    } elseif ($dinasSaatIni) {
        $jenisFinal = $dinasSaatIni->is_pengganti ? 'wakilan' : 'reguler';
    } else {
        $jenisFinal = $petugas?->status_petugas
            ?? $petugas?->jenis_petugas
            ?? 'reguler';
    }

    $statusPetugas = strtolower((string) $jenisFinal) === 'wakilan'
        ? 'WAKILAN'
        : 'REGULER';
@endphp

<header class="op-header">
    <div class="op-header-left">
        <button
            type="button"
            class="op-menu-button"
            id="opMenuButton"
            aria-label="Buka atau tutup sidebar"
            aria-controls="opSidebar"
            aria-expanded="false"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <a href="{{ url('/user/dashboard') }}" class="op-brand">
            <div class="op-brand-logo">
                <img
                    src="{{ asset('images/logo-kai.jpg') }}"
                    alt="Logo KAI"
                >
            </div>

            <div class="op-brand-text">
                <div class="op-brand-title">KAI OPERASIONAL</div>
                <div class="op-brand-subtitle">SISTEM DIGITAL PJL</div>
            </div>
        </a>
    </div>

    <div class="op-header-right">
        <div class="op-user-info">
            <div class="op-user-name">
                {{ $namaPetugas }}
            </div>

            <div class="op-user-details">
                <span class="op-user-duty">
                    {{ $nomorPjl }}
                </span>

                <span class="op-user-status {{ $statusPetugas === 'WAKILAN' ? 'is-wakilan' : 'is-reguler' }}">
                    {{ $statusPetugas }}
                </span>
            </div>
        </div>
    </div>
</header>