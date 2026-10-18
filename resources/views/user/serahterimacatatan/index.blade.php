
@extends('layouts.admin')

@section('title', 'Serah Terima Dinas PJL')

@section('content')
<div class="min-h-screen bg-slate-100 p-4 md:p-6">
    <div class="mx-auto max-w-7xl space-y-5">

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <p class="mb-1 font-bold">Periksa kembali formulir:</p>
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!$dinas)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6">
                <h1 class="text-xl font-bold text-[#1d2d50]">Serah Terima Dinas PJL</h1>
                <p class="mt-2 text-sm text-amber-900">
                    Belum ada dinas aktif untuk akun ini. Data serah terima hanya dapat
                    dibuat ketika dinas berstatus berlangsung.
                </p>
            </div>
        @else
            @include('user.serahterimacatatan.partials.header')
            @include('user.serahterimacatatan.partials.ka-batal')
            @include('user.serahterimacatatan.partials.ka-berjalan')
            @include('user.serahterimacatatan.partials.ka-belum-lewat')
            @include('user.serahterimacatatan.partials.alat-pengaman')
            @include('user.serahterimacatatan.partials.inventaris')
            @include('user.serahterimacatatan.partials.catatan')
            @include('user.serahterimacatatan.partials.petugas')
            @include('user.serahterimacatatan.partials.tanda-tangan')
            @include('user.serahterimacatatan.partials.actions')
        @endif
    </div>
</div>
@endsection
