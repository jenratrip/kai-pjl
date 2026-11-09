@extends('layouts.user')

@section('title', 'Detail Pesan Pembinaan - Sistem PJL')

@section('content')
    <div class="w-full min-w-0 px-2 py-6 sm:px-5 lg:px-8 lg:py-7">
        <section class="mx-auto w-full max-w-4xl">
            <a
                href="{{ route('user.coaching-messages.index') }}"
                class="mb-5 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali ke Pesan Pembinaan
            </a>

            @if (session('success'))
                <div role="status" class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 bg-slate-50 p-5 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pesan Pembinaan</p>
                            <h1 class="mt-1 text-xl font-bold text-[#1d2d50] sm:text-2xl">{{ $message->subject }}</h1>
                        </div>
                        @if ($message->read_at === null)
                            <span class="w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Belum dibaca</span>
                        @else
                            <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Sudah dibaca</span>
                        @endif
                    </div>
                    <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Diterima</dt>
                            <dd class="mt-1 font-medium text-slate-800">{{ $message->received_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tersedia sampai</dt>
                            <dd class="mt-1 font-medium text-slate-800">{{ $message->expires_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</dd>
                        </div>
                    </dl>
                </header>

                <div class="space-y-5 p-5 sm:p-6">
                    <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm leading-7 text-slate-700 sm:p-5">
                        <p class="whitespace-pre-wrap">{{ $message->body }}</p>
                    </div>

                    @if ($message->read_at === null)
                        <form method="POST" action="{{ route('user.coaching-messages.read', $message) }}">
                            @csrf
                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#14213d]">
                                Tandai sudah dibaca
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-slate-500">
                            Dibaca {{ $message->read_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB.
                        </p>
                    @endif
                </div>
            </article>
        </section>
    </div>
@endsection
