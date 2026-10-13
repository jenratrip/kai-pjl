<form
    id="serahTerimaSubmitForm"
    method="POST"
    action="{{ route('user.serah-terima-dinas.store') }}"
    class="space-y-4"
>
    @csrf

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
        ></textarea>
    </div>

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
            Simpan dan kirim ke admin
        </button>
    </div>

    <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-900">
        Pastikan informasi KA, alat pengamanan, catatan dinas, dan tanda tangan
        sudah diperiksa sebelum mengirim serah terima.
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('serahTerimaSubmitForm');

    if (!form) {
        return;
    }

    let submitting = false;

    form.addEventListener('submit', function (event) {
        if (submitting) {
            event.preventDefault();
            return;
        }

        const signatureInput = document.getElementById('signatureData');
        const signatureSubmitInput = document.getElementById('signatureSubmitInput');
        const penerimaInput = document.getElementById('penerima_user_id');
        const ringkasanInput = document.getElementById('ringkasanInput');
        const ringkasanText = document.getElementById('ringkasan_text');
        const submitButton = document.getElementById('submitSerahTerima');

        const signature = signatureInput?.value || '';
        const penerima = penerimaInput?.value || '';

        if (!penerima) {
            event.preventDefault();
            alert('Pilih admin penerima serah terima terlebih dahulu.');
            penerimaInput?.focus();
            return;
        }

        if (!signature) {
            event.preventDefault();
            alert('Silakan bubuhkan tanda tangan penyerah terlebih dahulu.');

            document.getElementById('signatureError')
                ?.classList.remove('hidden');

            document.getElementById('signatureCanvas')
                ?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

            return;
        }

        if (!ringkasanText || !ringkasanInput || !signatureSubmitInput) {
            event.preventDefault();
            alert('Form serah terima tidak lengkap. Muat ulang halaman dan coba lagi.');
            return;
        }

        const summary = {
            ka_batal: document.getElementById('ka_batal')?.value || '',
            ka_berjalan: document.getElementById('ka_berjalan')?.value || '',
            ka_belum_lewat: document.getElementById('ka_belum_lewat')?.value || '',
            alat: {},
            alat_catatan: {},
            catatan_serah_terima: ringkasanText.value.trim()
        };

        // Kumpulkan status alat pengamanan yang dipilih.
        document.querySelectorAll('[name^="alat["]').forEach(function (input) {
            if (!input.checked) {
                return;
            }

            const match = input.name.match(/^alat\[(.+)\]$/);

            if (match) {
                summary.alat[match[1]] = input.value;
            }
        });

        // Kumpulkan catatan setiap alat pengamanan.
        document.querySelectorAll('[data-alat-catatan]').forEach(function (input) {
            const key = input.dataset.alatCatatan;

            if (key) {
                summary.alat_catatan[key] = input.value;
            }
        });

        try {
            ringkasanInput.value = JSON.stringify(summary);
            signatureSubmitInput.value = signature;
        } catch (error) {
            event.preventDefault();
            alert('Data serah terima gagal diproses. Silakan coba lagi.');
            return;
        }

        submitting = true;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Mengirim serah terima...';
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
