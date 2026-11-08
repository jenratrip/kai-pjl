@if ($modeKonfirmasiPenerima)
    <section
        data-ttd-penerima
        class="space-y-4 rounded-xl border border-amber-200 bg-white p-4 shadow-sm"
    >
        <form
            id="formKonfirmasiPenerima"
            method="POST"
            action="{{ route('user.serah-terima-dinas.konfirmasi', $serahTerima->id) }}"
            class="space-y-3"
        >
            @csrf

            <input
                type="hidden"
                name="tanda_tangan_penerima"
                id="signaturePenerimaSubmit"
            >

            <div
                id="konfirmasiPenerimaError"
                class="hidden rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                role="alert"
            ></div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a
                    href="{{ route('user.serah-terima-dinas.riwayat') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Kembali
                </a>

                <button
                    type="submit"
                    id="submitKonfirmasiPenerima"
                    class="rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#263d68] disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Konfirmasi &amp; tanda tangani
                </button>
            </div>

            <p class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs leading-relaxed text-blue-900">
                Dengan menekan tombol konfirmasi, Anda menyatakan telah menerima
                serah terima dinas ini. Tanda tangan akan disimpan pada dokumen
                yang sama dan isi serah terima tidak dapat diubah lagi oleh
                penyerah.
            </p>
        </form>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formKonfirmasiPenerima');
        const canvasData = document.getElementById('signaturePenerimaData');
        const submitInput = document.getElementById('signaturePenerimaSubmit');
        const submitButton = document.getElementById('submitKonfirmasiPenerima');
        const errorBox = document.getElementById('konfirmasiPenerimaError');

        if (!form || !canvasData || !submitInput || !submitButton) {
            return;
        }

        let submitting = false;

        form.addEventListener('submit', function (event) {
            if (submitting) {
                event.preventDefault();
                return;
            }

            const signature = canvasData.value.trim();

            if (!signature) {
                event.preventDefault();

                errorBox.textContent =
                    'Silakan bubuhkan tanda tangan penerima terlebih dahulu.';
                errorBox.classList.remove('hidden');

                document.getElementById('signaturePenerimaError')
                    ?.classList.remove('hidden');

                document.getElementById('signaturePenerimaCanvas')
                    ?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                return;
            }

            submitInput.value = signature;
            submitting = true;

            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan tanda tangan...';
        });

        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                submitting = false;
                submitButton.disabled = false;
                submitButton.textContent = 'Konfirmasi & tanda tangani';
            }
        });
    });
    </script>
@endif