@php
    $petugas = auth()->user();

    $namaPetugas = $petugas->name ?? 'Petugas';

    // Layout hanya menggunakan konteks Dinas yang disediakan oleh halaman.
    $dinasSaatIni = $dinasHeader ?? ($dinasAktif ?? null);

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
            :aria-expanded="sidebarOpen.toString()"
            @click="sidebarOpen = !sidebarOpen"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <a href="{{ route('user.realisasi-ka') }}" class="op-brand">
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
        <a href="{{ route('user.profile.show') }}"
           class="op-header-avatar rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2"
           aria-label="Buka profil {{ $namaPetugas }}" title="Profil Saya">
            <x-user-operasional.user-avatar :user="$petugas" size="h-10 w-10" />
        </a>
    </div>
</header>
