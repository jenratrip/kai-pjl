@extends('layouts.admin')

@section('content')
<div class="container mx-auto max-w-4xl px-4 py-6">

    {{-- Header --}}
    <div class="mb-5">
        <a href="{{ route('admin.catatan-dinas.index') }}"
           class="text-sm text-blue-700 hover:underline">
            &larr; Kembali ke daftar Catatan Dinas
        </a>

        <h1 class="mt-3 text-2xl font-bold text-gray-900">
            Detail Catatan Dinas
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Periksa catatan petugas, kemudian berikan keputusan review.
        </p>
    </div>

    {{-- Notifikasi berhasil --}}
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Notifikasi validasi --}}
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-red-800">
            <p class="mb-2 font-semibold">
                Catatan belum dapat diproses.
            </p>

            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Informasi catatan --}}
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">
            Informasi Catatan
        </h2>

        <dl class="grid gap-4 sm:grid-cols-2">

            <div>
                <dt class="text-sm text-gray-500">Petugas</dt>
                <dd class="font-medium text-gray-900">
                    {{ $catatanDinas->user?->name ?? '—' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">NIP</dt>
                <dd class="text-gray-900">
                    {{ $catatanDinas->user?->nip ?? '—' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">Waktu Catatan</dt>
                <dd class="text-gray-900">
                    {{ $catatanDinas->waktu_catatan?->format('d/m/Y H:i') ?? '—' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">Shift</dt>
                <dd class="text-gray-900">
                    {{ $catatanDinas->dinas?->shift?->nama_shift ?? '—' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">Judul</dt>
                <dd class="text-gray-900">
                    {{ $catatanDinas->judul ?: 'Catatan Dinas' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">Kategori</dt>
                <dd class="text-gray-900">
                    {{ $catatanDinas->kategori ?: '—' }}
                </dd>
            </div>

        </dl>

        {{-- Isi catatan --}}
        <div class="mt-5">
            <h3 class="mb-2 text-sm font-medium text-gray-500">
                Isi Catatan
            </h3>

            <div class="whitespace-pre-line break-words rounded-lg bg-gray-50 p-4 text-gray-800">
                {{ $catatanDinas->isi_catatan ?: 'Tidak ada isi catatan.' }}
            </div>
        </div>

        {{-- Tindak lanjut --}}
        @if ($catatanDinas->tindak_lanjut)
            <div class="mt-4">
                <h3 class="mb-2 text-sm font-medium text-gray-500">
                    Tindak Lanjut
                </h3>

                <div class="whitespace-pre-line break-words rounded-lg bg-gray-50 p-4 text-gray-800">
                    {{ $catatanDinas->tindak_lanjut }}
                </div>
            </div>
        @endif

        {{-- Lampiran --}}
        @if ($catatanDinas->attachments && $catatanDinas->attachments->isNotEmpty())
            <div class="mt-5">
                <h3 class="mb-3 text-sm font-medium text-gray-500">
                    Lampiran Catatan
                </h3>

                <div class="space-y-2">
                    @foreach ($catatanDinas->attachments as $attachment)
                        @php
                            $path = $attachment->path
                                ?? $attachment->file_path
                                ?? $attachment->lampiran
                                ?? null;

                            $namaFile = $attachment->nama_file
                                ?? $attachment->original_name
                                ?? ($path ? basename($path) : 'Lampiran');
                        @endphp

                        @if ($path)
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 p-3">
                                <span class="break-all text-sm text-gray-700">
                                    {{ $namaFile }}
                                </span>

                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-sm font-medium text-blue-700 hover:underline">
                                    Lihat lampiran
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    {{-- Riwayat review --}}
    <section class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-lg font-semibold text-gray-900">
            Riwayat Review
        </h2>

        @php
            $statusReview = $catatanDinas->status_review ?? 'menunggu_review';

            $statusReviewClass = match ($statusReview) {
                'disetujui' => 'bg-green-100 text-green-800',
                'perlu_revisi' => 'bg-red-100 text-red-800',
                default => 'bg-amber-100 text-amber-800',
            };

            $statusReviewLabel = match ($statusReview) {
                'disetujui' => 'Disetujui',
                'perlu_revisi' => 'Perlu Revisi',
                default => 'Menunggu Review',
            };
        @endphp

        <div class="mb-4">
            <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $statusReviewClass }}">
                {{ $statusReviewLabel }}
            </span>
        </div>

        <div class="space-y-2 text-sm text-gray-600">
            <p>
                Ditinjau oleh:
                <span class="font-medium text-gray-900">
                    {{ $catatanDinas->ditinjauOleh?->name ?? 'Belum ditinjau' }}
                </span>
            </p>

            <p>
                Waktu review:
                <span class="font-medium text-gray-900">
                    {{ $catatanDinas->ditinjau_pada?->format('d/m/Y H:i') ?? '—' }}
                </span>
            </p>
        </div>

        @if ($catatanDinas->catatan_revisi)
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4">
                <h3 class="font-semibold text-red-800">
                    Catatan Revisi
                </h3>

                <p class="mt-1 whitespace-pre-line break-words text-sm text-red-700">{{ $catatanDinas->catatan_revisi }}</p>
            </div>
        @endif
    </section>

    {{-- Form keputusan Admin --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="mb-2 text-lg font-semibold text-gray-900">
            Keputusan Admin
        </h2>

        <p class="mb-5 text-sm text-gray-500">
            Setujui catatan jika sudah sesuai, atau minta revisi dengan memberikan alasan yang jelas.
        </p>

        <form method="POST"
              action="{{ route('admin.catatan-dinas.update', $catatanDinas->id) }}">
            @csrf
            @method('PATCH')

            {{-- Keputusan --}}
            <div class="mb-4">
                <label for="catatan_status_review"
                       class="mb-2 block text-sm font-medium text-gray-700">
                    Hasil Review
                </label>

                <select id="catatan_status_review"
                        name="status_review"
                        required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">

                    <option value="">Pilih keputusan</option>

                    <option value="disetujui"
                        @selected(old('status_review', $catatanDinas->status_review) === 'disetujui')>
                        ACC / Setujui
                    </option>

                    <option value="perlu_revisi"
                        @selected(old('status_review', $catatanDinas->status_review) === 'perlu_revisi')>
                        Minta Revisi
                    </option>
                </select>
            </div>

            {{-- Catatan revisi --}}
            <div class="mb-5">
                <label for="catatan_catatan_revisi"
                       class="mb-2 block text-sm font-medium text-gray-700">
                    Catatan Revisi
                    <span class="font-normal text-gray-500">
                        (wajib jika meminta revisi)
                    </span>
                </label>

                <textarea id="catatan_catatan_revisi"
                          name="catatan_revisi"
                          rows="4"
                          maxlength="5000"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                          placeholder="Jelaskan bagian yang perlu diperbaiki...">{{ old('catatan_revisi', $catatanDinas->catatan_revisi) }}</textarea>

                <p class="mt-1 text-xs text-gray-500">
                    Maksimal 5.000 karakter.
                </p>
            </div>

            {{-- Tombol aksi --}}
            <div class="flex flex-wrap gap-3">
                <button type="submit"
                        class="rounded-lg bg-gray-900 px-5 py-2.5 font-medium text-white transition hover:bg-gray-700">
                    Simpan Keputusan
                </button>

                <a href="{{ route('admin.catatan-dinas.index') }}"
                   class="rounded-lg border border-gray-300 px-5 py-2.5 font-medium text-gray-700 transition hover:bg-gray-50">
                    Kembali
                </a>
            </div>
        </form>
    </section>

</div>
@endsection
