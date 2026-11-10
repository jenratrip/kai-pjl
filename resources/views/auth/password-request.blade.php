<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi - KAI-PJL</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[#1E3157] px-5 py-8 font-sans text-[#1F3157]">
    <main class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl sm:p-8">
        <div class="mb-5 text-center">
            <img src="{{ asset('images/logo-kai.jpg') }}" alt="Logo KAI" class="mx-auto h-14 w-24 object-contain">
            <h1 class="mt-3 text-xl font-bold">Lupa Kata Sandi</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Masukkan NIPP/NIPKWT dan email yang terdaftar pada akun yang sama.</p>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm leading-6 text-blue-900" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="nip" class="mb-1 block text-sm font-semibold">NIPP / NIPKWT</label>
                <input id="nip" name="nip" value="{{ old('nip') }}" required autocomplete="username" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#1F3157] focus:outline-none focus:ring-2 focus:ring-[#1F3157]/15">
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-semibold">Email akun</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-[#1F3157] focus:outline-none focus:ring-2 focus:ring-[#1F3157]/15">
            </div>
            <button type="submit" class="w-full rounded-lg bg-[#E3943D] px-4 py-3 text-sm font-bold text-white hover:bg-[#D5822B] focus:outline-none focus:ring-2 focus:ring-[#E3943D]/40">
                Kirim permintaan reset
            </button>
        </form>

        <p class="mt-5 text-center text-sm">
            <a href="{{ route('login') }}" class="font-semibold text-[#1F3157] underline underline-offset-2">Kembali ke login</a>
        </p>
    </main>
</body>
</html>
