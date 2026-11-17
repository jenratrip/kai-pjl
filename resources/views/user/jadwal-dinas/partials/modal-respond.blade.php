<div
    x-data="modalTukarJadwalRespond()"
    class="border-t border-slate-100 pt-5"
>
    <h3 class="text-sm font-bold text-slate-800">
        Tanggapi Permintaan
    </h3>

    <p class="mt-1 text-xs text-slate-500">
        Periksa kedua jadwal sebelum memberikan keputusan.
    </p>

    <form @submit.prevent="submitResponse" class="mt-4 space-y-4">

        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">
                Keputusan
            </label>

            <div class="grid grid-cols-2 gap-3">
                <label
                    class="flex cursor-pointer items-center gap-2 rounded-xl border p-3"
                    :class="action === 'approve'
                        ? 'border-emerald-400 bg-emerald-50'
                        : 'border-slate-200'"
                >
                    <input
                        type="radio"
                        value="approve"
                        x-model="action"
                        class="accent-emerald-600"
                    >
                    <span class="text-sm font-semibold text-emerald-700">
                        Setujui
                    </span>
                </label>

                <label
                    class="flex cursor-pointer items-center gap-2 rounded-xl border p-3"
                    :class="action === 'reject'
                        ? 'border-rose-400 bg-rose-50'
                        : 'border-slate-200'"
                >
                    <input
                        type="radio"
                        value="reject"
                        x-model="action"
                        class="accent-rose-600"
                    >
                    <span class="text-sm font-semibold text-rose-700">
                        Tolak
                    </span>
                </label>
            </div>
        </div>

        <div x-show="action === 'approve'" x-cloak>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                <i class="fas fa-info-circle mr-1"></i>
                Setelah disetujui, sistem akan memvalidasi ulang dan menukar kepemilikan kedua jadwal secara otomatis jika memenuhi syarat.
            </div>
        </div>

        <div x-show="action === 'reject'" x-cloak>
            <label class="mb-2 block text-sm font-semibold text-slate-700">
                Alasan Penolakan
            </label>

            <textarea
                x-model="reason"
                rows="3"
                maxlength="1000"
                :required="action === 'reject'"
                placeholder="Tuliskan alasan penolakan (minimal 5 karakter)..."
                class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100"
            ></textarea>

            <p class="mt-1 text-xs text-slate-400">
                Minimal 5 karakter, maksimal 1000 karakter.
            </p>
        </div>

        <div
            x-show="errorMessage"
            x-cloak
            class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"
            x-text="errorMessage"
        ></div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button
                type="button"
                @click="$dispatch('close-tukar-modal')"
                class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
            >
                Tutup
            </button>

            <button
                type="submit"
                :disabled="submitting || !action || (action === 'reject' && reason.trim().length < 5)"
                class="rounded-xl px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                :class="action === 'reject'
                    ? 'bg-rose-600 hover:bg-rose-700'
                    : 'bg-emerald-600 hover:bg-emerald-700'"
            >
                <i class="fas fa-spinner fa-spin mr-1" x-show="submitting"></i>
                <span x-text="submitting ? 'Memproses...' : (action === 'approve' ? 'Setujui Pertukaran' : 'Tolak Permintaan')"></span>
            </button>
        </div>
    </form>
</div>

<script>
function modalTukarJadwalRespond() {
    return {
        action: '',
        reason: '',
        submitting: false,
        errorMessage: '',

        async submitResponse() {
            if (this.submitting || !this.action) return;

            if (this.action === 'reject' && this.reason.trim().length < 5) {
                this.errorMessage = 'Alasan penolakan minimal 5 karakter.';
                return;
            }

            if (
                this.action === 'approve' &&
                !confirm('Setujui pertukaran jadwal ini? Sistem akan melakukan validasi dan pertukaran otomatis.')
            ) {
                return;
            }

            this.submitting = true;
            this.errorMessage = '';

            try {
                const response = await fetch(
                    @json(url('/tukar-jadwal-dinas')) + '/{{ $permintaan->id }}/respond',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            action: this.action,
                            alasan_penerima: this.reason
                        })
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.ok) {
                    throw new Error(result.message || 'Gagal memproses permintaan.');
                }

                alert(result.message || 'Tanggapan berhasil disimpan.');
                window.location.reload();
            } catch (error) {
                this.errorMessage = error.message;
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
