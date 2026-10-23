<header class="topbar">
    <div class="brand">
        <div class="kai-logo">
            <img
                src="{{ asset('images/logo-kai.jpg') }}"
                alt="Logo KAI"
            >
        </div>

        <div class="brand-text">
            <div class="brand-title">Sistem PJL</div>
            <div class="brand-subtitle">
                PT. KERETA API INDONESIA
            </div>
        </div>
    </div>

    <div class="header-right">
        <div class="pjl-info">
            <div class="pjl-name">
                {{ $lokasiDinas ?? ($dinasAktif->jpl->nama_jpl ?? 'PJL') }}
            </div>

            <div class="shift">
                @if (!empty($namaShift) && $namaShift !== '-')
                    {{ $namaShift }}
                    @if (!empty($jamDinas) && $jamDinas !== '-')
                        ({{ $jamDinas }})
                    @endif
                @elseif (isset($dinasAktif) && $dinasAktif)
                    {{ $dinasAktif->shift->nama_shift ?? 'Shift belum ditentukan' }}
                @else
                    Shift belum ditentukan
                @endif
            </div>
        </div>

        <form
            action="{{ route('logout') }}"
            method="POST"
            class="logout-form"
        >
            @csrf

            <button type="submit" class="logout-btn">
                <span>↪</span>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</header>