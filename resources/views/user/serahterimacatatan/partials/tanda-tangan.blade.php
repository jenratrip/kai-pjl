@php
    $modeLihat = !empty($modeLihat);
    $serahTerima = $serahTerima ?? null;

    $ttdLama = $serahTerima?->tanda_tangan_penyerah;
    $urlTtdLama = $ttdLama
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($ttdLama)
        : null;
@endphp

<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-4 flex items-center gap-3">
        <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#1d2d50] text-sm font-bold text-white"
        >
            8
        </span>

        <div>
            <h2 class="font-bold text-[#1d2d50]">Tanda tangan</h2>
            <p class="mt-0.5 text-xs text-slate-500">
                @if ($modeLihat)
                    Tanda tangan yang tersimpan pada serah terima ini.
                @elseif ($urlTtdLama)
                    Tanda tangan sudah tersimpan. Klik Ganti jika ingin membuat yang baru.
                @else
                    Bubuhkan tanda tangan sebelum menyimpan serah terima dinas.
                @endif
            </p>
        </div>
    </div>

    {{-- Hanya tanda tangan penyerah. Admin tidak perlu menandatangani. --}}
    <div class="max-w-xl">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <h3 class="font-bold text-[#1d2d50]">
                PJL yang menyerahkan dinas
            </h3>

            <p class="mt-1 text-sm text-slate-600">
                {{ data_get($serahTerima, 'ringkasan.nama_penyerah') ?: auth()->user()->name }}
            </p>

            <p class="text-xs text-slate-500">
                NIP: {{ data_get($serahTerima, 'ringkasan.nip_penyerah') ?: (auth()->user()->nip ?: '-') }}
            </p>

            {{-- 1) Tanda tangan tersimpan --}}
            @if ($urlTtdLama)
                <div id="signatureSavedBox" class="mt-3">
                    <img
                        src="{{ $urlTtdLama }}"
                        alt="Tanda tangan penyerah tersimpan"
                        class="h-40 w-full rounded-lg border border-slate-300 bg-white object-contain"
                    >

                    @unless ($modeLihat)
                        <div class="mt-2 flex justify-end">
                            <button
                                type="button"
                                id="changeSignature"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Ganti tanda tangan
                            </button>
                        </div>
                    @endunless
                </div>
            @elseif ($modeLihat)
                <p class="mt-3 text-sm text-slate-500">
                    Tanda tangan penyerah belum tersedia.
                </p>
            @endif

            {{-- 2) Area menggambar: tersembunyi jika sudah ada tanda tangan --}}
            @unless ($modeLihat)
                <div
                    id="signatureDrawArea"
                    class="{{ $urlTtdLama ? 'hidden' : '' }}"
                >
                    <canvas
                        id="signatureCanvas"
                        aria-label="Area tanda tangan penyerah"
                        class="mt-3 block h-40 w-full touch-none rounded-lg border border-slate-300 bg-white"
                    ></canvas>

                    <div class="mt-2 flex flex-wrap justify-end gap-2">
                        @if ($urlTtdLama)
                            <button
                                type="button"
                                id="cancelChangeSignature"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Batal, pakai yang lama
                            </button>
                        @endif

                        <button
                            type="button"
                            id="clearSignature"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Hapus tanda tangan
                        </button>
                    </div>

                    <p class="mt-2 text-xs leading-relaxed text-slate-500">
                        Tanda tangani area putih menggunakan mouse, touchpad,
                        atau sentuhan layar.
                    </p>
                </div>

                {{-- Input ini dibaca JavaScript di actions.blade.php --}}
                <input
                    type="hidden"
                    id="signatureData"
                    value=""
                >

                <p
                    id="signatureError"
                    class="mt-2 hidden text-xs font-medium text-red-600"
                    role="alert"
                >
                    Tanda tangan penyerah wajib diisi.
                </p>
            @endunless
        </div>
    </div>
</section>

@unless ($modeLihat)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('signatureCanvas');
    const hidden = document.getElementById('signatureData');
    const clearButton = document.getElementById('clearSignature');
    const errorMessage = document.getElementById('signatureError');

    const savedBox = document.getElementById('signatureSavedBox');
    const drawArea = document.getElementById('signatureDrawArea');
    const changeButton = document.getElementById('changeSignature');
    const cancelButton = document.getElementById('cancelChangeSignature');

    if (!canvas || !hidden || !clearButton) {
        return;
    }

    const context = canvas.getContext('2d');

    let drawing = false;
    let hasSignature = false;
    let lastPoint = null;
    let resizeTimer = null;

    function configureContext() {
        context.lineWidth = 2.2;
        context.lineCap = 'round';
        context.lineJoin = 'round';
        context.strokeStyle = '#1d2d50';
        context.fillStyle = '#1d2d50';
    }

    function point(event) {
        const rect = canvas.getBoundingClientRect();

        return {
            x: event.clientX - rect.left,
            y: event.clientY - rect.top,
        };
    }

    function saveSignature() {
        hidden.value = hasSignature
            ? canvas.toDataURL('image/png')
            : '';
    }

    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();

        // Canvas tersembunyi (ukuran 0): lewati, dihitung ulang saat tampil.
        if (!rect.width || !rect.height) {
            return;
        }

        // Simpan gambar sebelum ukuran canvas berubah.
        const previousImage = hasSignature
            ? canvas.toDataURL('image/png')
            : null;

        const ratio = Math.max(window.devicePixelRatio || 1, 1);

        canvas.width = Math.round(rect.width * ratio);
        canvas.height = Math.round(rect.height * ratio);

        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        configureContext();

        if (previousImage) {
            const image = new Image();

            image.onload = function () {
                context.drawImage(image, 0, 0, rect.width, rect.height);
                saveSignature();
            };

            image.src = previousImage;
        }
    }

    function startDrawing(event) {
        if (event.button !== undefined && event.button !== 0) {
            return;
        }

        event.preventDefault();

        drawing = true;
        lastPoint = point(event);

        canvas.setPointerCapture?.(event.pointerId);

        // Tetap mencatat tanda tangan walau hanya satu ketukan.
        context.beginPath();
        context.arc(
            lastPoint.x,
            lastPoint.y,
            context.lineWidth / 2,
            0,
            Math.PI * 2
        );
        context.fill();

        hasSignature = true;
        errorMessage?.classList.add('hidden');
        saveSignature();
    }

    function draw(event) {
        if (!drawing || !lastPoint) {
            return;
        }

        event.preventDefault();

        const currentPoint = point(event);

        context.beginPath();
        context.moveTo(lastPoint.x, lastPoint.y);
        context.lineTo(currentPoint.x, currentPoint.y);
        context.stroke();

        lastPoint = currentPoint;
        hasSignature = true;

        saveSignature();
    }

    function stopDrawing() {
        if (!drawing) {
            return;
        }

        drawing = false;
        lastPoint = null;
        saveSignature();
    }

    function clearSignature() {
        context.save();
        context.setTransform(1, 0, 0, 1, 0, 0);
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.restore();

        hasSignature = false;
        drawing = false;
        lastPoint = null;
        hidden.value = '';

        errorMessage?.classList.add('hidden');
    }

    // Ganti: sembunyikan gambar lama, tampilkan canvas.
    function showDrawArea() {
        savedBox?.classList.add('hidden');
        drawArea?.classList.remove('hidden');
        resizeCanvas();
    }

    // Batal: hapus coretan baru, kembali ke tanda tangan lama (action "keep").
    function showSavedBox() {
        clearSignature();
        drawArea?.classList.add('hidden');
        savedBox?.classList.remove('hidden');
    }

    canvas.addEventListener('pointerdown', startDrawing);
    canvas.addEventListener('pointermove', draw);
    canvas.addEventListener('pointerup', stopDrawing);
    canvas.addEventListener('pointercancel', stopDrawing);
    canvas.addEventListener('lostpointercapture', stopDrawing);

    clearButton.addEventListener('click', clearSignature);
    changeButton?.addEventListener('click', showDrawArea);
    cancelButton?.addEventListener('click', showSavedBox);

    // Validasi saat submit dilakukan di actions.blade.php.
    // Tidak ada listener submit di sini agar tidak bentrok.

    resizeCanvas();

    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(resizeCanvas, 150);
    });
});
</script>
@endunless