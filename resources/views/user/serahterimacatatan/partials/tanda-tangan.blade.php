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
                Bubuhkan tanda tangan sebelum menyimpan serah terima dinas.
            </p>
        </div>
    </div>

    {{-- Hanya tanda tangan penyerah. Admin tidak perlu menandatangani atau menyetujui. --}}
    <div class="max-w-xl">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <h3 class="font-bold text-[#1d2d50]">
                PJL yang menyerahkan dinas
            </h3>

            <p class="mt-1 text-sm text-slate-600">
                {{ auth()->user()->name }}
            </p>

            <p class="text-xs text-slate-500">
                NIP: {{ auth()->user()->nip ?: '-' }}
            </p>

            <canvas
                id="signatureCanvas"
                aria-label="Area tanda tangan penyerah"
                class="mt-3 block h-40 w-full touch-none rounded-lg border border-slate-300 bg-white"
            ></canvas>

            <div class="mt-2 flex justify-end">
                <button
                    type="button"
                    id="clearSignature"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    Hapus tanda tangan
                </button>
            </div>

            {{-- Input ini dibaca JavaScript actions.blade.php --}}
            <input
                type="hidden"
                id="signatureData"
                value=""
            >

            <p class="mt-2 text-xs leading-relaxed text-slate-500">
                Tanda tangani area putih menggunakan mouse, touchpad,
                atau sentuhan layar.
            </p>

            <p
                id="signatureError"
                class="mt-2 hidden text-xs font-medium text-red-600"
                role="alert"
            >
                Tanda tangan penyerah wajib diisi.
            </p>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('signatureCanvas');
    const hidden = document.getElementById('signatureData');
    const clearButton = document.getElementById('clearSignature');
    const errorMessage = document.getElementById('signatureError');
    const submitForm = document.getElementById('serahTerimaSubmitForm');

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
        context.fillStyle = '#1d2d50';
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
        context.clearRect(0, 0, canvas.width, canvas.height);

        hasSignature = false;
        drawing = false;
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

    // Validasi sisi tampilan; controller tetap wajib memvalidasi.
    if (submitForm) {
        submitForm.addEventListener('submit', function (event) {
            saveSignature();

            if (!hasSignature || !hidden.value) {
                event.preventDefault();

                errorMessage?.classList.remove('hidden');
                canvas.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });

                return;
            }

            errorMessage?.classList.add('hidden');
        });
    }

    resizeCanvas();

    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);

        resizeTimer = window.setTimeout(resizeCanvas, 150);
    });
});
</script>