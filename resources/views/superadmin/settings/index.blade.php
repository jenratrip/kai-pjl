@extends('layouts.superadmin')

@section('title', 'Pengaturan Superadmin')
@section('page-title', 'Pengaturan Superadmin')

@section('content')
    <div class="space-y-6">
        <header>
            <h1 class="text-2xl font-bold text-gray-800">Pengaturan Superadmin</h1>
            <p class="mt-1 text-sm text-gray-500">
                Kelola informasi profil dan keamanan akun Superadmin.
            </p>
        </header>

        <nav aria-label="Bagian Pengaturan Superadmin" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <a href="#profil-superadmin"
               class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:bg-blue-50/40">
                <span class="text-sm font-semibold text-gray-800">Profil Superadmin</span>
                <span class="mt-1 block text-xs text-gray-500">Informasi akun yang sedang login</span>
            </a>

            <a href="#keamanan-akun"
               class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:bg-blue-50/40">
                <span class="text-sm font-semibold text-gray-800">Keamanan Akun</span>
                <span class="mt-1 block text-xs text-gray-500">Pengelolaan akses dan kata sandi</span>
            </a>
        </nav>

        <section id="profil-superadmin" class="scroll-mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM4 21a8 8 0 0 1 16 0" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-800">Profil Superadmin</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Informasi akun Superadmin yang sedang login.
                    </p>
                </div>
            </div>

            @if (session('success'))
                <div class="mt-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->hasAny(['name', 'email', 'phone']))
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <p class="font-semibold">Periksa kembali data profil:</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        @foreach (['name', 'email', 'phone'] as $field)
                            @foreach ($errors->get($field) as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                action="{{ route('superadmin.settings.profile.update') }}"
                method="POST"
                class="mt-6 border-t border-gray-100 pt-6"
            >
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(220px,1fr)_minmax(0,2fr)] lg:gap-8">
                    <div>
                        <dl class="space-y-3 rounded-lg bg-gray-50 p-4">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">NIP</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-800">{{ $user->nip ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Role</dt>
                                <dd class="mt-1 text-sm font-medium capitalize text-gray-800">{{ $user->role?->name ?? 'Superadmin' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nama lengkap</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $user->name) }}"
                                required
                                maxlength="255"
                                autocomplete="name"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email', $user->email) }}"
                                maxlength="255"
                                autocomplete="email"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="mb-1 block text-sm font-medium text-gray-700">Nomor telepon</label>
                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                value="{{ old('phone', $user->phone) }}"
                                maxlength="20"
                                autocomplete="tel"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            >
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Simpan Profil
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <section id="keamanan-akun" class="scroll-mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 10V7a5 5 0 0 1 10 0v3m-11 0h12a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2Zm5 4v3" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-800">Keamanan Akun</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Pengelolaan keamanan dan kata sandi akun Superadmin.
                    </p>
                </div>
            </div>

            @if (session('password_success'))
                <div class="mt-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
                    {{ session('password_success') }}
                </div>
            @endif

            <form
                action="{{ route('superadmin.settings.password.update') }}"
                method="POST"
                class="mt-6 max-w-2xl space-y-5 border-t border-gray-100 pt-6"
            >
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="mb-1 block text-sm font-medium text-gray-700">Password saat ini</label>
                    <div class="relative">
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="block h-11 w-full rounded-lg border border-gray-300 px-3 pr-12 text-sm text-gray-800 shadow-sm transition placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                        <button
                            type="button"
                            data-password-toggle="current_password"
                            data-password-label="password saat ini"
                            aria-label="Tampilkan password saat ini"
                            aria-controls="current_password"
                            aria-pressed="false"
                            class="absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center rounded-r-lg text-gray-500 transition hover:bg-gray-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                        >
                            <svg data-eye-open class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                <circle cx="12" cy="12" r="2.75" stroke-width="1.8" />
                            </svg>
                            <svg data-eye-closed class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.6 10.6 0 0 1 12 5c6.25 0 9.75 7 9.75 7a16.6 16.6 0 0 1-3.1 3.77M6.23 6.23C3.67 7.9 2.25 12 2.25 12s3.5 7 9.75 7c1.1 0 2.1-.2 3-.54" />
                            </svg>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password baru</label>
                    <div class="relative">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            class="block h-11 w-full rounded-lg border border-gray-300 px-3 pr-12 text-sm text-gray-800 shadow-sm transition placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                        <button
                            type="button"
                            data-password-toggle="password"
                            data-password-label="password baru"
                            aria-label="Tampilkan password baru"
                            aria-controls="password"
                            aria-pressed="false"
                            class="absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center rounded-r-lg text-gray-500 transition hover:bg-gray-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                        >
                            <svg data-eye-open class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                <circle cx="12" cy="12" r="2.75" stroke-width="1.8" />
                            </svg>
                            <svg data-eye-closed class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.6 10.6 0 0 1 12 5c6.25 0 9.75 7 9.75 7a16.6 16.6 0 0 1-3.1 3.77M6.23 6.23C3.67 7.9 2.25 12 2.25 12s3.5 7 9.75 7c1.1 0 2.1-.2 3-.54" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Konfirmasi password baru</label>
                    <div class="relative">
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            class="block h-11 w-full rounded-lg border border-gray-300 px-3 pr-12 text-sm text-gray-800 shadow-sm transition placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                        <button
                            type="button"
                            data-password-toggle="password_confirmation"
                            data-password-label="konfirmasi password baru"
                            aria-label="Tampilkan konfirmasi password baru"
                            aria-controls="password_confirmation"
                            aria-pressed="false"
                            class="absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center rounded-r-lg text-gray-500 transition hover:bg-gray-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                        >
                            <svg data-eye-open class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                                <circle cx="12" cy="12" r="2.75" stroke-width="1.8" />
                            </svg>
                            <svg data-eye-closed class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.24A10.6 10.6 0 0 1 12 5c6.25 0 9.75 7 9.75 7a16.6 16.6 0 0 1-3.1 3.77M6.23 6.23C3.67 7.9 2.25 12 2.25 12s3.5 7 9.75 7c1.1 0 2.1-.2 3-.54" />
                            </svg>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Ganti Password
                    </button>
                </div>
            </form>
        </section>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-password-toggle]').forEach((button) => {
                const input = document.getElementById(button.dataset.passwordToggle);
                const eyeOpen = button.querySelector('[data-eye-open]');
                const eyeClosed = button.querySelector('[data-eye-closed]');

                button.addEventListener('click', () => {
                    const shouldShowPassword = input.type === 'password';

                    input.type = shouldShowPassword ? 'text' : 'password';
                    button.setAttribute('aria-pressed', String(shouldShowPassword));
                    button.setAttribute(
                        'aria-label',
                        `${shouldShowPassword ? 'Sembunyikan' : 'Tampilkan'} ${button.dataset.passwordLabel}`
                    );
                    eyeOpen.classList.toggle('hidden', shouldShowPassword);
                    eyeClosed.classList.toggle('hidden', !shouldShowPassword);
                });
            });
        </script>
    @endpush
@endsection
