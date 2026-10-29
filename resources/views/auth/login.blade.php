<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login PJL - Sistem Operasional PJL</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [hidden] {
            display: none !important;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #1E3157;
            color: #1F3157;
            font-family: Arial, sans-serif;
        }

        .input-icon {
            pointer-events: none;
            position: absolute;
            top: 50%;
            left: 13px;
            z-index: 2;
            display: block;
            width: 18px;
            height: 18px;
            transform: translateY(-50%);
            color: #6B7280;
        }

        .input-icon svg,
        .password-toggle svg,
        .info-icon svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        fieldset.pjl-fields {
            min-width: 0;
            border: 0;
            padding: 0;
            margin: 0;
        }

        .jenis-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .jenis-group--row {
            flex-wrap: nowrap;
        }

        .jenis-option {
            position: relative;
            flex: 1 1 90px;
            min-width: 0;
        }

        .jenis-option input {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            opacity: 0;
            pointer-events: none;
        }

        .jenis-option label {
            display: flex;
            min-height: 36px;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            border: 1px solid #DDE1E7;
            border-radius: 8px;
            color: #6B7280;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .jenis-option input:checked + label {
            border-color: #E3943D;
            background: #FFF7ED;
            color: #B45F0F;
        }

        .jenis-option input:focus-visible + label {
            outline: 3px solid rgba(227, 148, 61, 0.25);
            outline-offset: 1px;
        }

        .dinasan-empty {
            margin: 0;
            color: #B91C1C;
            font-size: 12px;
        }

        @media (max-width: 360px) {
            .jenis-option label {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    @php
        // Dikirim dari LoginController::showLoginForm() => [id_shift => nama_shift]
        $dinasanList = $dinasanList ?? [];
        $jenis = old('jenis_petugas', 'reguler');

        // Tampilkan bagian PJL jika ada input/error PJL dari submit sebelumnya.
        $showPjl = old('nomor_pjl') || old('dinasan')
            || $errors->has('nomor_pjl')
            || $errors->has('dinasan')
            || $errors->has('jenis_petugas');
    @endphp

    <main class="flex min-h-screen items-center justify-center px-5 py-6">
        <div class="w-full max-w-[410px] rounded-[10px] bg-white px-[30px] pt-6 pb-5 shadow-[0_8px_25px_rgba(0,0,0,0.16)] max-[600px]:max-w-[380px] max-[600px]:px-[22px] max-[360px]:px-4">

            <div class="mb-2 text-center">
                <img
                    src="{{ asset('images/logo-kai.jpg') }}"
                    alt="Logo KAI"
                    class="mx-auto block h-[58px] w-[100px] object-contain max-[600px]:h-[52px] max-[600px]:w-[90px] max-[360px]:h-12 max-[360px]:w-[82px]"
                >
            </div>

            <div class="mt-[5px] text-center text-[17px] leading-[1.3] font-bold text-[#1F3157] max-[600px]:text-base max-[360px]:text-[15px]">
                Sistem Operasional PJL
            </div>

            <div class="mt-[5px] text-center text-[11px] leading-[1.4] text-[#6B7280] max-[600px]:text-[10px] max-[360px]:text-[9px]">
                PT. Kereta Api Indonesia (Persero)
            </div>

            <hr class="my-4 border-0 border-t border-[#E5E7EB]">

            @if ($errors->any())
                <div class="mb-4 rounded-[7px] border border-red-200 bg-red-50 px-[13px] py-[11px] text-xs leading-[1.5] text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Error dari pengecekan via JavaScript --}}
            <div
                class="mb-4 rounded-[7px] border border-red-200 bg-red-50 px-[13px] py-[11px] text-xs leading-[1.5] text-red-700"
                id="clientError"
                hidden
            ></div>

            <form
                method="POST"
                action="{{ route('login.store') }}"
                data-cek-url="{{ route('login.cek') }}"
                id="loginForm"
            >
                @csrf

                {{-- Kredensial semua role --}}
                <div class="mb-3">
                    <label
                        for="nip"
                        class="mb-[5px] block text-[13px] font-bold text-[#1F3157] max-[360px]:text-xs"
                    >
                        NIPP / NIPKWT
                    </label>

                    <div class="relative w-full">
                        <span class="input-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="12" cy="7" r="4"></circle>
                                <path d="M5 21v-2a7 7 0 0 1 14 0v2"></path>
                            </svg>
                        </span>

                        <input
                            type="text"
                            name="nip"
                            id="nip"
                            class="h-[42px] w-full rounded-lg border border-[#DDE1E7] bg-white py-0 pr-[45px] pl-[42px] text-[13px] text-[#1F3157] outline-none transition-all duration-200 placeholder:text-[#9CA3AF] focus:border-[#1F3157] focus:ring-[3px] focus:ring-[#1F3157]/[0.08] max-[600px]:h-11 max-[360px]:h-[42px] max-[360px]:text-xs"
                            placeholder="Masukkan NIPP atau NIPKWT"
                            value="{{ old('nip') }}"
                            autocomplete="username"
                            inputmode="numeric"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <label
                        for="password"
                        class="mb-[5px] block text-[13px] font-bold text-[#1F3157] max-[360px]:text-xs"
                    >
                        Kata Sandi
                    </label>

                    <div class="relative w-full">
                        <span class="input-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect x="4" y="10" width="16" height="10" rx="2"></rect>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                            </svg>
                        </span>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="h-[42px] w-full rounded-lg border border-[#DDE1E7] bg-white py-0 pr-[45px] pl-[42px] text-[13px] text-[#1F3157] outline-none transition-all duration-200 placeholder:text-[#9CA3AF] focus:border-[#1F3157] focus:ring-[3px] focus:ring-[#1F3157]/[0.08] max-[600px]:h-11 max-[360px]:h-[42px] max-[360px]:text-xs"
                            placeholder="••••••••••••"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle absolute top-1/2 right-[11px] flex h-6 w-6 -translate-y-1/2 items-center justify-center border-0 bg-transparent p-0 text-[#6B7280] transition-colors hover:text-[#1F3157] focus:outline-none"
                            data-target="password"
                            aria-label="Tampilkan password"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Khusus petugas PJL --}}
                <fieldset
                    class="pjl-fields"
                    id="pjlFields"
                    {{ $showPjl ? '' : 'hidden disabled' }}
                >
                    <div class="mt-4 mb-3 border-t border-dashed border-[#E5E7EB] pt-3 text-xs leading-[1.4] text-[#6B7280]">
                        <strong class="block text-[13px] text-[#1F3157]">
                            Data Dinas Petugas PJL
                        </strong>
                        Lengkapi data berikut untuk memulai dinas.
                    </div>

                    {{-- Nomor PJL --}}
                    <div class="mb-3">
                        <label
                            for="nomorPjl"
                            class="mb-[5px] block text-[13px] font-bold text-[#1F3157] max-[360px]:text-xs"
                        >
                            Nomor PJL
                        </label>

                        <div class="relative w-full">
                            <span class="input-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"></path>
                                    <circle cx="12" cy="10" r="2.5"></circle>
                                </svg>
                            </span>

                            <input
                                type="text"
                                name="nomor_pjl"
                                id="nomorPjl"
                                class="h-[42px] w-full rounded-lg border border-[#DDE1E7] bg-white py-0 pr-[45px] pl-[42px] text-[13px] text-[#1F3157] outline-none transition-all duration-200 placeholder:text-[#9CA3AF] focus:border-[#1F3157] focus:ring-[3px] focus:ring-[#1F3157]/[0.08] max-[600px]:h-11 max-[360px]:h-[42px] max-[360px]:text-xs"
                                placeholder="Contoh: 351"
                                value="{{ old('nomor_pjl') }}"
                                required
                            >
                        </div>
                    </div>

                    {{-- Dinasan --}}
                    <div class="mb-3">
                        <span class="mb-[5px] block text-[13px] font-bold text-[#1F3157] max-[360px]:text-xs">
                            Dinasan
                        </span>

                        <div class="jenis-group">
                            @forelse ($dinasanList as $shiftId => $shiftNama)
                                <div class="jenis-option">
                                    <input
                                        type="radio"
                                        name="dinasan"
                                        id="dinasan{{ $shiftId }}"
                                        value="{{ $shiftId }}"
                                        {{ (string) old('dinasan') === (string) $shiftId ? 'checked' : '' }}
                                        required
                                    >
                                    <label for="dinasan{{ $shiftId }}">
                                        {{ $shiftNama }}
                                    </label>
                                </div>
                            @empty
                                <p class="dinasan-empty">
                                    Data dinasan belum tersedia.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Jenis petugas --}}
                    <div class="mb-3">
                        <span class="mb-[5px] block text-[13px] font-bold text-[#1F3157] max-[360px]:text-xs">
                            Jenis Petugas
                        </span>

                        <div class="jenis-group jenis-group--row">
                            <div class="jenis-option">
                                <input
                                    type="radio"
                                    name="jenis_petugas"
                                    id="jenisReguler"
                                    value="reguler"
                                    {{ $jenis === 'reguler' ? 'checked' : '' }}
                                >
                                <label for="jenisReguler">Reguler</label>
                            </div>

                            <div class="jenis-option">
                                <input
                                    type="radio"
                                    name="jenis_petugas"
                                    id="jenisWakilan"
                                    value="wakilan"
                                    {{ $jenis === 'wakilan' ? 'checked' : '' }}
                                >
                                <label for="jenisWakilan">Wakilan (Pengganti)</label>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <button
                    type="submit"
                    class="login-button mt-0.5 h-11 w-full cursor-pointer rounded-lg border-0 bg-[#E3943D] font-[Arial] text-sm font-bold text-white transition-all hover:-translate-y-px hover:bg-[#D5822B] active:translate-y-0 focus:outline-none focus:ring-[3px] focus:ring-[#E3943D]/25 disabled:cursor-wait disabled:opacity-70 disabled:transform-none max-[600px]:h-[46px] max-[600px]:text-[13px] max-[360px]:h-11 max-[360px]:text-xs"
                >
                    Masuk ke Sistem
                </button>
            </form>

            <div class="mt-3 flex items-center justify-center gap-1.5 text-center text-[10px] leading-[1.4] text-[#7B8491] max-[600px]:text-[9px] max-[360px]:text-[8px]">
                <span class="flex h-[13px] w-[13px] shrink-0 items-center justify-center">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="12" cy="12" r="9"></circle>
                        <line x1="12" y1="10" x2="12" y2="16"></line>
                        <circle cx="12" cy="7" r="0.5" fill="currentColor"></circle>
                    </svg>
                </span>

                <span>Gunakan kredensial resmi Anda.</span>
            </div>
        </div>
    </main>
</body>
</html>