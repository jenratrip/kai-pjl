<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Sistem Operasional PJL</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#1D2D50] flex items-center justify-center px-4">

    <div class="w-full max-w-[470px] bg-white rounded-xl shadow-lg px-12 py-14">

        {{-- Logo sementara --}}
        <div class="flex justify-center mb-4">
            <div class="text-5xl font-black tracking-tight text-[#252B61]">
                KAI
            </div>
        </div>

        {{-- Header --}}
        <div class="text-center">
            <h1 class="text-2xl font-bold text-[#1D2D50]">
                Sistem Operasional PJL
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                PT. Kereta Api Indonesia (Persero)
            </p>
        </div>

        <div class="border-t border-gray-200 my-8"></div>

        {{-- Error --}}
        @if ($errors->any())
            <div class="mb-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                @foreach ($errors->all() as $error)
                    <p class="text-sm text-red-600">
                        {{ $error }}
                    </p>
                @endforeach
            </div>
        @endif

        {{-- Login Form --}}
        <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
            @csrf

            {{-- NIP --}}
            <div>
                <label
                    for="nip"
                    class="block mb-2 text-sm font-semibold text-[#1D2D50]"
                >
                    Username / NIP
                </label>

                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <svg
                            class="w-5 h-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </div>

                    <input
                        type="text"
                        id="nip"
                        name="nip"
                        value="{{ old('nip') }}"
                        placeholder="Masukkan NIP"
                        autocomplete="username"
                        required
                        autofocus
                        class="w-full h-14 rounded-lg border border-gray-200 bg-white pl-12 pr-4 text-[#1D2D50] placeholder-gray-400 outline-none transition focus:border-[#E4973B] focus:ring-2 focus:ring-[#E4973B]/20"
                    >
                </div>
            </div>

            {{-- Password --}}
            <div>
                <label
                    for="password"
                    class="block mb-2 text-sm font-semibold text-[#1D2D50]"
                >
                    Kata Sandi
                </label>

                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <svg
                            class="w-5 h-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                            />
                        </svg>
                    </div>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan kata sandi"
                        autocomplete="current-password"
                        required
                        class="w-full h-14 rounded-lg border border-gray-200 bg-white pl-12 pr-12 text-[#1D2D50] placeholder-gray-400 outline-none transition focus:border-[#E4973B] focus:ring-2 focus:ring-[#E4973B]/20"
                    >

                    <button
                        type="button"
                        id="togglePassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-[#1D2D50]"
                        aria-label="Tampilkan kata sandi"
                    >
                        <svg
                            id="eyeIcon"
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                class="w-full h-12 rounded-lg bg-[#E4973B] text-white font-semibold text-base transition hover:bg-[#D8872B] focus:outline-none focus:ring-2 focus:ring-[#E4973B]/40"
            >
                Masuk ke Sistem
            </button>
        </form>

        {{-- Information --}}
        <div class="flex items-center justify-center gap-2 mt-5 text-xs text-gray-500">
            <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z"
                />
            </svg>

            <span>Gunakan kredensial NIP resmi Anda.</span>
        </div>

    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', () => {
            const isPassword = password.type === 'password';

            password.type = isPassword ? 'text' : 'password';

            togglePassword.setAttribute(
                'aria-label',
                isPassword
                    ? 'Sembunyikan kata sandi'
                    : 'Tampilkan kata sandi'
            );
        });
    </script>

</body>
</html>