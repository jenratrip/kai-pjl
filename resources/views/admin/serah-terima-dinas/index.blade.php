@extends('layouts.admin')

@section('title', 'Serah Terima Dinas')

@section('content')
<div class="p-6 lg:p-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-[#1d2d50]">
            Serah Terima Dinas
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Daftar pengajuan serah terima dinas dari petugas PJL
            yang ditujukan kepada akun Anda.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div id="liveNotification"
         class="mb-5 hidden rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
         role="status"
         aria-live="polite">
    </div>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span id="liveIndicator"
                  class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
            <span id="liveStatus">Menghubungkan...</span>
        </div>

        <span id="pendingCount"
              class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
            Menunggu konfirmasi: —
        </span>
    </div>

    <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="status"
                       class="mb-1 block text-sm font-medium text-gray-700">
                    Status pengajuan
                </label>

                <select name="status"
                        id="status"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-1 focus:ring-[#1d2d50]">
                    <option value=""
                        @selected(request('status') === '')>
                        Semua status
                    </option>

                    <option value="menunggu_penerima"
                        @selected(request('status') === 'menunggu_penerima')>
                        Menunggu konfirmasi
                    </option>

                    <option value="selesai"
                        @selected(request('status') === 'selesai')>
                        Selesai
                    </option>
                </select>
            </div>

            <a href="{{ route('admin.serah-terima-dinas.index') }}"
               id="resetFilter"
               class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Reset
            </a>
        </div>

        <p class="mt-2 text-xs text-gray-500">
            Daftar diperbarui otomatis. Pilihan status langsung diterapkan.
        </p>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-5 py-4">No.</th>
                        <th class="px-5 py-4">Petugas PJL</th>
                        <th class="px-5 py-4">JPL</th>
                        <th class="px-5 py-4">Waktu</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Aksi</th>
                    </tr>
                </thead>

                <tbody id="serahTerimaTableBody"
                       class="divide-y divide-gray-100">
                    @forelse ($serahTerima as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                {{ $serahTerima->firstItem() + $loop->index }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="font-semibold text-gray-800">
                                    {{ $item->dinasPenyerah?->user?->name ?? '—' }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ $item->dinasPenyerah?->user?->nip ?? '—' }}
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                {{ $item->dinasPenyerah?->jpl?->nama_jpl
                                    ?? $item->dinasPenyerah?->jpl?->kode_jpl
                                    ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                {{ $item->waktu_serah_terima
                                    ? $item->waktu_serah_terima->format('d/m/Y H:i')
                                    : '—' }}
                            </td>

                            <td class="px-5 py-4">
                                @if ($item->status === 'selesai')
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                        Selesai
                                    </span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                        Menunggu konfirmasi
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <a href="{{ route('admin.serah-terima-dinas.show', $item) }}"
                                   class="inline-flex whitespace-nowrap rounded-lg bg-[#1d2d50] px-3 py-2 text-xs font-semibold text-white hover:bg-[#293f69]">
                                    Lihat detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-semibold text-gray-700">
                                    Belum ada pengajuan serah terima.
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    Pengajuan akan muncul jika ditujukan
                                    kepada akun admin yang sedang login.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="paginationContainer"
             class="border-t border-gray-100 p-4">
            {{ $serahTerima->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filter = document.getElementById('status');
    const tbody = document.getElementById('serahTerimaTableBody');
    const notification = document.getElementById('liveNotification');
    const liveStatus = document.getElementById('liveStatus');
    const liveIndicator = document.getElementById('liveIndicator');
    const pendingCount = document.getElementById('pendingCount');
    const pagination = document.getElementById('paginationContainer');

    if (!filter || !tbody) return;

    const updatesUrl = @json(route('admin.serah-terima-dinas.updates'));
    const initialRows = [...tbody.querySelectorAll('tr')];

    const knownIds = new Set(
        initialRows
            .map(row => {
                const link = row.querySelector('a[href]');
                const match = link?.href.match(/serah-terima-dinas\/(\d+)(?:$|[?#])/);
                return match ? match[1] : null;
            })
            .filter(Boolean)
    );

    let selectedStatus = filter.value;
    let busy = false;
    let firstLoad = true;
    let lastPending = null;
    let notificationTimer = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function showNotification(message) {
        notification.textContent = message;
        notification.classList.remove('hidden');

        if (notificationTimer) {
            clearTimeout(notificationTimer);
        }

        notificationTimer = setTimeout(function () {
            notification.classList.add('hidden');
        }, 8000);
    }

    function renderStatus(status) {
        if (status === 'selesai') {
            return `
                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                    Selesai
                </span>`;
        }

        return `
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                Menunggu konfirmasi
            </span>`;
    }

    function renderRows(items) {
        if (!items.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center">
                        <p class="font-semibold text-gray-700">
                            Belum ada pengajuan serah terima.
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            Belum ada pengajuan untuk status ini.
                        </p>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = items.map(function (item, index) {
            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-4">${index + 1}</td>

                    <td class="px-5 py-4">
                        <div class="font-semibold text-gray-800">
                            ${escapeHtml(item.petugas)}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            ${escapeHtml(item.nip)}
                        </div>
                    </td>

                    <td class="px-5 py-4">${escapeHtml(item.jpl)}</td>

                    <td class="whitespace-nowrap px-5 py-4">
                        ${escapeHtml(item.waktu)}
                    </td>

                    <td class="px-5 py-4">
                        ${renderStatus(item.status)}
                    </td>

                    <td class="px-5 py-4">
                        <a href="${escapeHtml(item.detail_url)}"
                           class="inline-flex whitespace-nowrap rounded-lg bg-[#1d2d50] px-3 py-2 text-xs font-semibold text-white hover:bg-[#293f69]">
                            Lihat detail
                        </a>
                    </td>
                </tr>`;
        }).join('');
    }

    async function loadUpdates(showNewNotification = true) {
        if (busy) return;
        busy = true;

        try {
            const url = new URL(updatesUrl, window.location.origin);

            if (selectedStatus) {
                url.searchParams.set('status', selectedStatus);
            }

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Gagal mengambil data.');
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error('Respons server tidak valid.');
            }

            const items = result.items || [];

            if (showNewNotification && !firstLoad) {
                const newItems = items.filter(item => {
                    return !knownIds.has(String(item.id));
                });

                if (newItems.length > 0) {
                    showNotification(
                        `${newItems.length} pengajuan serah terima baru diterima.`
                    );

                    if ('Notification' in window &&
                        Notification.permission === 'granted') {
                        new Notification('Serah Terima Dinas', {
                            body: `${newItems.length} pengajuan baru diterima.`
                        });
                    }
                }
            }

            items.forEach(item => knownIds.add(String(item.id)));

            if (typeof result.total_menunggu === 'number') {
                pendingCount.textContent =
                    `Menunggu konfirmasi: ${result.total_menunggu}`;

                if (
                    lastPending !== null &&
                    result.total_menunggu > lastPending &&
                    !firstLoad
                ) {
                    showNotification(
                        'Jumlah pengajuan yang menunggu konfirmasi bertambah.'
                    );
                }

                lastPending = result.total_menunggu;
            }

            renderRows(items);

            // Endpoint updates mengambil 10 data terbaru.
            // Pagination server dinonaktifkan ketika tabel dirender oleh AJAX.
            if (pagination) {
                pagination.classList.add('hidden');
            }

            liveStatus.textContent =
                'Diperbarui ' + new Date().toLocaleTimeString('id-ID');

            liveIndicator.classList.remove('bg-red-500');
            liveIndicator.classList.add('bg-green-500');

            firstLoad = false;
        } catch (error) {
            liveStatus.textContent = 'Gagal memperbarui. Mencoba kembali...';
            liveIndicator.classList.remove('bg-green-500');
            liveIndicator.classList.add('bg-red-500');
        } finally {
            busy = false;
        }
    }

    filter.addEventListener('change', function () {
        selectedStatus = this.value;

        const url = new URL(window.location.href);

        if (selectedStatus) {
            url.searchParams.set('status', selectedStatus);
        } else {
            url.searchParams.delete('status');
        }

        url.searchParams.delete('page');
        window.history.replaceState({}, '', url);

        loadUpdates(false);
    });

    loadUpdates(false);
    setInterval(function () {
        loadUpdates(true);
    }, 10000);

    // Meminta izin notifikasi browser setelah interaksi pengguna.
    if ('Notification' in window &&
        Notification.permission === 'default') {
        document.addEventListener('click', function askPermission() {
            Notification.requestPermission();
        }, { once: true });
    }
});
</script>
@endpush
