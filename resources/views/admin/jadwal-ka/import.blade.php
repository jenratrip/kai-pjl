@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-5xl space-y-6 px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <div class="mb-2 flex items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('admin.jadwal-ka.index') }}"
                   class="transition hover:text-blue-700">
                    Jadwal Perjalanan KA
                </a>
                <span>/</span>
                <span class="text-slate-700">Import Excel</span>
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-slate-800">
                Import Jadwal Perjalanan KA
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Unggah workbook Excel untuk membaca jadwal dari setiap sheet JPL.
            </p>
        </div>

        <a href="{{ route('admin.jadwal-ka.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300
                  bg-white px-4 py-2.5 text-sm font-semibold text-slate-700
                  transition hover:bg-slate-50">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="h-4 w-4"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Jadwal
        </a>
    </div>

    {{-- Notifikasi sukses --}}
    @if (session('success'))
        <div class="flex items-start gap-3 rounded-xl border border-green-200
                    bg-green-50 p-4 text-green-800">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="mt-0.5 h-5 w-5 shrink-0"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M5 13l4 4L19 7" />
            </svg>

            <div class="text-sm">
                <p class="font-semibold">Berhasil</p>
                <p class="mt-1">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Notifikasi error --}}
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="mt-0.5 h-5 w-5 shrink-0"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>

                <div>
                    <p class="font-semibold">Import belum dapat dilanjutkan.</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Form import --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="font-semibold text-slate-800">
                        Unggah Workbook Excel
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Pilih file Excel yang akan diperiksa sebelum diimpor.
                    </p>
                </div>

                <form
                    id="import-form"
                    action="{{ route('admin.jadwal-ka.import.preview') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="space-y-5 p-6"
                >
                    @csrf

                    {{-- Dropzone --}}
                    <div>
                        <label for="file"
                               class="mb-3 block text-sm font-semibold text-slate-700">
                            File workbook Excel
                        </label>

                        <div
                            id="dropzone"
                            class="group relative cursor-pointer rounded-2xl border-2 border-dashed
                                   border-slate-300 bg-slate-50 px-5 py-10 text-center
                                   transition hover:border-blue-400 hover:bg-blue-50/50"
                        >
                            <input
                                id="file"
                                name="file"
                                type="file"
                                accept=".xlsx,.xls"
                                required
                                class="sr-only"
                            >

                            <div id="upload-placeholder">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center
                                            rounded-2xl bg-blue-100 text-blue-700
                                            transition group-hover:scale-105">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="h-8 w-8"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor"
                                         stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M12 16V4m0 0L7 9m5-5l5 5M4 16.5v2A1.5 1.5 0 005.5 20h13a1.5 1.5 0 001.5-1.5v-2" />
                                    </svg>
                                </div>

                                <p class="mt-5 text-base font-semibold text-slate-800">
                                    Seret dan lepas file Excel di sini
                                </p>

                                <p class="mt-2 text-sm text-slate-500">
                                    atau klik area ini untuk memilih file dari komputer
                                </p>

                                <div class="mt-4 flex flex-wrap justify-center gap-2">
                                    <span class="rounded-md border border-green-200 bg-green-50
                                                 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        .XLSX
                                    </span>
                                    <span class="rounded-md border border-green-200 bg-green-50
                                                 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        .XLS
                                    </span>
                                    <span class="rounded-md border border-slate-200 bg-white
                                                 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        Maks. 20 MB
                                    </span>
                                </div>
                            </div>

                            {{-- File terpilih --}}
                            <div id="selected-file" class="hidden">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center
                                            rounded-xl bg-green-100 text-green-700">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="h-7 w-7"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor"
                                         stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M7 3.75h7l5 5V20a.75.75 0 01-.75.75h-10.5A.75.75 0 017 20V3.75z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M14 3.75v5h5M9 14h6m-6 3h6" />
                                    </svg>
                                </div>

                                <p id="file-name"
                                   class="mx-auto mt-4 max-w-full break-all text-sm font-semibold
                                          text-slate-800">
                                </p>

                                <p id="file-size" class="mt-1 text-xs text-slate-500"></p>

                                <p class="mt-3 text-xs font-medium text-green-700">
                                    File siap diproses
                                </p>

                                <button
                                    id="remove-file"
                                    type="button"
                                    class="mt-3 rounded-lg border border-slate-300 bg-white
                                           px-3 py-1.5 text-xs font-semibold text-slate-600
                                           transition hover:bg-slate-100"
                                >
                                    Ganti file
                                </button>
                            </div>
                        </div>

                        <p id="file-error"
                           class="mt-2 hidden text-sm font-medium text-red-600"
                           role="alert">
                        </p>
                    </div>

                    {{-- Informasi --}}
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 class="mt-0.5 h-5 w-5 shrink-0 text-blue-700"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor"
                                 stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 110-18 9 9 0 010 18z" />
                            </svg>

                            <div class="text-sm text-blue-900">
                                <p class="font-semibold">Perhatian sebelum import</p>
                                <p class="mt-1 leading-6">
                                    Nama setiap sheet harus sama dengan nilai
                                    <code class="rounded bg-white px-1 py-0.5">kode_jpl</code>
                                    yang sudah terdaftar. Sistem tidak membuat data JPL baru
                                    saat import.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Format kolom --}}
                    <div class="rounded-xl border border-slate-200 p-4">
                        <h3 class="text-sm font-semibold text-slate-800">
                            Format kolom Excel
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Sistem mencari baris header secara otomatis.
                        </p>

                        <div class="mt-4 space-y-3 text-sm">
                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide
                                          text-slate-500">
                                    Kolom wajib
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <code class="rounded-md bg-slate-100 px-2.5 py-1.5
                                                 text-slate-700">NOMOR KA</code>
                                    <code class="rounded-md bg-slate-100 px-2.5 py-1.5
                                                 text-slate-700">JAM</code>
                                </div>
                            </div>

                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide
                                          text-slate-500">
                                    Kolom opsional
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <code class="rounded-md bg-slate-100 px-2.5 py-1.5
                                                 text-slate-700">NAMA KA</code>
                                    <code class="rounded-md bg-slate-100 px-2.5 py-1.5
                                                 text-slate-700">DARI</code>
                                    <code class="rounded-md bg-slate-100 px-2.5 py-1.5
                                                 text-slate-700">KE</code>
                                    <code class="rounded-md bg-slate-100 px-2.5 py-1.5
                                                 text-slate-700">KETERANGAN</code>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 pt-3 text-slate-600">
                                <p>
                                    <span class="font-medium text-slate-700">Format jam:</span>
                                    <code>00:29</code>, <code>00.29</code>, atau
                                    <code>00:29:00</code>.
                                </p>
                                <p class="mt-2">
                                    Jadwal yang sama (KA, JPL, dan jam identik) akan dilewati,
                                    bukan digandakan.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol submit --}}
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5
                                sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500">
                            Data akan diperiksa pada halaman preview sebelum dikonfirmasi.
                        </p>

                        <button
                            id="submit-btn"
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl
                                   bg-blue-700 px-5 py-3 text-sm font-semibold text-white
                                   shadow-sm transition hover:bg-blue-800
                                   focus:outline-none focus:ring-4 focus:ring-blue-200
                                   disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <svg id="submit-icon"
                                 xmlns="http://www.w3.org/2000/svg"
                                 class="h-4 w-4"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor"
                                 stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 5l7 7-7 7" />
                            </svg>

                            <span id="submit-text">Baca dan Preview Excel</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel panduan --}}
        <aside class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-800">
                    Alur Import
                </h2>

                <ol class="mt-5 space-y-5">
                    <li class="flex gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center
                                     rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                            1
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                Unggah Excel
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Pilih workbook yang berisi sheet jadwal JPL.
                            </p>
                        </div>
                    </li>

                    <li class="flex gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center
                                     rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                            2
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                Periksa Preview
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Periksa hasil pembacaan, data yang valid, dan duplikasi.
                            </p>
                        </div>
                    </li>

                    <li class="flex gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center
                                     rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                            3
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                Konfirmasi Import
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Lanjutkan dari halaman preview untuk menyimpan data.
                            </p>
                        </div>
                    </li>
                </ol>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>

                    <div>
                        <h3 class="text-sm font-semibold text-amber-900">
                            Periksa nama sheet
                        </h3>
                        <p class="mt-1 text-xs leading-5 text-amber-800">
                            Pastikan kode JPL pada nama sheet sesuai dengan data JPL
                            yang sudah terdaftar di sistem.
                        </p>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <p class="pb-2 text-center text-xs text-slate-400">
        Jadwal Perjalanan KA · Sistem Informasi PJL
    </p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('import-form');
    const input = document.getElementById('file');
    const dropzone = document.getElementById('dropzone');
    const placeholder = document.getElementById('upload-placeholder');
    const selectedFile = document.getElementById('selected-file');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const fileError = document.getElementById('file-error');
    const removeButton = document.getElementById('remove-file');
    const submitButton = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const submitIcon = document.getElementById('submit-icon');

    const maxSize = 20 * 1024 * 1024;
    const allowedExtensions = ['xls', 'xlsx'];
    let submitting = false;

    function showError(message) {
        fileError.textContent = message;
        fileError.classList.remove('hidden');
        dropzone.classList.add('border-red-400', 'bg-red-50');
    }

    function clearError() {
        fileError.textContent = '';
        fileError.classList.add('hidden');
        dropzone.classList.remove('border-red-400', 'bg-red-50');
    }

    function formatSize(bytes) {
        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }

        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function renderFile(file) {
        clearError();

        if (!file) {
            return false;
        }

        const extension = file.name.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(extension)) {
            input.value = '';
            showError('Format file tidak didukung. Pilih file .xlsx atau .xls.');
            return false;
        }

        if (file.size > maxSize) {
            input.value = '';
            showError('Ukuran file melebihi batas 20 MB. Silakan pilih file yang lebih kecil.');
            return false;
        }

        if (file.size === 0) {
            input.value = '';
            showError('File kosong dan tidak dapat diproses.');
            return false;
        }

        fileName.textContent = file.name;
        fileSize.textContent = formatSize(file.size);

        placeholder.classList.add('hidden');
        selectedFile.classList.remove('hidden');

        dropzone.classList.remove('border-slate-300', 'bg-slate-50');
        dropzone.classList.add('border-green-300', 'bg-green-50/50');

        return true;
    }

    function resetFile() {
        input.value = '';
        placeholder.classList.remove('hidden');
        selectedFile.classList.add('hidden');

        dropzone.classList.remove(
            'border-green-300',
            'bg-green-50/50',
            'border-blue-400',
            'bg-blue-50/50'
        );

        dropzone.classList.add('border-slate-300', 'bg-slate-50');
        clearError();
    }

    // Klik area unggah untuk membuka pemilih file.
    dropzone.addEventListener('click', function (event) {
        if (
            event.target.closest('#remove-file') ||
            event.target.closest('button')
        ) {
            return;
        }

        input.click();
    });

    // File dipilih melalui dialog.
    input.addEventListener('change', function () {
        if (input.files.length > 0) {
            renderFile(input.files[0]);
        }
    });

    // Efek visual saat file diseret ke area unggah.
    ['dragenter', 'dragover'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            event.stopPropagation();

            dropzone.classList.add('border-blue-400', 'bg-blue-50/50');
        });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            event.stopPropagation();

            dropzone.classList.remove('border-blue-400', 'bg-blue-50/50');
        });
    });

    // Mendukung drag-and-drop file dari komputer.
    dropzone.addEventListener('drop', function (event) {
        const files = event.dataTransfer.files;

        if (!files || files.length === 0) {
            return;
        }

        try {
            const transfer = new DataTransfer();
            transfer.items.add(files[0]);
            input.files = transfer.files;

            renderFile(input.files[0]);
        } catch (error) {
            resetFile();
            showError('Browser tidak dapat membaca file yang diseret. Silakan pilih file melalui tombol pemilih.');
        }
    });

    // Memilih ulang file.
    removeButton.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        resetFile();
        input.click();
    });

    // Validasi sebelum dikirim ke controller.
    form.addEventListener('submit', function (event) {
        if (submitting) {
            event.preventDefault();
            return;
        }

        if (!input.files || input.files.length === 0) {
            event.preventDefault();
            showError('Silakan pilih file Excel terlebih dahulu.');
            return;
        }

        if (!renderFile(input.files[0])) {
            event.preventDefault();
            return;
        }

        submitting = true;
        submitButton.disabled = true;
        submitText.textContent = 'Membaca file...';

        submitIcon.classList.add('animate-spin');
        submitIcon.innerHTML =
            '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.364-6.364l-2.121 2.121M7.757 16.243l-2.121 2.121m12.728 0l-2.121-2.121M7.757 7.757L5.636 5.636" />';
    });
});
</script>
@endsection
