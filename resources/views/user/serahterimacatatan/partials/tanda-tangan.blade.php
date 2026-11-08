@php
    $modeLihat = !empty($modeLihat);
    $modeKonfirmasiPenerima = !empty($modeKonfirmasiPenerima);
    $serahTerima = $serahTerima ?? null;

    // Tanda tangan penyerah
    $ttdPenyerah = $serahTerima?->tanda_tangan_penyerah;

    $ttdPenyerahAda = $ttdPenyerah
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($ttdPenyerah);

    $urlTtdPenyerah = $ttdPenyerahAda
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($ttdPenyerah)
        : null;

    // Tanda tangan penerima
    $ttdPenerima = $serahTerima?->tanda_tangan_penerima;

    $ttdPenerimaAda = $ttdPenerima
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($ttdPenerima);

    $urlTtdPenerima = $ttdPenerimaAda
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($ttdPenerima)
        : null;
@endphp

<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

    {{-- JUDUL --}}
    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#1d2d50] text-sm font-bold text-white">
            8
        </span>

        <div>
            <h2 class="font-bold text-[#1d2d50]">
                Tanda tangan
            </h2>

            <p class="mt-0.5 text-xs text-slate-500">
                @if ($modeKonfirmasiPenerima)
                    Periksa tanda tangan penyerah, kemudian bubuhkan tanda tangan penerima.
                @elseif ($modeLihat)
                    Tanda tangan penyerah dan penerima yang tersimpan pada serah terima ini.
                @elseif ($urlTtdPenyerah)
                    Tanda tangan penyerah sudah tersimpan. Klik Ganti jika ingin membuat yang baru.
                @else
                    Bubuhkan tanda tangan sebelum menyimpan serah terima dinas.
                @endif
            </p>
        </div>
    </div>

    {{-- =====================================================
         LAYOUT TANDA TANGAN KIRI DAN KANAN
    ====================================================== --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

        {{-- =================================================
             KOLOM KIRI: TANDA TANGAN PENYERAH
        ================================================== --}}
        <div data-ttd-penyerah class="min-w-0">
            <div class="h-full rounded-lg border border-slate-200 bg-slate-50 p-3">

                <h3 class="font-bold text-[#1d2d50]">
                    PJL yang menyerahkan dinas
                </h3>

                <p class="mt-1 text-sm text-slate-600">
                    {{ data_get($serahTerima, 'ringkasan.nama_penyerah') ?: ($serahTerima?->dinas?->user?->name ?? auth()->user()->name) }}
                </p>

                <p class="text-xs text-slate-500">
                    NIPP / NIPKWT:
                    {{ data_get($serahTerima, 'ringkasan.nip_penyerah') ?: ($serahTerima?->dinas?->user?->nip ?? auth()->user()->nip ?? '-') }}
                </p>

                {{-- Tanda tangan penyerah tersimpan --}}
                @if ($urlTtdPenyerah)
                    <div id="signatureSavedBox" class="mt-3">

                        <div class="flex h-40 items-center justify-center rounded-lg border border-slate-300 bg-white p-3">
                            <img
                                src="{{ $urlTtdPenyerah }}"
                                alt="Tanda tangan penyerah tersimpan"
                                class="max-h-full max-w-full object-contain"
                            >
                        </div>

                        @if (!$modeLihat && !$modeKonfirmasiPenerima)
                            <div class="mt-2 flex justify-end">
                                <button
                                    type="button"
                                    id="changeSignature"
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                >
                                    Ganti tanda tangan
                                </button>
                            </div>
                        @endif

                    </div>
                @elseif ($modeLihat || $modeKonfirmasiPenerima)
                    <div class="mt-3 flex h-40 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white p-4">
                        <p class="text-center text-sm text-slate-500">
                            Tanda tangan penyerah belum tersedia.
                        </p>
                    </div>
                @endif

                {{-- Area menggambar tanda tangan penyerah --}}
                @if (!$modeLihat && !$modeKonfirmasiPenerima)
                    <div
                        id="signatureDrawArea"
                        class="{{ $urlTtdPenyerah ? 'hidden' : '' }}"
                    >
                        <canvas
                            id="signatureCanvas"
                            aria-label="Area tanda tangan penyerah"
                            class="mt-3 block h-40 w-full touch-none rounded-lg border border-slate-300 bg-white"
                        ></canvas>

                        <div class="mt-2 flex flex-wrap justify-end gap-2">

                            @if ($urlTtdPenyerah)
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
                @endif

            </div>
        </div>

        {{-- =================================================
             KOLOM KANAN: TANDA TANGAN PENERIMA
        ================================================== --}}
        <div data-ttd-penerima class="min-w-0">
            <div class="h-full rounded-lg border border-slate-200 bg-slate-50 p-3">

                <h3 class="font-bold text-[#1d2d50]">
                    PJL yang menerima dinas
                </h3>

                <p class="mt-1 text-sm text-slate-600">
                    {{ data_get($serahTerima, 'ringkasan.nama_penerima') ?: ($serahTerima?->penerima?->name ?? '-') }}
                </p>

                <p class="text-xs text-slate-500">
                    NIPP / NIPKWT:
                    {{ data_get($serahTerima, 'ringkasan.nip_penerima') ?: ($serahTerima?->penerima?->nip ?? '-') }}
                </p>

                {{-- Tanda tangan penerima sudah tersimpan --}}
                @if ($urlTtdPenerima)

                    <div class="mt-3">
                        <p class="mb-2 text-xs font-medium text-slate-500">
                            Tanda tangan penerima:
                        </p>

                        <div class="flex h-40 items-center justify-center rounded-lg border border-slate-300 bg-white p-3">
                            <img
                                src="{{ $urlTtdPenerima }}"
                                alt="Tanda tangan penerima"
                                class="max-h-full max-w-full object-contain"
                            >
                        </div>
                    </div>

                {{-- Mode konfirmasi: penerima dapat menggambar tanda tangan --}}
                @elseif ($modeKonfirmasiPenerima)

                    <canvas
                        id="signaturePenerimaCanvas"
                        aria-label="Area tanda tangan penerima"
                        class="mt-3 block h-40 w-full touch-none rounded-lg border border-slate-300 bg-white"
                    ></canvas>

                    <input
                        type="hidden"
                        id="signaturePenerimaData"
                        value=""
                    >

                    <p
                        id="signaturePenerimaError"
                        class="mt-2 hidden text-xs font-medium text-red-600"
                        role="alert"
                    >
                        Tanda tangan penerima wajib diisi.
                    </p>

                    <div class="mt-2 flex justify-end">
                        <button
                            type="button"
                            id="clearSignaturePenerima"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Hapus tanda tangan
                        </button>
                    </div>

                    <p class="mt-2 text-xs leading-relaxed text-slate-500">
                        Bubuhkan tanda tangan pada area putih menggunakan
                        mouse, touchpad, atau sentuhan layar.
                    </p>

                @else

                    <div class="mt-3 flex h-40 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white p-4">
                        <p class="text-center text-sm text-slate-500">
                            Penerima belum memberikan tanda tangan.
                        </p>
                    </div>

                @endif

            </div>
        </div>

    </div>
</section>

{{-- =====================================================
     JAVASCRIPT TANDA TANGAN PENYERAH
====================================================== --}}
@if (!$modeLihat && !$modeKonfirmasiPenerima)
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

    if (!canvas || !hidden || !clearButton) return;

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
            y: event.clientY - rect.top
        };
    }

    function saveSignature() {
        hidden.value = hasSignature
            ? canvas.toDataURL('image/png')
            : '';
    }

    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();

        // Canvas tersembunyi: jangan diproses sampai ditampilkan.
        if (!rect.width || !rect.height) return;

        // Simpan coretan sebelum ukuran canvas berubah.
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
                context.drawImage(
                    image,
                    0,
                    0,
                    rect.width,
                    rect.height
                );

                saveSignature();
            };

            image.src = previousImage;
        }
    }

    function startDrawing(event) {
        if (event.button !== undefined && event.button !== 0) return;

        event.preventDefault();

        drawing = true;
        lastPoint = point(event);

        canvas.setPointerCapture?.(event.pointerId);

        // Tetap mencatat tanda tangan meskipun hanya satu ketukan.
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
        if (!drawing || !lastPoint) return;

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
        if (!drawing) return;

        drawing = false;
        lastPoint = null;

        saveSignature();
    }

    function clearSignature() {
        context.save();

        context.setTransform(1, 0, 0, 1, 0, 0);

        context.clearRect(
            0,
            0,
            canvas.width,
            canvas.height
        );

        context.restore();

        hasSignature = false;
        drawing = false;
        lastPoint = null;
        hidden.value = '';

        errorMessage?.classList.add('hidden');
    }

    function showDrawArea() {
        savedBox?.classList.add('hidden');
        drawArea?.classList.remove('hidden');

        // Atur ulang ukuran setelah canvas ditampilkan.
        resizeCanvas();
    }

    // Batal mengganti tanda tangan dan kembali ke tanda tangan lama.
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

    resizeCanvas();

    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);

        resizeTimer = window.setTimeout(
            resizeCanvas,
            150
        );
    });

});
</script>
@endif

{{-- =====================================================
     JAVASCRIPT TANDA TANGAN PENERIMA
====================================================== --}}
@if ($modeKonfirmasiPenerima && !$urlTtdPenerima)
<script>
document.addEventListener('DOMContentLoaded', function () {

    const canvas = document.getElementById('signaturePenerimaCanvas');
    const hidden = document.getElementById('signaturePenerimaData');
    const clearButton = document.getElementById('clearSignaturePenerima');
    const errorMessage = document.getElementById('signaturePenerimaError');

    if (!canvas || !hidden || !clearButton) return;

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
            y: event.clientY - rect.top
        };
    }

    function saveSignature() {
        hidden.value = hasSignature
            ? canvas.toDataURL('image/png')
            : '';
    }

    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();

        if (!rect.width || !rect.height) return;

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
                context.drawImage(
                    image,
                    0,
                    0,
                    rect.width,
                    rect.height
                );

                saveSignature();
            };

            image.src = previousImage;
        }
    }

    function startDrawing(event) {
        if (event.button !== undefined && event.button !== 0) return;

        event.preventDefault();

        drawing = true;
        lastPoint = point(event);

        canvas.setPointerCapture?.(event.pointerId);

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
        if (!drawing || !lastPoint) return;

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
        if (!drawing) return;

        drawing = false;
        lastPoint = null;

        saveSignature();
    }

    function clearSignature() {
        context.save();

        context.setTransform(1, 0, 0, 1, 0, 0);

        context.clearRect(
            0,
            0,
            canvas.width,
            canvas.height
        );

        context.restore();

        drawing = false;
        hasSignature = false;
        lastPoint = null;
        hidden.value = '';

        errorMessage?.classList.add('hidden');
    }

    canvas.addEventListener('pointerdown', startDrawing);
    canvas.addEventListener('pointermove', draw);
    canvas.addEventListener('pointerup', stopDrawing);
    canvas.addEventListener('pointercancel', stopDrawing);
    canvas.addEventListener('lostpointercapture', stopDrawing);

    clearButton.addEventListener('click', clearSignature);

    resizeCanvas();

    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);

        resizeTimer = window.setTimeout(
            resizeCanvas,
            150
        );
    });

});
</script>
@endif