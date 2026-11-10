@extends('layouts.user')

@section('title', 'Pesan Pembinaan - Sistem PJL')

@section('content')
    <div class="w-full min-w-0 px-2 py-6 sm:px-5 lg:px-8 lg:py-7">
        <section class="mx-auto w-full max-w-5xl" data-coaching-message-inbox data-user-id="{{ auth()->id() }}">
            <header class="mb-6">
                <h1 class="text-2xl font-bold text-[#1d2d50]">Pesan Pembinaan</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Pesan tersedia selama 72 jam sejak diterima.
                </p>
            </header>

            @if (session('success'))
                <div role="status" class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($messages->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500" aria-hidden="true">✉</div>
                    <h2 class="font-semibold text-slate-800">Belum ada pesan aktif</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Pesan pembinaan yang diterima akan muncul di halaman ini.
                    </p>
                </div>
            @else
                <div class="mb-4 flex items-center justify-between rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    <span>{{ $messages->total() }} pesan aktif</span>
                    <span data-coaching-unread-count data-count="{{ $unreadCount }}" data-label="belum dibaca">
                        {{ $unreadCount }} belum dibaca
                    </span>
                </div>

                <div class="space-y-3">
                    @foreach ($messages as $message)
                        <a
                            href="{{ route('user.coaching-messages.show', $message) }}"
                            class="block rounded-xl border bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow-md sm:p-5 {{ $message->read_at === null ? 'border-l-4 border-l-amber-400' : 'border-slate-200' }}"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-semibold text-slate-900">{{ $message->subject }}</h2>
                                        @if ($message->read_at === null)
                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Belum dibaca</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Sudah dibaca</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 line-clamp-2 whitespace-pre-line text-sm text-slate-600">{{ $message->body }}</p>
                                </div>
                                <div class="shrink-0 text-xs leading-5 text-slate-500 sm:text-right">
                                    <p>Diterima {{ $message->received_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
                                    <p>Kedaluwarsa {{ $message->expires_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $messages->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
