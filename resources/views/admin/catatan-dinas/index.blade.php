@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">Review Catatan Dinas</h1>
            <p class="mt-1 text-sm text-gray-500">
                Periksa catatan yang dikirim petugas PJL.
            </p>
        </div>

        <div class="rounded-lg border px-4 py-3">
            <div class="text-sm text-gray-500">Menunggu review</div>
            <div class="text-2xl font-bold">
                {{ $totalMenungguReview }}
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" class="mb-5 flex flex-wrap gap-3">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Cari petugas atau isi catatan..."
            class="min-w-56 rounded-lg border px-3 py-2"
        >

        <select name="status_review" class="rounded-lg border px-3 py-2">
            <option value="">Semua status review</option>
            <option value="menunggu_review" @selected(request('status_review') === 'menunggu_review')>
                Menunggu review
            </option>
            <option value="disetujui" @selected(request('status_review') === 'disetujui')>
                Disetujui
            </option>
            <option value="perlu_revisi" @selected(request('status_review') === 'perlu_revisi')>
                Perlu revisi
            </option>
        </select>

        <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-white">
            Filter
        </button>

        <a href="{{ route('admin.catatan-dinas.index') }}"
           class="rounded-lg border px-4 py-2">
            Reset
        </a>
    </form>

    <div class="overflow-x-auto rounded-xl border">
        <table class="min-w-full divide-y">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm">Waktu</th>
                    <th class="px-4 py-3 text-left text-sm">Petugas</th>
                    <th class="px-4 py-3 text-left text-sm">Catatan</th>
                    <th class="px-4 py-3 text-left text-sm">Status review</th>
                    <th class="px-4 py-3 text-left text-sm">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($catatanDinas as $item)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            {{ $item->waktu_catatan?->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium">
                                {{ $item->user?->name ?? 'Petugas tidak tersedia' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $item->user?->nip ?? '—' }}
                            </div>
                        </td>
                        <td class="max-w-md px-4 py-3">
                            <div class="font-medium">
                                {{ $item->judul ?: 'Catatan Dinas' }}
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ \Illuminate\Support\Str::limit($item->isi_catatan, 100) }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            @switch($item->status_review)
                                @case('disetujui')
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-green-800">
                                        Disetujui
                                    </span>
                                    @break
                                @case('perlu_revisi')
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-red-800">
                                        Perlu revisi
                                    </span>
                                    @break
                                @default
                                    <span class="rounded-full bg-yellow-100 px-3 py-1 text-yellow-800">
                                        Menunggu review
                                    </span>
                            @endswitch
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <a href="{{ route('admin.catatan-dinas.show', $item->id) }}"
                               class="font-medium text-blue-700 hover:underline">
                                Periksa detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            Belum ada Catatan Dinas yang sesuai filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $catatanDinas->links() }}
    </div>
</div>
@endsection
