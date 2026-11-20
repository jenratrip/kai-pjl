@extends('layouts.user')

@section('title', 'Catatan Dinas - Sistem PJL')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user/catatan-dinas.css') }}">
@endpush

@section('content')
    {{-- Shell bersama menyediakan area konten; section ini tetap memakai struktur lamanya. --}}
    @include('user.catatan-dinas._section', [
        'catatan'      => $catatan,
        'daftarDinas'  => $daftarDinas,
        'dinasAktif'   => $dinasAktif ?? null,
        'redirectTo'   => '',
    ])
@endsection

@push('scripts')
    <script>
        window.catatanDinasConfig = {
            oldTanggal: @json((string) old('tanggal', '')),
            oldShiftId: @json((string) old('shift_id', '')),
            hasErrors: @json($errors->any())
        };
    </script>
    <script src="{{ asset('js/user/catatan-dinas.js') }}" defer></script>
@endpush