@php
    $modeLihat = !empty($modeLihat);
    $modePenerima = !empty($modePenerima);
    $modeEditForm = !empty($modeEdit) && !empty($serahTerima) && !$modeLihat;

    $ringkasanLama = old(
        'ringkasan_text',
        data_get($serahTerima ?? null, 'ringkasan.catatan_serah_terima', '')
    );

    $adaTtdPenyerahLama = !empty(($serahTerima ?? null)?->tanda_tangan_penyerah);

    $menungguKonfirmasiPenerima =
        $modePenerima
        && !empty($serahTerima)
        && $serahTerima->status === 'menunggu_penerima'
        && empty($serahTerima->tanda_tangan_penerima);
@endphp

@if ($modePenerima)

    {{-- KONFIRMASI OLEH PENERIMA --}}
    @if ($menungguKonfirmasiPenerima)

        <div class="space-y-4 print:hidden">

            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <h3 class="font-bold text-blue-900">
                    Konfirmasi Serah Terima Dinas
                </h3>

                <p class="mt-1 text-sm leading-relaxed text-blue-800">
                    Silakan periksa seluruh informasi serah terima.
                    Jika sudah sesuai, bubuhkan tanda tangan sebagai
                    penerima untuk mengonfirmasi serah terima ini.
                </p>
            </div>

            <form
                id="formKonfirmasiPenerima"
                method="POST"
                action="{{ route('user.serah-terima-dinas.konfirmasi', $serahTerima->id) }}"
                class="space-y-4"
            >
                @csrf

                <div class="rounded-lg border border-slate-200 bg-white p-4">

                    <label class="mb-3 block font-semibold text-slate-800">
                        Tanda tangan penerima
                    </label>

                    {{-- AREA TANDA TANGAN DIPERKECIL --}}
                    <div
                        class="w-full overflow-hidden rounded-lg border border-slate-300 bg-white"
                        style="height: 180px; max-height: 180px;"
                    >
                        <canvas
                            id="canvasTandaTanganPenerima"
                            class="block w-full"
                            style="
                                width: 100%;
                                height: 180px !important;
                                max-height: 180px !important;
                                touch-action: none;
                            "
                        ></canvas>
                    </div>

                    <input
                        type="hidden"
                        name="tanda_tangan_penerima"
                        id="inputTandaTanganPenerima"
                    >

                    <p
                        id="errorTandaTanganPenerima"
                        class="mt-2 hidden text-sm text-red-600"
                        role="alert"
                    >
                        Tanda tangan penerima wajib diisi.
                    </p>

                    <div class="mt-3 flex justify-end">
                        <button
                            type="button"
                            id="hapusTandaTanganPenerima"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Hapus tanda tangan
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">

                    <a
                        href="{{ route('user.serah-terima-dinas.riwayat') }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-[#1d2d50] transition hover:bg-slate-50"
                    >
                        Kembali ke riwayat
                    </a>

                    <button
                        type="submit"
                        id="tombolKonfirmasiPenerima"
                        class="rounded-lg bg-green-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Konfirmasi &amp; Tanda Tangani
                    </button>

                </div>
            </form>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('canvasTandaTanganPenerima');
            const form = document.getElementById('formKonfirmasiPenerima');
            const input = document.getElementById('inputTandaTanganPenerima');
            const tombolHapus = document.getElementById('hapusTandaTanganPenerima');
            const tombolKonfirmasi = document.getElementById('tombolKonfirmasiPenerima');
            const error = document.getElementById('errorTandaTanganPenerima');

            if (!canvas || !form || !input) {
                return;
            }

            const ctx = canvas.getContext('2d');

            let menggambar = false;
            let adaCoretan = false;
            let submitting = false;

            function aturCanvas() {
                const rect = canvas.getBoundingClientRect();

                if (!rect.width || !rect.height) {
                    return;
                }

                const dpr = window.devicePixelRatio || 1;

                const gambarLama = adaCoretan
                    ? canvas.toDataURL('image/png')
                    : null;

                canvas.width = Math.round(rect.width * dpr);
                canvas.height = Math.round(rect.height * dpr);

                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

                ctx.lineWidth = 2.5;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.strokeStyle = '#1d2d50';

                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, rect.width, rect.height);

                if (gambarLama) {
                    const img = new Image();

                    img.onload = function () {
                        ctx.drawImage(
                            img,
                            0,
                            0,
                            rect.width,
                            rect.height
                        );
                    };

                    img.src = gambarLama;
                }
            }

            function posisi(event) {
                const rect = canvas.getBoundingClientRect();

                return {
                    x: event.clientX - rect.left,
                    y: event.clientY - rect.top
                };
            }

            canvas.addEventListener('pointerdown', function (event) {
                if (submitting) {
                    return;
                }

                event.preventDefault();

                menggambar = true;
                adaCoretan = true;

                canvas.setPointerCapture(event.pointerId);

                const p = posisi(event);

                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
                ctx.lineTo(p.x + 0.1, p.y + 0.1);
                ctx.stroke();

                error.classList.add('hidden');
            });

            canvas.addEventListener('pointermove', function (event) {
                if (!menggambar || submitting) {
                    return;
                }

                event.preventDefault();

                const p = posisi(event);

                ctx.lineTo(p.x, p.y);
                ctx.stroke();
            });

            function berhentiMenggambar() {
                menggambar = false;
            }

            canvas.addEventListener('pointerup', berhentiMenggambar);
            canvas.addEventListener('pointercancel', berhentiMenggambar);
            canvas.addEventListener('lostpointercapture', berhentiMenggambar);

            tombolHapus.addEventListener('click', function () {
                if (submitting) {
                    return;
                }

                const rect = canvas.getBoundingClientRect();

                ctx.clearRect(
                    0,
                    0,
                    canvas.width,
                    canvas.height
                );

                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, rect.width, rect.height);

                adaCoretan = false;
                input.value = '';

                error.classList.add('hidden');
            });

            form.addEventListener('submit', function (event) {
                if (submitting) {
                    event.preventDefault();
                    return;
                }

                if (!adaCoretan) {
                    event.preventDefault();

                    error.classList.remove('hidden');

                    canvas.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    return;
                }

                input.value = canvas.toDataURL('image/png');

                if (!input.value.startsWith('data:image/png;base64,')) {
                    event.preventDefault();

                    error.classList.remove('hidden');
                    input.value = '';

                    return;
                }

                submitting = true;

                tombolKonfirmasi.disabled = true;
                tombolKonfirmasi.textContent = 'Mengirim konfirmasi...';
                tombolHapus.disabled = true;
            });

            aturCanvas();

            let resizeTimeout;

            window.addEventListener('resize', function () {
                clearTimeout(resizeTimeout);

                resizeTimeout = setTimeout(function () {
                    if (!menggambar && !submitting) {
                        aturCanvas();
                    }
                }, 150);
            });

            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    submitting = false;

                    tombolKonfirmasi.disabled = false;
                    tombolKonfirmasi.textContent = 'Konfirmasi & Tanda Tangani';
                    tombolHapus.disabled = false;
                }
            });
        });
        </script>

    @else

        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            Serah terima ini sudah dikonfirmasi atau tidak lagi menunggu tanda tangan penerima.
        </div>

        <div class="flex justify-end">
            <a
                href="{{ route('user.serah-terima-dinas.riwayat') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-[#1d2d50] transition hover:bg-slate-50"
            >
                Kembali ke riwayat
            </a>
        </div>

    @endif

@elseif ($modeLihat)

    {{-- DATA SUDAH DISETUJUI ADMIN --}}
    <div class="space-y-4 print:hidden">

        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            Serah terima ini sudah disetujui admin dan terkunci. Data hanya
            dapat dilihat atau dicetak.
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">

            <a
                href="{{ route('user.serah-terima-dinas.riwayat') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-[#1d2d50] transition hover:bg-slate-50"
            >
                Kembali ke riwayat
            </a>

            <button
                type="button"
                onclick="window.print()"
                class="rounded-lg border border-[#1d2d50] bg-white px-4 py-2.5 text-sm font-semibold text-[#1d2d50] transition hover:bg-slate-50"
            >
                Cetak
            </button>

        </div>
    </div>

@else

    {{-- FORM PENYERAH: SIMPAN / EDIT SERAH TERIMA --}}
    <form
        id="serahTerimaSubmitForm"
        method="POST"
        action="{{ route('user.serah-terima-dinas.store') }}"
        class="space-y-4"
        data-ada-ttd-lama="{{ $adaTtdPenyerahLama ? '1' : '0' }}"
    >
        @csrf

        @if ($modeEditForm)
            <input type="hidden" name="serah_terima_id" value="{{ $serahTerima->id }}">
        @endif

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

        <input
            type="hidden"
            name="tanda_tangan_penyerah_action"
            id="signatureActionInput"
            value="keep"
        >

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

        <p
            id="serahTerimaClientError"
            class="hidden rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 print:hidden"
            role="alert"
        ></p>

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

        <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-900 print:hidden">
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
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

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

            return el && typeof el.value === 'string'
                ? el.value.trim()
                : '';
        }

        function resetTombol() {
            submitting = false;

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = labelTombol;
            }
        }

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

                tampilkanError(
                    'Form serah terima tidak lengkap. Muat ulang halaman dan coba lagi.'
                );

                return;
            }

            // 1. Nama penerima wajib.
            if (!nilaiField('nama_penerima')) {
                event.preventDefault();

                tampilkanError(
                    'Isi nama penerima serah terima terlebih dahulu.',
                    cariField('nama_penerima')
                );

                return;
            }

            // 2. Semua inventaris harus sudah diperiksa.
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

                tampilkanError(
                    'Pemeriksaan inventaris sedang disimpan. Tunggu sebentar lalu coba lagi.'
                );

                return;
            }

            // 4. Tanda tangan penyerah.
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

            // 5. Susun ringkasan.
            const summary = {
                alat: {},
                alat_catatan: {},
                catatan_serah_terima: ringkasanText.value.trim()
            };

            document.querySelectorAll('[name^="alat["]').forEach(function (input) {
                if (!input.checked) {
                    return;
                }

                const match = input.name.match(/^alat\[(.+)\]$/);

                if (match) {
                    summary.alat[match[1]] = input.value;
                }
            });

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

                tampilkanError(
                    'Data serah terima gagal diproses. Silakan coba lagi.'
                );

                return;
            }

            signatureSubmitInput.value = signature;
            signatureActionInput.value = signature ? 'set' : 'keep';

            // 6. Salin field tambahan yang berada di luar form.
            form.querySelectorAll('input[data-generated="1"]').forEach(function (el) {
                el.remove();
            });

            FIELD_TAMBAHAN.forEach(function (nama) {
                const asli = form.elements.namedItem(nama);

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

@endif

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
