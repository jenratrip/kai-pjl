<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Serah Terima Dinas PJL</title>
    <link rel="stylesheet" href="{{ asset('css/user/serah-terima-dinas.css') }}">
</head>
<body>
<div
    id="serahTerimaConfig"
    hidden
    data-server-time="{{ now('Asia/Jakarta')->toIso8601String() }}"
    data-store-url="{{ route('user.serah-terima-dinas.store') }}"
></div>
    @include('user.serah-terima-dinas.partials.header')

    <main class="main">
        <div class="page-card">
            @include('user.serah-terima-dinas.partials.form')
            @include('user.serah-terima-dinas.partials.inventaris')
            @include('user.serah-terima-dinas.partials.catatan-ppka')
            @include('user.serah-terima-dinas.partials.tanda-tangan')
            @include('user.serah-terima-dinas.partials.actions')
        </div>
    </main>

    <script src="{{ asset('js/user/serah-terima-dinas.js') }}" defer></script>
</body>
</html>