@extends('layouts.admin')

@section('title', 'Edit Jadwal KA')

@section('content')
<div class="p-6">

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.jadwal-ka.index') }}"
           class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50">
            ←
        </a>

        <div>
            <h1 class="text-xl font-bold text-[#1d2d50]">Edit Jadwal KA</h1>
            <p class="mt-1 text-sm text-gray-500">
                Perbarui informasi jadwal kereta api.
            </p>
        </div>
    </div>

    <div class="max-w-3xl overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <form action="{{ route('admin.jadwal-ka.update', $jadwalKa->id) }}"
            method="POST">
            @include('admin.jadwal-ka._form', [
                'jadwalKa' => $jadwalKa,
                'keretaApi' => $keretaApi,
                'jpls' => $jpls,
            ])
        </form>
    </div>

</div>
@endsection