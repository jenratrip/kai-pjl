<div
    x-data="modalTukarJadwalCreate()"
    class="overflow-hidden rounded-2xl bg-white shadow-xl"
>
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800">Ajukan Tukar Jadwal</h2>
            <p class="mt-1 text-xs text-slate-500">
                Lengkapi jadwal dan petugas tujuan pertukaran.
            </p>
        </div>

        <button
            type="button"
            @click="$dispatch('close-tukar-modal')"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
        >
            <i class="fas fa-times"></i>
        </button>
    </div>

    <form @submit.prevent="submitForm" class="space-y-5 p-5">

        {{-- JADWAL PEMOHON --}}
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">
                1. Pilih Jadwal Anda
            </label>

            <select
                x-model="form.dinas_pemohon_id"
                @change="resetRecipientSchedule()"
                required
                class="w-full rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
            >
                <option value="">Pilih jadwal dinas</option>

                @foreach ($jadwalMilikSaya as $dinas)
                    <option value="{{ $dinas->id }}">
                        {{ $dinas->tanggal_dinas?->translatedFormat('d F Y') ?? '-' }}
                        · {{ $dinas->jam_mulai?->format('H:i') ?? '-' }}
                        - {{ $dinas->jam_selesai?->format('H:i') ?? '-' }}
                        · {{ $dinas->jpl?->nama ?? $dinas->jpl?->kode ?? '-' }}
                        · {{ $dinas->shift?->nama ?? '-' }}
                    </option>
                @endforeach
            </select>

            @if ($jadwalMilikSaya->isEmpty())
                <p class="mt-2 text-sm text-rose-600">
                    Tidak ada jadwal yang memenuhi syarat untuk ditukar.
                </p>
            @else
                <p class="mt-2 text-xs text-slate-500">
                    Hanya jadwal yang belum dimulai dan belum terkunci yang ditampilkan.
                </p>
            @endif
        </div>

        <div class="border-t border-slate-100"></div>

        {{-- CARI PENERIMA --}}
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">
                2. Cari Petugas Penerima
            </label>

            <div class="relative">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>

                    <input
                        type="text"
                        x-model="search"
                        @input.debounce.400ms="searchRecipient()"
                        :disabled="!form.dinas_pemohon_id"
                        placeholder="Masukkan nama atau NIP petugas..."
                        autocomplete="off"
                        class="w-full rounded-xl border border-slate-200 py-3 pl-10 pr-4 text-sm outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100 disabled:bg-slate-50"
                    >
                </div>

                <div
                    x-show="searchLoading"
                    class="mt-2 text-xs text-slate-500"
                >
                    <i class="fas fa-spinner fa-spin mr-1"></i>
                    Mencari petugas...
                </div>

                <div
                    x-show="searchResults.length && !selectedRecipient"
                    x-cloak
                    class="absolute z-20 mt-2 max-h-52 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg"
                >
                    <template x-for="user in searchResults" :key="user.id">
                        <button
                            type="button"
                            @click="selectRecipient(user)"
                            class="flex w-full items-center justify-between border-b border-slate-100 px-4 py-3 text-left last:border-0 hover:bg-orange-50"
                        >
                            <div>
                                <p class="text-sm font-semibold text-slate-800" x-text="user.name"></p>
                                <p class="mt-0.5 text-xs text-slate-500" x-text="'NIP: ' + (user.nip || '-')"></p>
                            </div>
                            <i class="fas fa-chevron-right text-xs text-slate-400"></i>
                        </button>
                    </template>
                </div>

                <p
                    x-show="search.length >= 2 && !searchLoading && searchResults.length === 0 && !selectedRecipient"
                    x-cloak
                    class="mt-2 text-xs text-slate-500"
                >
                    Tidak ditemukan petugas yang sesuai.
                </p>
            </div>

            {{-- PENERIMA TERPILIH --}}
            <div
                x-show="selectedRecipient"
                x-cloak
                class="mt-3 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-3"
            >
                <div>
                    <p class="text-xs font-semibold text-emerald-700">Petugas dipilih</p>
                    <p class="mt-1 text-sm font-bold text-slate-800" x-text="selectedRecipient?.name"></p>
                    <p class="text-xs text-slate-500" x-text="'NIP: ' + (selectedRecipient?.nip || '-')"></p>
                </div>

                <button
                    type="button"
                    @click="clearRecipient()"
                    class="rounded-lg px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-100"
                >
                    Ganti
                </button>
            </div>
        </div>

        {{-- JADWAL PENERIMA --}}
        <div x-show="selectedRecipient" x-cloak>
            <label class="mb-2 block text-sm font-semibold text-slate-700">
                3. Pilih Jadwal Penerima
            </label>

            <div
                x-show="scheduleLoading"
                class="rounded-xl bg-slate-50 p-4 text-center text-sm text-slate-500"
            >
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Memuat jadwal penerima...
            </div>

            <div
                x-show="!scheduleLoading && recipientSchedules.length === 0"
                class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700"
            >
                Penerima tidak memiliki jadwal yang memenuhi syarat untuk ditukar.
            </div>

            <div
                x-show="!scheduleLoading && recipientSchedules.length > 0"
                class="max-h-56 space-y-2 overflow-y-auto"
            >
                <template x-for="schedule in recipientSchedules" :key="schedule.id">
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition"
                        :class="form.dinas_tujuan_id == schedule.id
                            ? 'border-orange-400 bg-orange-50'
                            : 'border-slate-200 hover:border-orange-200'"
                    >
                        <input
                            type="radio"
                            name="dinas_tujuan_id"
                            :value="schedule.id"
                            x-model="form.dinas_tujuan_id"
                            class="mt-1 accent-orange-500"
                        >

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800" x-text="schedule.tanggal_label || schedule.tanggal_dinas || '-'"></p>
                            <p class="mt-1 text-xs text-slate-500">
                                <span x-text="schedule.jam_mulai || '-'"></span>
                                –
                                <span x-text="schedule.jam_selesai || '-'"></span>
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                <span x-text="schedule.jpl?.kode || schedule.jpl?.nama || '-'"></span>
                                ·
                                <span x-text="schedule.shift?.nama || schedule.shift?.nama_shift || '-'"></span>
                            </p>
                            <p
                                x-show="schedule.resort"
                                class="mt-1 text-xs text-slate-400"
                                x-text="schedule.resort"
                            ></p>
                        </div>
                    </label>
                </template>
            </div>
        </div>

        <div class="border-t border-slate-100"></div>

        {{-- ALASAN --}}
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">
                4. Alasan Pertukaran
            </label>

            <textarea
                x-model="form.alasan_pemohon"
                rows="3"
                minlength="10"
                maxlength="1000"
                required
                placeholder="Jelaskan alasan pengajuan tukar jadwal (minimal 10 karakter)..."
                class="w-full resize-y rounded-xl border border-slate-200 px-3 py-3 text-sm outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
            ></textarea>

            <div class="mt-1 flex justify-between text-xs text-slate-400">
                <span>Minimal 10 karakter</span>
                <span x-text="form.alasan_pemohon.length + '/1000'"></span>
            </div>
        </div>

        <div
            x-show="errorMessage"
            x-cloak
            class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"
            x-text="errorMessage"
        ></div>

        <div class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
            <button
                type="button"
                @click="$dispatch('close-tukar-modal')"
                class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
            >
                Batal
            </button>

            <button
                type="submit"
                :disabled="submitting || !form.dinas_pemohon_id || !form.dinas_tujuan_id || form.alasan_pemohon.trim().length < 10"
                class="rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <i class="fas fa-spinner fa-spin mr-1" x-show="submitting"></i>
                <span x-text="submitting ? 'Mengirim...' : 'Kirim Pengajuan'"></span>
            </button>
        </div>
    </form>
</div>

<script>
function modalTukarJadwalCreate() {
    return {
        search: '',
        searchLoading: false,
        searchResults: [],
        selectedRecipient: null,
        recipientSchedules: [],
        scheduleLoading: false,
        submitting: false,
        errorMessage: '',

        form: {
            dinas_pemohon_id: '',
            penerima_id: '',
            dinas_tujuan_id: '',
            alasan_pemohon: ''
        },

        async searchRecipient() {
            this.selectedRecipient = null;
            this.recipientSchedules = [];
            this.form.penerima_id = '';
            this.form.dinas_tujuan_id = '';

            if (this.search.trim().length < 2) {
                this.searchResults = [];
                return;
            }

            this.searchLoading = true;

            try {
                const url = new URL(
                    @json(route('user.tukar-jadwal-dinas.cari-penerima')),
                    window.location.origin
                );

                url.searchParams.set('q', this.search.trim());

                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Pencarian petugas gagal.');
                }

                this.searchResults = result.data || [];
            } catch (error) {
                this.errorMessage = error.message;
            } finally {
                this.searchLoading = false;
            }
        },

        async selectRecipient(user) {
            this.selectedRecipient = user;
            this.form.penerima_id = user.id;
            this.searchResults = [];
            this.search = user.name;
            this.form.dinas_tujuan_id = '';
            this.recipientSchedules = [];

            await this.loadRecipientSchedules();
        },

        async loadRecipientSchedules() {
            if (!this.form.dinas_pemohon_id || !this.form.penerima_id) {
                return;
            }

            this.scheduleLoading = true;

            try {
                const url = @json(url('/tukar-jadwal-dinas'))
                    + '/' + this.form.dinas_pemohon_id
                    + '/penerima/' + this.form.penerima_id + '/jadwal';

                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Gagal memuat jadwal penerima.');
                }

                this.recipientSchedules = result.data || [];
            } catch (error) {
                this.errorMessage = error.message;
            } finally {
                this.scheduleLoading = false;
            }
        },

        resetRecipientSchedule() {
            this.clearRecipient();
        },

        clearRecipient() {
            this.selectedRecipient = null;
            this.search = '';
            this.searchResults = [];
            this.recipientSchedules = [];
            this.form.penerima_id = '';
            this.form.dinas_tujuan_id = '';
            this.errorMessage = '';
        },

        async submitForm() {
            if (this.submitting) return;

            this.errorMessage = '';
            this.submitting = true;

            try {
                const response = await fetch(
                    @json(route('user.tukar-jadwal-dinas.store')),
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.form)
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.ok) {
                    throw new Error(
                        result.message || 'Pengajuan tukar jadwal gagal dikirim.'
                    );
                }

                alert(result.message || 'Pengajuan tukar jadwal berhasil dikirim.');
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
