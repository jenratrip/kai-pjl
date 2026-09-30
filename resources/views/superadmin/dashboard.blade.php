@extends('layouts.superadmin')

@section('title', 'Dashboard')

@section('page-title', 'Dashboard')

@section('content')

    {{-- Statistik --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">

        {{-- Total User --}}
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <p class="text-sm text-gray-500">
                Total User
            </p>

            <p class="mt-2 text-3xl font-bold text-gray-800">
                {{ $totalUsers }}
            </p>
        </div>

        {{-- Total Admin --}}
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <p class="text-sm text-gray-500">
                Total Admin
            </p>

            <p class="mt-2 text-3xl font-bold text-gray-800">
                {{ $totalAdmins }}
            </p>
        </div>

        {{-- Total Petugas --}}
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <p class="text-sm text-gray-500">
                Total Petugas
            </p>

            <p class="mt-2 text-3xl font-bold text-gray-800">
                {{ $totalPetugas }}
            </p>
        </div>

        {{-- Activity Hari Ini --}}
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <p class="text-sm text-gray-500">
                Aktivitas Hari Ini
            </p>

            <p class="mt-2 text-3xl font-bold text-gray-800">
                {{ $activitiesToday }}
            </p>
        </div>

    </div>


    {{-- Recent Activity --}}
    <div class="mt-8 rounded-xl bg-white shadow-sm">

        <div class="border-b border-gray-200 px-6 py-5">

            <h3 class="text-lg font-semibold text-gray-800">
                Aktivitas Terbaru
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                Aktivitas terbaru yang dilakukan pengguna sistem.
            </p>

        </div>

        <div class="divide-y divide-gray-100">

            @forelse ($recentActivities as $activity)

                <div class="flex items-center justify-between px-6 py-4">

                    <div class="flex items-center gap-4">

                        {{-- Icon --}}
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 text-gray-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 8v4l3 2"
                                />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                />
                            </svg>

                        </div>

                        {{-- Activity Information --}}
                        <div>

                            <p class="text-sm font-medium text-gray-800">
                                {{ $activity->description ?? ucfirst($activity->action) }}
                            </p>

                            <p class="mt-1 text-xs text-gray-500">
                                {{ $activity->user->name ?? 'Sistem' }}
                                ·
                                {{ $activity->module }}
                            </p>

                        </div>

                    </div>

                    {{-- Time --}}
                    <div class="text-right">

                        <p class="text-xs text-gray-500">
                            {{ $activity->created_at->format('d M Y') }}
                        </p>

                        <p class="mt-1 text-xs font-medium text-gray-700">
                            {{ $activity->created_at->format('H:i') }}
                        </p>

                    </div>

                </div>

            @empty

                <div class="px-6 py-10 text-center">

                    <p class="text-sm text-gray-500">
                        Belum ada aktivitas.
                    </p>

                </div>

            @endforelse

        </div>

    </div>

@endsection