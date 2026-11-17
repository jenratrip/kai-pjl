@extends('layouts.user')

@section('content')
<div
    class="min-h-screen bg-slate-50 px-4 py-6 md:px-6"
    x-data="tukarJadwalPage()"
>
    <div class="mx-auto max-w-7xl space-y-6">

        {{-- HEADER --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Tukar Jadwal Dinas
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Ajukan pertukaran jadwal dengan petugas PJL lain dan pantau status permintaan.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('user.tukar-jadwal-dinas.history') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <i class="fas fa-history"></i>
                    Riwayat
                </a>

                <button
                    type="button"
                    @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600"
                >
                    <i class="fas fa-exchange-alt"></i>
                    Ajukan Tukar Jadwal
                </button>
            </div>
        </div>

        {{-- INFORMASI --}}
        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
            <div class="flex gap-3">
                <div class="mt-0.5 text-blue-600">
                    <i class="fas fa-info-circle text-lg"></i>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-blue-900">
                        Informasi pertukaran jadwal
                    </h3>

                    <p class="mt-1 text-sm leading-relaxed text-blue-800">
                        Pilih jadwal milik Anda dan jadwal petugas lain yang ingin ditukar.
                        Pertukaran baru diterapkan setelah penerima menyetujui dan sistem
                        berhasil memvalidasi kedua jadwal.
                    </p>
                </div>
            </div>
        </div>

        {{-- PERMINTAAN DIBUAT --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold text-slate-800">
                        Permintaan Saya
                    </h2>

                    <p class="text-xs text-slate-500">
                        Permintaan tukar jadwal yang Anda ajukan kepada petugas lain.
                    </p>
                </div>

                <span class="text-xs font-medium text-slate-500">
                    {{ $dibuatOlehSaya->total() }} permintaan
                </span>
            </div>

            @forelse ($dibuatOlehSaya as $item)
                @php
                    $snapshot = $item->snapshot_dinas_pemohon ?? [];
                    $tujuan = $item->snapshot_dinas_tujuan ?? [];

                    $statusStyle = match ($item->status) {
                        'menunggu' => 'bg-amber-50 text-amber-700 ring-amber-200',
                        'berhasil' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                        'ditolak' => 'bg-rose-50 text-rose-700 ring-rose-200',
                        'dibatalkan' => 'bg-slate-100 text-slate-600 ring-slate-200',
                        'gagal' => 'bg-red-50 text-red-700 ring-red-200',
                        default => 'bg-slate-100 text-slate-600 ring-slate-200',
                    };
                @endphp

                <div class="border-b border-slate-100 p-5 last:border-b-0">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusStyle }}">
                                    {{ ucfirst($item->status) }}
                                </span>

                                <span class="text-xs text-slate-400">
                                    Diajukan:
                                    {{ $item->diajukan_pada?->format('d M Y H:i') ?? '-' }}
                                </span>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                {{-- JADWAL PEMOHON --}}
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Jadwal Anda
                                    </p>

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $snapshot['tanggal'] ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $snapshot['jam_mulai'] ?? '-' }}
                                        –
                                        {{ $snapshot['jam_selesai'] ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $snapshot['jpl_nama'] ?? $snapshot['jpl'] ?? '-' }}
                                        ·
                                        {{ $snapshot['shift_nama'] ?? $snapshot['shift'] ?? '-' }}
                                    </p>
                                </div>

                                {{-- JADWAL TUJUAN --}}
                                <div class="rounded-xl bg-orange-50 p-3">
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-orange-500">
                                        Jadwal Tujuan
                                    </p>

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $tujuan['tanggal'] ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $tujuan['jam_mulai'] ?? '-' }}
                                        –
                                        {{ $tujuan['jam_selesai'] ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $tujuan['jpl_nama'] ?? $tujuan['jpl'] ?? '-' }}
                                        ·
                                        {{ $tujuan['shift_nama'] ?? $tujuan['shift'] ?? '-' }}
                                    </p>
                                </div>
                            </div>

                            <p class="mt-3 text-sm text-slate-600">
                                <span class="font-semibold">Penerima:</span>
                                {{ $item->penerima_nama_snapshot ?? $item->penerima?->name ?? 'Pengguna tidak tersedia' }}
                            </p>
                        </div>

                        <div class="flex shrink-0 gap-2">
                            <button
                                type="button"
                                @click="openDetail({{ $item->id }})"
                                class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                Detail
                            </button>

                            @if ($item->status === 'menunggu')
                                <button
                                    type="button"
                                    @click="cancelRequest({{ $item->id }})"
                                    class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50"
                                >
                                    Batalkan
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <i class="fas fa-exchange-alt text-xl"></i>
                    </div>

                    <p class="font-semibold text-slate-700">
                        Belum ada permintaan
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Permintaan tukar jadwal yang Anda buat akan muncul di sini.
                    </p>
                </div>
            @endforelse

            @if ($dibuatOlehSaya->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $dibuatOlehSaya->appends(request()->except('dibuat_page'))->links() }}
                </div>
            @endif
        </section>

        {{-- PERMINTAAN MASUK --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold text-slate-800">
                        Permintaan Masuk
                    </h2>

                    <p class="text-xs text-slate-500">
                        Permintaan dari petugas lain yang ditujukan kepada Anda.
                    </p>
                </div>

                <span class="text-xs font-medium text-slate-500">
                    {{ $dikirimKePadaSaya->total() }} permintaan
                </span>
            </div>

            @forelse ($dikirimKePadaSaya as $item)
                @php
                    $snapshot = $item->snapshot_dinas_pemohon ?? [];
                    $tujuan = $item->snapshot_dinas_tujuan ?? [];

                    $statusStyle = match ($item->status) {
                        'menunggu' => 'bg-amber-50 text-amber-700 ring-amber-200',
                        'berhasil' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                        'ditolak' => 'bg-rose-50 text-rose-700 ring-rose-200',
                        'dibatalkan' => 'bg-slate-100 text-slate-600 ring-slate-200',
                        'gagal' => 'bg-red-50 text-red-700 ring-red-200',
                        default => 'bg-slate-100 text-slate-600 ring-slate-200',
                    };
                @endphp

                <div class="border-b border-slate-100 p-5 last:border-b-0">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusStyle }}">
                                    {{ ucfirst($item->status) }}
                                </span>

                                <span class="text-xs text-slate-400">
                                    Diajukan:
                                    {{ $item->diajukan_pada?->format('d M Y H:i') ?? '-' }}
                                </span>
                            </div>

                            <p class="mb-3 text-sm text-slate-700">
                                <span class="font-semibold">
                                    {{ $item->pemohon_nama_snapshot ?? $item->pemohon?->name ?? 'Pengguna tidak tersedia' }}
                                </span>

                                mengajukan pertukaran jadwal kepada Anda.
                            </p>

                            <div class="grid gap-3 md:grid-cols-2">
                                {{-- JADWAL PEMOHON --}}
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Jadwal Pemohon
                                    </p>

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $snapshot['tanggal'] ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $snapshot['jam_mulai'] ?? '-' }}
                                        –
                                        {{ $snapshot['jam_selesai'] ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $snapshot['jpl_nama'] ?? $snapshot['jpl'] ?? '-' }}
                                        ·
                                        {{ $snapshot['shift_nama'] ?? $snapshot['shift'] ?? '-' }}
                                    </p>
                                </div>

                                {{-- JADWAL PENERIMA --}}
                                <div class="rounded-xl bg-orange-50 p-3">
                                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-orange-500">
                                        Jadwal Anda
                                    </p>

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $tujuan['tanggal'] ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $tujuan['jam_mulai'] ?? '-' }}
                                        –
                                        {{ $tujuan['jam_selesai'] ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $tujuan['jpl_nama'] ?? $tujuan['jpl'] ?? '-' }}
                                        ·
                                        {{ $tujuan['shift_nama'] ?? $tujuan['shift'] ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex shrink-0 gap-2">
                            <button
                                type="button"
                                @click="openDetail({{ $item->id }})"
                                class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                Detail
                            </button>

                            @if ($item->status === 'menunggu')
                                <button
                                    type="button"
                                    @click="openRespond({{ $item->id }})"
                                    class="rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-orange-600"
                                >
                                    Tanggapi
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <i class="fas fa-inbox text-xl"></i>
                    </div>

                    <p class="font-semibold text-slate-700">
                        Tidak ada permintaan masuk
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Permintaan dari petugas lain akan muncul di sini.
                    </p>
                </div>
            @endforelse

            @if ($dikirimKePadaSaya->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $dikirimKePadaSaya->appends(request()->except('masuk_page'))->links() }}
                </div>
            @endif
        </section>
    </div>

    {{-- MODAL CONTAINER --}}
    <div
        x-show="modalOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[80] flex items-center justify-center overflow-y-auto bg-slate-950/50 p-4"
        @keydown.escape.window="closeModal()"
        @click.self="closeModal()"
        @close-tukar-modal.window="closeModal()"
    >
        <div
            class="my-auto w-full max-w-3xl"
            x-transition
            id="tukar-jadwal-modal-content"
        >
            {{-- LOADING --}}
            <div
                class="rounded-2xl bg-white p-8 text-center shadow-xl"
                x-show="loading"
            >
                <i class="fas fa-spinner fa-spin text-2xl text-orange-500"></i>

                <p class="mt-3 text-sm text-slate-500">
                    Memuat...
                </p>
            </div>

            {{-- KONTEN MODAL --}}
            <div
                x-show="!loading"
                x-html="modalHtml"
            ></div>
        </div>
    </div>

    {{-- TOAST --}}
    <div
        x-show="toast.show"
        x-cloak
        x-transition
        class="fixed bottom-5 right-5 z-[100] max-w-sm rounded-xl px-4 py-3 text-sm font-medium text-white shadow-lg"
        :class="toast.type === 'error' ? 'bg-rose-600' : 'bg-emerald-600'"
        x-text="toast.message"
    ></div>
</div>

<script>
function tukarJadwalPage() {
    return {
        modalOpen: false,
        loading: false,
        modalHtml: '',

        toast: {
            show: false,
            type: 'success',
            message: ''
        },

        async openCreate() {
            this.modalOpen = true;
            this.loading = true;
            this.modalHtml = '';

            try {
                const response = await fetch(
                    @json(route('user.tukar-jadwal-dinas.create')),
                    {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error('Gagal memuat formulir pengajuan.');
                }

                this.modalHtml = await response.text();

                this.$nextTick(() => {
                    this.initModal();
                });
            } catch (error) {
                this.showToast(error.message, 'error');
                this.closeModal();
            } finally {
                this.loading = false;
            }
        },

        async openDetail(id) {
            this.modalOpen = true;
            this.loading = true;
            this.modalHtml = '';

            try {
                const url = @json(url('/tukar-jadwal-dinas')) + '/' + id;

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });

                if (!response.ok) {
                    throw new Error('Gagal memuat detail permintaan.');
                }

                this.modalHtml = await response.text();

                this.$nextTick(() => {
                    this.initModal();
                });
            } catch (error) {
                this.showToast(error.message, 'error');
                this.closeModal();
            } finally {
                this.loading = false;
            }
        },

        async openRespond(id) {
            await this.openDetail(id);
        },

        async cancelRequest(id) {
            if (!confirm('Apakah Anda yakin ingin membatalkan permintaan ini?')) {
                return;
            }

            try {
                const response = await fetch(
                    @json(url('/tukar-jadwal-dinas')) + '/' + id + '/cancel',
                    {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.ok) {
                    throw new Error(
                        result.message || 'Permintaan gagal dibatalkan.'
                    );
                }

                this.showToast(
                    result.message || 'Permintaan berhasil dibatalkan.'
                );

                setTimeout(() => {
                    window.location.reload();
                }, 800);

            } catch (error) {
                this.showToast(error.message, 'error');
            }
        },

        initModal() {
            const container = document.getElementById(
                'tukar-jadwal-modal-content'
            );

            if (window.Alpine && container) {
                Alpine.initTree(container);
            }
        },

        closeModal() {
            this.modalOpen = false;
            this.modalHtml = '';
            this.loading = false;
        },

        showToast(message, type = 'success') {
            this.toast = {
                show: true,
                type,
                message
            };

            setTimeout(() => {
                this.toast.show = false;
            }, 3500);
        }
    };
}
</script>
@endsection
