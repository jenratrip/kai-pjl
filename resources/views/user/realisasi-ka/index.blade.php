@extends('layouts.user')

@section('title', 'Realisasi KA - Sistem PJL')

@section('content')
<div class="realisasi-ka-page w-full min-w-0 px-2 py-6 sm:px-5 lg:px-8 lg:py-7">
    <section class="mx-auto w-full max-w-[1700px] rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-6 lg:p-8">

        @include('user.realisasi-ka.partials.info-dinas')
        @include('user.realisasi-ka.partials.filter-jadwal')
        @include('user.realisasi-ka.partials.peringatan-awas')
        @include('user.realisasi-ka.partials.ringkasan')
        @include('user.realisasi-ka.partials.keterangan-warna')
        @include('user.realisasi-ka.partials.tabel-realisasi')
        @include('user.realisasi-ka.partials.form-ka-manual')
        @include('user.realisasi-ka.partials.aksi-realisasi')

    </section>

    @include('user.realisasi-ka.partials.catatan-dinas')
</div>
@endsection

@push('scripts')
    @include('user.realisasi-ka.scripts.config')

    <script>
        /*
         * Semua file JavaScript di bawah sengaja di-include ke dalam SATU
         * tag script agar const, let, Map, Set, dan fungsi tetap berada
         * pada lexical scope yang sama seperti file awal.
         */
        @include('user.realisasi-ka.scripts.01-data-utilitas-filter')
        @include('user.realisasi-ka.scripts.02-status-awas')
        @include('user.realisasi-ka.scripts.03-tabel-dan-state')
        @include('user.realisasi-ka.scripts.04-interaksi-ringkasan')
        @include('user.realisasi-ka.scripts.05-penyimpanan')
        @include('user.realisasi-ka.scripts.06-cetak-dan-ka-manual')
        @include('user.realisasi-ka.scripts.07-catatan-dinas')
        @include('user.realisasi-ka.scripts.08-inisialisasi')
    </script>
@endpush