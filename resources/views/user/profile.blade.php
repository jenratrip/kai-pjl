@extends('layouts.user')

@section('title', 'Profil dan Keamanan Akun - Sistem PJL')

@section('content')
<main class="mx-auto w-full max-w-6xl space-y-5 px-4 py-5 sm:px-6 lg:py-7">
    <header>
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Akun PJL</p>
        <h1 class="mt-1 text-2xl font-bold text-[#1d2d50]">Profil dan Keamanan Akun</h1>
        <p class="mt-1 text-sm text-slate-600">Informasi identitas hanya dapat dilihat. Foto dan password dapat dikelola oleh pemilik akun.</p>
    </header>

    @if (session('success'))
        <p class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
            {{ session('success') }}
        </p>
    @endif

    <div class="grid items-start gap-5 lg:grid-cols-2">
      <div class="space-y-5">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                <h2 class="text-base font-semibold text-[#1d2d50]">Informasi Petugas</h2>
            </div>
            <dl class="grid gap-x-6 gap-y-3 p-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama lengkap</dt>
                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NIP / NIPKWT</dt>
                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $user->nip ?: '-' }}</dd>
            </div>
            @if ($dinasAktif?->jpl?->resort?->nama_resort)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Resort penugasan aktif</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $dinasAktif->jpl->resort->nama_resort }}</dd>
                </div>
            @endif
            @if ($dinasAktif?->jpl?->nama_jpl)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">JPL penugasan aktif</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $dinasAktif->jpl->nama_jpl }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status akun</dt>
                <dd class="mt-1">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-700' }}">
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </dd>
            </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
              <h2 class="text-base font-semibold text-[#1d2d50]">Foto Profil</h2>
          </div>
          <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
            <x-user-operasional.user-avatar :user="$user" size="h-24 w-24" class="text-2xl" />
            <div class="min-w-0 flex-1">
                <p class="text-sm text-slate-600">Foto ditampilkan pada header dan navigasi. Format JPEG, PNG, atau WebP, maksimal 2 MB.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button" data-profile-photo-open
                            class="rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#14213d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2">
                        {{ $user->photo ? 'Ganti Foto' : 'Unggah Foto' }}
                    </button>
                    @if ($user->photo)
                        <form method="POST" action="{{ route('user.profile.photo.destroy') }}"
                              onsubmit="return confirm('Hapus foto profil dan gunakan avatar inisial?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2">
                                Hapus Foto
                            </button>
                        </form>
                    @endif
                </div>
            </div>
          </div>
        </section>
      </div>

      <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
            <h2 class="text-base font-semibold text-[#1d2d50]">Keamanan Akun</h2>
            <p class="mt-1 text-sm text-slate-600">Gunakan password saat ini untuk mengonfirmasi perubahan.</p>
        </div>
        <form method="POST" action="{{ route('user.profile.password.update') }}" class="space-y-4 p-4">
            @csrf
            @method('PUT')
            <div>
                <label for="current_password" class="block text-sm font-semibold text-slate-700">Password saat ini</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/20">
                @error('current_password') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700">Password baru</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/20">
                    @error('password') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Konfirmasi password baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                           class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/20">
                </div>
            </div>
            <button type="submit" class="rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#14213d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1d2d50] focus-visible:ring-offset-2">
                Ubah Password
            </button>
        </form>
      </section>
    </div>
</main>

<div data-profile-photo-modal
     class="fixed inset-0 z-[1500] hidden items-center justify-center overflow-y-auto bg-slate-950/60 p-4 sm:p-6"
     @if ($errors->has('photo')) data-open-on-load="true" @endif
     role="dialog" aria-modal="true" aria-labelledby="profile-photo-title">
    <div data-profile-photo-panel class="my-auto max-h-[calc(100vh-2rem)] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div>
                <h2 id="profile-photo-title" class="text-lg font-semibold text-[#1d2d50]">Foto Profil</h2>
                <p class="mt-1 text-sm text-slate-600">Pilih foto dan periksa preview sebelum menyimpan.</p>
            </div>
            <button type="button" data-profile-photo-close aria-label="Tutup dialog foto profil"
                    class="rounded-lg px-2 py-1 text-2xl leading-none text-slate-500 hover:bg-slate-100">&times;</button>
        </div>
        <form method="POST" action="{{ route('user.profile.photo.update') }}"
              enctype="multipart/form-data" data-profile-photo-form class="space-y-4 px-5 py-5 sm:px-6">
            @csrf
            <div>
                <label for="profile-photo" class="block text-sm font-semibold text-slate-700">Unggah atau ganti foto</label>
                <input id="profile-photo" name="photo" type="file" required
                       accept="image/jpeg,image/png,image/webp" data-profile-photo-input
                       aria-describedby="profile-photo-help profile-photo-error"
                       class="mt-2 block w-full rounded-lg border border-slate-300 text-sm text-slate-700 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700">
                <p id="profile-photo-help" class="mt-1 text-xs text-slate-500">JPEG, PNG, atau WebP, maksimal 2 MB.</p>
                <p id="profile-photo-error" data-profile-photo-error class="mt-1 text-sm text-red-600" role="alert" hidden></p>
            </div>
            <div data-profile-photo-preview-wrap class="hidden">
                <p class="text-xs font-semibold text-slate-600">Preview foto baru</p>
                <img data-profile-photo-preview alt="Preview foto profil baru"
                     class="mt-2 h-28 w-28 rounded-full border border-slate-200 object-cover">
            </div>
            @error('photo') <p class="text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
            <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                <button type="button" data-profile-photo-cancel class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                <button type="submit" class="rounded-lg bg-[#1d2d50] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14213d]">Simpan Foto</button>
            </div>
        </form>
    </div>
</div>
@endsection
