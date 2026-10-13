<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Inventaris | KAI PJL</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <header class="bg-[#0b1f3a] px-6 py-5 text-white">
        <div class="mx-auto max-w-4xl">
            <h1 class="text-xl font-bold">KAI PJL</h1>
            <p class="text-sm text-slate-300">Admin · Master Inventaris</p>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-6 py-8">
        <a href="{{ route('admin.inventaris.index') }}"
           class="text-sm font-medium text-blue-700 hover:underline">
            ← Kembali ke inventaris
        </a>

        <div class="mb-6 mt-4">
            <h2 class="text-2xl font-bold">Edit Inventaris</h2>
            <p class="mt-1 text-sm text-slate-500">
                Perbarui informasi {{ $inventaris->nama_barang }}.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.inventaris.update', $inventaris) }}"
              method="POST"
              class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="kode_barang" class="mb-2 block text-sm font-semibold">
                        Kode Barang
                    </label>
                    <input id="kode_barang" name="kode_barang"
                           value="{{ old('kode_barang', $inventaris->kode_barang) }}"
                           class="w-full rounded-lg border border-slate-300 px-4 py-3">
                </div>

                <div>
                    <label for="nama_barang" class="mb-2 block text-sm font-semibold">
                        Nama Barang *
                    </label>
                    <input id="nama_barang" name="nama_barang" required
                           value="{{ old('nama_barang', $inventaris->nama_barang) }}"
                           class="w-full rounded-lg border border-slate-300 px-4 py-3">
                </div>

                <div>
                    <label for="kategori" class="mb-2 block text-sm font-semibold">
                        Kategori *
                    </label>
                    <select id="kategori" name="kategori" required
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3">
                        <option value="perangkat_pengamanan"
                            @selected(old('kategori', $inventaris->kategori) === 'perangkat_pengamanan')>
                            Perangkat Pengamanan
                        </option>
                        <option value="alat_pendukung"
                            @selected(old('kategori', $inventaris->kategori) === 'alat_pendukung')>
                            Alat Pendukung
                        </option>
                        <option value="inventaris_pjl"
                            @selected(old('kategori', $inventaris->kategori) === 'inventaris_pjl')>
                            Inventaris PJL
                        </option>
                    </select>
                </div>

                <div>
                    <label for="jumlah" class="mb-2 block text-sm font-semibold">
                        Jumlah *
                    </label>
                    <input id="jumlah" name="jumlah" type="number" min="1" required
                           value="{{ old('jumlah', $inventaris->jumlah) }}"
                           class="w-full rounded-lg border border-slate-300 px-4 py-3">
                </div>

                <div>
                    <label for="lokasi" class="mb-2 block text-sm font-semibold">
                        Lokasi *
                    </label>
                    <input id="lokasi" name="lokasi" required
                           value="{{ old('lokasi', $inventaris->lokasi) }}"
                           class="w-full rounded-lg border border-slate-300 px-4 py-3">
                </div>

                <div>
                    <label for="jpl_id" class="mb-2 block text-sm font-semibold">
                        Penempatan JPL
                    </label>
                    <select id="jpl_id" name="jpl_id"
                            class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3">
                        <option value="">Tidak ditetapkan</option>
                        @foreach ($jpl as $pos)
                            <option value="{{ $pos->id }}"
                                @selected((string) old('jpl_id', $inventaris->jpl_id) === (string) $pos->id)>
                                {{ $pos->kode_jpl }} — {{ $pos->nama_jpl }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="keterangan" class="mb-2 block text-sm font-semibold">
                    Keterangan
                </label>
                <textarea id="keterangan" name="keterangan" rows="4"
                          class="w-full rounded-lg border border-slate-300 px-4 py-3">{{ old('keterangan', $inventaris->keterangan) }}</textarea>
            </div>

            <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-4">
                <input type="checkbox" name="is_active" value="1"
                       @checked((bool) old('is_active', $inventaris->is_active))
                       class="h-4 w-4 rounded border-slate-300">
                <span class="text-sm font-medium">Inventaris aktif</span>
            </label>

            <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="{{ route('admin.inventaris.index') }}"
                   class="rounded-lg border border-slate-300 px-5 py-3 font-semibold">
                    Batal
                </a>
                <button type="submit"
                        class="rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </main>
</body>
</html>
