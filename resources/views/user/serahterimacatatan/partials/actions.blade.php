@php
    $modeEditForm = !empty($modeEdit) && !empty($serahTerima);

    $ringkasanLama = old(
        'ringkasan_text',
        data_get($serahTerima, 'ringkasan.catatan_serah_terima', '')
    );

    $adaTtdPenyerahLama = !empty($serahTerima?->tanda_tangan_penyerah);
@endphp

<form
    id="serahTerimaSubmitForm"
    method="POST"
    action="{{ route('user.serah-terima-dinas.store') }}"
    class="space-y-4"
    data-ada-ttd-lama="{{ $adaTtdPenyerahLama ? '1' : '0' }}"
>
    @csrf

    {{-- Hanya terisi saat mengedit dari riwayat --}}
    @if ($modeEditForm)
        <input type="hidden" name="serah_terima_id" value="{{ $serahTerima->id }}">
    @endif

    {{-- Data yang dikirim ke controller --}}
    <input
        type="hidden"
        name="ringkasan"
        id="ringkasanInput"
    >

    <input
        type="hidden"
        name="tanda_tangan_penyerah"
        id="signatureSubmitInput"
    >

    {{-- set = tanda tangan baru dikirim, keep = pakai tanda tangan lama --}}
    <input
        type="hidden"
        name="tanda_tangan_penyerah_action"
        id="signatureActionInput"
        value="keep"
    >

    {{-- Ringkasan tambahan --}}
    <div>
        <label
            for="ringkasan_text"
            class="mb-1 block text-sm font-semibold text-slate-700"
        >
            Ringkasan serah terima
        </label>

        <textarea
            id="ringkasan_text"
            rows="3"
            required
            placeholder="Ringkas kondisi dinas dan hal yang perlu diketahui penerima."
            class="w-full rounded-lg border border-slate-300 bg-white p-3 text-sm"
        >{{ $ringkasanLama }}</textarea>
    </div>

    {{-- Pesan kesalahan dari pengecekan di browser --}}
    <p
        id="serahTerimaClientError"
        class="hidden rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 print:hidden"
        role="alert"
    ></p>

    {{-- Tombol aksi --}}
    <div class="flex flex-wrap items-center justify-end gap-3 print:hidden">
        <button
            type="button"
            onclick="window.print()"
            class="rounded-lg border border-[#1d2d50] bg-white px-4 py-2.5 text-sm font-semibold text-[#1d2d50] transition hover:bg-slate-50"
        >
            Cetak
        </button>

        <button
            type="submit"
            id="submitSerahTerima"
            class="rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#263d68] disabled:cursor-not-allowed disabled:opacity-50"
        >
            Simpan serah terima
        </button>
    </div>

    <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-900">
        Pastikan informasi KA, alat pengamanan, inventaris, catatan dinas, dan
        tanda tangan sudah diperiksa sebelum menyimpan serah terima. Setelah
        disimpan, serah terima langsung dikirim ke admin dan tercatat di
        riwayat. Selama belum disetujui admin, serah terima masih bisa dibuka
        dan diedit dari riwayat.
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('serahTerimaSubmitForm');

    if (!form) {
        return;
    }

    const submitButton = document.getElementById('submitSerahTerima');
    const errorBox = document.getElementById('serahTerimaClientError');
    const labelTombol = submitButton ? submitButton.textContent.trim() : '';

    // Field tambahan yang dibaca controller. Jika field-nya berada di luar
    // <form> ini (bagian petugas, catatan penting, dll.), nilainya disalin
    // otomatis saat submit.
    const FIELD_TAMBAHAN = [
        'penerima_user_id',
        'nama_penerima',
        'nip_penerima',
        'nip_penyerah',
        'ppka',
        'catatan_penting'
    ];

    let submitting = false;

    function tampilkanError(pesan, target) {
        if (errorBox) {
            errorBox.textContent = pesan;
            errorBox.classList.remove('hidden');
        } else {
            alert(pesan);
        }

        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });

            if (typeof target.focus === 'function') {
                target.focus({ preventScroll: true });
            }
        }
    }

    function bersihkanError() {
        if (errorBox) {
            errorBox.textContent = '';
            errorBox.classList.add('hidden');
        }
    }

    function cariField(nama) {
        const dalamForm = form.elements.namedItem(nama);

        if (dalamForm && !dalamForm.dataset?.generated) {
            return dalamForm;
        }

        return document.querySelector('[name="' + nama + '"]')
            || document.getElementById(nama);
    }

    function nilaiField(nama) {
        const el = cariField(nama);

        return el && typeof el.value === 'string' ? el.value.trim() : '';
    }

    function resetTombol() {
        submitting = false;

        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = labelTombol;
        }
    }

    // Tombol kembali di browser (bfcache) tidak boleh meninggalkan tombol terkunci.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            resetTombol();
        }
    });

    form.addEventListener('submit', function (event) {
        if (submitting) {
            event.preventDefault();
            return;
        }

        bersihkanError();

        const signatureInput = document.getElementById('signatureData');
        const signatureSubmitInput = document.getElementById('signatureSubmitInput');
        const signatureActionInput = document.getElementById('signatureActionInput');
        const ringkasanInput = document.getElementById('ringkasanInput');
        const ringkasanText = document.getElementById('ringkasan_text');

        if (!ringkasanText || !ringkasanInput || !signatureSubmitInput || !signatureActionInput) {
            event.preventDefault();
            tampilkanError('Form serah terima tidak lengkap. Muat ulang halaman dan coba lagi.');
            return;
        }

        // 1. Nama penerima wajib (controller: nama_penerima required).
        if (!nilaiField('nama_penerima')) {
            event.preventDefault();
            tampilkanError(
                'Isi nama penerima serah terima terlebih dahulu.',
                cariField('nama_penerima')
            );
            return;
        }

        // 2. Semua inventaris harus sudah diperiksa (lengkap / tidak lengkap).
        const inventarisBelum = document.querySelectorAll(
            '#inventaris [data-item][data-state="belum"]'
        );

        if (inventarisBelum.length > 0) {
            event.preventDefault();
            tampilkanError(
                'Masih ada ' + inventarisBelum.length +
                ' inventaris yang belum diperiksa. Pilih Lengkap atau Tidak lengkap pada setiap barang.',
                inventarisBelum[0]
            );
            return;
        }

        // 3. Jangan simpan saat pemeriksaan inventaris masih berjalan.
        const masihMenyimpan = Array.from(
            document.querySelectorAll('#inventaris [data-status]')
        ).some(function (el) {
            return el.textContent.trim().toLowerCase().startsWith('menyimpan');
        });

        if (masihMenyimpan) {
            event.preventDefault();
            tampilkanError('Pemeriksaan inventaris sedang disimpan. Tunggu sebentar lalu coba lagi.');
            return;
        }

        // 4. Tanda tangan penyerah: baru, atau pakai yang lama saat mengedit.
        const signature = signatureInput?.value || '';
        const adaTtdLama = form.dataset.adaTtdLama === '1';

        if (!signature && !adaTtdLama) {
            event.preventDefault();

            document.getElementById('signatureError')
                ?.classList.remove('hidden');

            tampilkanError(
                'Silakan bubuhkan tanda tangan penyerah terlebih dahulu.',
                document.getElementById('signatureCanvas')
            );
            return;
        }

        // 5. Susun ringkasan. Daftar KA diisi ulang oleh server dari data
        //    realisasi terbaru, jadi tidak perlu dikirim dari browser.
        const summary = {
            alat: {},
            alat_catatan: {},
            catatan_serah_terima: ringkasanText.value.trim()
        };

        // Status alat pengamanan yang dipilih.
        document.querySelectorAll('[name^="alat["]').forEach(function (input) {
            if (!input.checked) {
                return;
            }

            const match = input.name.match(/^alat\[(.+)\]$/);

            if (match) {
                summary.alat[match[1]] = input.value;
            }
        });

        // Catatan setiap alat pengamanan.
        document.querySelectorAll('[data-alat-catatan]').forEach(function (input) {
            const key = input.dataset.alatCatatan;

            if (key) {
                summary.alat_catatan[key] = input.value;
            }
        });

        try {
            ringkasanInput.value = JSON.stringify(summary);
        } catch (error) {
            event.preventDefault();
            tampilkanError('Data serah terima gagal diproses. Silakan coba lagi.');
            return;
        }

        signatureSubmitInput.value = signature;
        signatureActionInput.value = signature ? 'set' : 'keep';

        // 6. Salin field tambahan yang berada di luar form ini.
        form.querySelectorAll('input[data-generated="1"]').forEach(function (el) {
            el.remove();
        });

        FIELD_TAMBAHAN.forEach(function (nama) {
            const asli = form.elements.namedItem(nama);

            // Sudah terkirim otomatis (mis. memakai atribut form="...").
            if (asli) {
                return;
            }

            const nilai = nilaiField(nama);

            if (nilai === '') {
                return;
            }

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = nama;
            hidden.value = nilai;
            hidden.dataset.generated = '1';
            form.appendChild(hidden);
        });

        submitting = true;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan serah terima...';
        }
    });
});
</script>

<style>
@media print {
    aside,
    nav,
    .print\:hidden {
        display: none !important;
    }

    body {
        background: white !important;
    }

    section {
        break-inside: avoid;
        box-shadow: none !important;
    }

    button {
        display: none !important;
    }
}
</style>