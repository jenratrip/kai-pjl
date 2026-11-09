@extends('layouts.superadmin')

@section('title', 'Dashboard Superadmin')
@section('page-title', 'Dashboard Superadmin')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Ringkasan pengawasan</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">Dashboard Superadmin</h1>
            <p class="mt-1 text-sm text-slate-500">Ikhtisar akun dan aktivitas terbaru di KAI-PJL.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800">
            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
            Akses pengawasan
        </span>
    </div>

    @php
        $summaryCards = [
            ['label' => 'Total pengguna', 'value' => $totalUsers, 'detail' => 'Seluruh akun terdaftar', 'accent' => 'border-l-blue-600'],
            ['label' => 'Admin', 'value' => $totalAdmins, 'detail' => 'Akun pengelola operasional', 'accent' => 'border-l-indigo-500'],
            ['label' => 'Petugas PJL', 'value' => $totalPetugas, 'detail' => 'Akun petugas lapangan', 'accent' => 'border-l-amber-500'],
            ['label' => 'Aktivitas hari ini', 'value' => $activitiesToday, 'detail' => 'Catatan pada Activity Log', 'accent' => 'border-l-emerald-500'],
        ];
    @endphp

    <section aria-label="Ringkasan sistem" class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <article class="rounded-2xl border border-slate-200 border-l-4 {{ $card['accent'] }} bg-white p-5 shadow-sm sm:p-6">
                <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($card['value']) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $card['detail'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-activity-title">
        <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:px-6 sm:py-5">
            <h2 id="recent-activity-title" class="text-lg font-semibold text-slate-900">Aktivitas terbaru</h2>
            <p class="text-sm text-slate-500">Perubahan terbaru yang tercatat dari pengguna sistem.</p>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($recentActivities as $activity)
                <article class="flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2" />
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="break-words text-sm font-semibold text-slate-800">{{ $activity->description ?? ucfirst($activity->action) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $activity->user->name ?? 'Sistem' }} <span aria-hidden="true">·</span> {{ $activity->module }}</p>
                        </div>
                    </div>
                    <time class="shrink-0 text-left text-xs text-slate-500 sm:text-right" datetime="{{ $activity->created_at?->toIso8601String() }}">
                        <span class="block font-medium text-slate-700">{{ $activity->created_at?->format('d M Y') ?? '—' }}</span>
                        <span class="mt-1 block">{{ $activity->created_at?->format('H:i') ?? '—' }} WIB</span>
                    </time>
                </article>
            @empty
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-medium text-slate-700">Belum ada aktivitas tercatat.</p>
                    <p class="mt-1 text-xs text-slate-500">Aktivitas pengguna akan muncul di sini setelah tercatat.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
