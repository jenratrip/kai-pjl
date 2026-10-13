@extends('layouts.admin')

@section('title', 'Manajemen User')

@section('content')
<div class="p-6">

    {{-- Header --}}
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Manajemen User
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Kelola akun Petugas Jaga Lintasan (PJL).
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">

            {{-- Download Template --}}
            <a
                href="{{ route('admin.users.import.template') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-[#1d2d50] transition hover:bg-gray-100"
            >
                <span>↓</span>
                Download Template
            </a>

            {{-- Tombol Import --}}
            <button
                type="button"
                onclick="
                        const modal = document.getElementById('importUserModal');
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    "
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16233f]"
            >
                <span>↑</span>
                Import Excel
            </button>

        </div>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="mb-2 font-semibold">Terjadi kesalahan:</p>
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Statistik --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50">
                    <span class="font-bold text-[#1d2d50]">U</span>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Total Akun PJL</p>
                    <p class="mt-1 text-2xl font-bold text-[#1d2d50]">
                        {{ $totalUsers }}
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-50">
                    <span class="font-bold text-green-600">A</span>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Akun Aktif</p>
                    <p class="mt-1 text-2xl font-bold text-green-600">
                        {{ $totalActive }}
                    </p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50">
                    <span class="font-bold text-red-600">N</span>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Akun Nonaktif</p>
                    <p class="mt-1 text-2xl font-bold text-red-600">
                        {{ $totalInactive }}
                    </p>
                </div>
            </div>
        </div>

    </div>

    {{-- Tabel Pengguna --}}
    <div id="usersResults" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="border-b border-gray-200 p-5">
            <h2 class="text-lg font-bold text-[#1d2d50]">
                Daftar Pengguna
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Cari dan lihat data akun PJL yang terdaftar.
            </p>
        </div>


        {{-- Pencarian dan Filter --}}
        <div class="flex flex-col gap-3 border-b border-gray-100 p-5 md:flex-row">
            <div class="relative flex-1">
                <input
                    type="search"
                    id="user-search"
                    autocomplete="off"
                    value="{{ request('search') }}"
                    placeholder="Cari NIP, nama, atau email..."
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-10 text-sm outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-blue-100"
                >

                <div
                    id="search-loading"
                    class="absolute right-3 top-1/2 hidden -translate-y-1/2"
                >
                    <svg class="h-4 w-4 animate-spin text-[#1d2d50]"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                </div>
            </div>

            <select
                id="status-filter"
                class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-[#1d2d50] md:w-48"
            >
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>
                    Aktif
                </option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>
                    Nonaktif
                </option>
            </select>

            <button
                id="reset-filter"
                type="button"
                class="{{ request()->filled('search') || request()->filled('status') ? '' : 'hidden' }} rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
            >
                Reset
            </button>
        </div>


        {{-- Tabel --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">

                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-4 font-semibold">No.</th>
                        <th class="px-5 py-4 font-semibold">NIP</th>
                        <th class="px-5 py-4 font-semibold">Nama Lengkap</th>
                        <th class="px-5 py-4 font-semibold">Email</th>
                        <th class="px-5 py-4 font-semibold">Role</th>
                        <th class="px-5 py-4 font-semibold">Status</th>
                        <th class="px-5 py-4 font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody id="users-table-body" class="divide-y divide-gray-100">

                    @forelse ($users as $user)
                        <tr class="transition hover:bg-gray-50">

                            <td class="whitespace-nowrap px-5 py-4 text-gray-500">
                                {{ $users->firstItem() + $loop->index }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-medium text-[#1d2d50]">
                                {{ $user->nip }}
                            </td>

                            <td class="px-5 py-4 font-medium text-gray-800">
                                {{ $user->name }}
                            </td>

                            <td class="px-5 py-4 text-gray-600">
                                {{ $user->email }}
                            </td>

                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $user->role->name === 'admin'
                                        ? 'bg-purple-50 text-purple-700'
                                        : 'bg-blue-50 text-blue-700' }}">
                                    {{ strtoupper($user->role->name) }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                @if ($user->is_active)
                                    <span class="inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex items-center gap-2">
                                    {{-- Detail --}}
                                    <a
                                        href="{{ route('admin.users.show', $user->id) }}"
                                        title="Detail pengguna"
                                        aria-label="Detail pengguna"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg
                                            bg-blue-50 text-blue-700 hover:bg-blue-100"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 12s3.5-7 9.75-7 9.75 7 9.75 7-3.5 7-9.75 7-9.75-7-9.75-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>

                                    {{-- Edit --}}
                                    <a
                                        href="{{ route('admin.users.edit', $user->id) }}"
                                        title="Edit pengguna"
                                        aria-label="Edit pengguna"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg
                                            bg-amber-50 text-amber-700 hover:bg-amber-100"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m16.862 4.487 2.651 2.651M4 20l4.5-.9L19.2 8.4a1.875 1.875 0 0 0-2.65-2.65L5.85 16.45 4 20Z"/>
                                        </svg>
                                    </a>

                                    {{-- Aktifkan / Nonaktifkan --}}
                                    <form
                                        action="{{ route('admin.users.toggle-status', $user->id) }}"
                                        method="POST"
                                        onsubmit="return confirm(this.dataset.confirm)"
                                        data-confirm="{{ $user->is_active ? 'Yakin ingin menonaktifkan akun ini?' : 'Yakin ingin mengaktifkan akun ini?' }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            title="{{ $user->is_active ? 'Nonaktifkan pengguna' : 'Aktifkan pengguna' }}"
                                            aria-label="{{ $user->is_active ? 'Nonaktifkan pengguna' : 'Aktifkan pengguna' }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg
                                                {{ $user->is_active
                                                        ? 'bg-red-50 text-red-700 hover:bg-red-100'
                                                        : 'bg-green-50 text-green-700 hover:bg-green-100' }}"
                                        >
                                            @if ($user->is_active)
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 6l12 12M18 6 6 18"/>
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m5 12 4 4L19 6"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <p class="font-semibold text-gray-600">
                                    Data pengguna tidak ditemukan.
                                </p>
                                <p class="mt-1 text-sm text-gray-400">
                                    Coba ubah kata kunci atau filter pencarian.
                                </p>
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div id="users-pagination" class="border-t border-gray-200 p-5">
            {{ $users->links() }}
        </div>

    </div>

    {{-- Modal Import User --}}
    <div
        id="importUserModal"
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/50 p-4"
    >
        <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">

            <div class="flex items-center justify-between border-b border-gray-200 p-5">
                <div>
                    <h2 class="text-lg font-bold text-[#1d2d50]">
                        Import User PJL
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Unggah file Excel sesuai template.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="closeImportUserModal()"
                    class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                    aria-label="Tutup modal"
                >
                    &times;
                </button>
            </div>

            <form
                action="{{ route('admin.users.import.preview') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-5 p-5"
            >
                @csrf

                <div>
                    <label
                        for="userExcelFile"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        File Excel
                    </label>

                    <input
                        id="userExcelFile"
                        type="file"
                        name="file"
                        accept=".xlsx,.xls,.csv"
                        required
                        class="block w-full rounded-lg border border-gray-300 text-sm text-gray-600 file:mr-4 file:border-0 file:bg-gray-100 file:px-4 file:py-3 file:font-semibold file:text-[#1d2d50] hover:file:bg-gray-200"
                    >

                    <p class="mt-2 text-xs text-gray-500">
                        Format: XLSX, XLS, atau CSV. Maksimal 5 MB.
                    </p>
                </div>

                <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-800">
                    <p class="font-semibold">Perhatikan sebelum mengunggah</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        <li>Gunakan template yang disediakan.</li>
                        <li>Role harus bernilai <code>pjl</code>.</li>
                        <li>NIP dan email tidak boleh duplikat.</li>
                        <li>Data akan diperiksa sebelum disimpan.</li>
                    </ul>
                </div>

                <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
                    <button
                        type="button"
                        onclick="document.getElementById('importUserModal').classList.add('hidden')"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#16233f]"
                    >
                        Upload dan Periksa
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    function closeImportUserModal() {
        const modal = document.getElementById('importUserModal');

        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('user-search');
        const statusFilter = document.getElementById('status-filter');
        const resetButton = document.getElementById('reset-filter');
        const tableBody = document.getElementById('users-table-body');
        const pagination = document.getElementById('users-pagination');
        const searchLoading = document.getElementById('search-loading');
        const tableLoading = document.getElementById('table-loading');

        if (!searchInput || !statusFilter || !tableBody || !pagination) {
            return;
        }

        let controller = null;

        async function loadUsers(targetUrl = null, keepPage = false) {
            const url = new URL(
                targetUrl || window.location.href,
                window.location.origin
            );

            const keyword = searchInput.value.trim();
            const status = statusFilter.value;

            if (keyword) {
                url.searchParams.set('search', keyword);
            } else {
                url.searchParams.delete('search');
            }

            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }

            if (!keepPage) {
                url.searchParams.delete('page');
            }

            if (controller) {
                controller.abort();
            }

            const currentController = new AbortController();
            controller = currentController;

            searchLoading?.classList.remove('hidden');
            tableLoading?.classList.remove('hidden');
            tableLoading?.classList.add('flex');

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: currentController.signal
                });

                if (!response.ok) {
                    throw new Error('Gagal memuat data pengguna.');
                }

                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');

                const newBody = doc.getElementById('users-table-body');
                const newPagination = doc.getElementById('users-pagination');

                if (!newBody || !newPagination) {
                    throw new Error('Bagian tabel atau pagination tidak ditemukan.');
                }

                // Pastikan hanya respons pencarian terbaru yang diterapkan.
                if (controller !== currentController) return;

                tableBody.innerHTML = newBody.innerHTML;
                pagination.innerHTML = newPagination.innerHTML;

                resetButton?.classList.toggle(
                    'hidden',
                    !keyword && !status
                );

                window.history.replaceState({}, '', url.toString());
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error(error);
                }
            } finally {
                if (controller === currentController) {
                    controller = null;
                    searchLoading?.classList.add('hidden');
                    tableLoading?.classList.add('hidden');
                    tableLoading?.classList.remove('flex');
                }
            }
        }

        // Langsung mencari setiap kali isi input berubah.
        searchInput.addEventListener('input', function () {
            loadUsers();
        });

        // Filter status langsung diterapkan.
        statusFilter.addEventListener('change', function () {
            loadUsers();
        });

        // Reset pencarian dan status.
        resetButton?.addEventListener('click', function () {
            searchInput.value = '';
            statusFilter.value = '';
            loadUsers();
        });

        // Pagination juga berjalan tanpa reload seluruh halaman.
        pagination.addEventListener('click', function (event) {
            const link = event.target.closest('a');

            if (!link || !link.href) return;

            const url = new URL(link.href, window.location.origin);

            if (url.origin !== window.location.origin) return;

            event.preventDefault();
            loadUsers(url.toString(), true);
        });
    });
</script>

@endsection
