@extends('layouts.user-operasional')

@section('title', 'Catatan Dinas - Sistem PJL')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user/catatan-dinas.css') }}">
@endpush

@section('content')
    {{-- Layout sudah punya <main>, jadi tidak perlu <main> lagi di sini. --}}
    @include('user.catatan-dinas._section', [
        'catatan'      => $catatan,
        'daftarDinas'  => $daftarDinas,
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