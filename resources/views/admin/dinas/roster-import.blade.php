<div class="space-y-5" x-data="dinasRosterPreview({ previewUrl: @js(route('admin.dinas.import.preview')), confirmUrl: @js(route('admin.dinas.import')) })"
     @dinas-roster-file-selected.window="previewExternalFile($event.detail.file)"
     @dinas-csv-file-selected.window="clearPreview()">
    <div>
        <h2 class="font-bold text-[#1d2d50]">Periode roster Excel</h2>
        <p class="mt-1 text-sm text-slate-600">Jabatan workbook dipetakan ke JPL aktif. Baris dan tanggal diperiksa seluruhnya sebelum tersedia konfirmasi.</p>
    </div>

    <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-2">
        <div>
            <label for="roster-month" class="mb-1.5 block text-sm font-semibold text-slate-700">Bulan</label>
            <select id="roster-month" name="month" x-model="month" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $monthNumber => $monthName)
                    <option value="{{ $monthNumber }}" @selected($monthNumber === 10)>{{ $monthName }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="roster-year" class="mb-1.5 block text-sm font-semibold text-slate-700">Tahun</label>
            <input id="roster-year" name="year" type="number" min="2000" max="2100" x-model="year" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
        </div>
    </div>

    <template x-if="errors.length > 0">
        <section class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <p class="font-semibold">Workbook belum dapat dipratinjau.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                <template x-for="error in errors" :key="error"><li x-text="error"></li></template>
            </ul>
        </section>
    </template>

    <template x-if="preview">
        <div class="space-y-5">
            <section class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
                <p class="font-bold">Pratinjau saja</p>
                <p class="mt-1">Validasi server mencakup seluruh petugas dan tanggal. Jika satu data gagal, seluruh roster tidak valid dan tidak ada penyimpanan sebagian.</p>
            </section>

            <section class="rounded-lg border border-slate-200 p-4">
                <h3 class="font-bold text-[#1d2d50]">Kecocokan jam master shift</h3>
                <div class="mt-3 grid gap-3 md:grid-cols-3">
                    <template x-for="(shift, code) in shiftConfiguration" :key="code">
                        <div class="rounded-lg border p-3" :class="shift.status === 'cocok' ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'">
                            <p class="font-bold text-slate-800"><span x-text="code"></span> - <span x-text="shift.name"></span></p>
                            <p class="mt-1 text-xs text-slate-600">Roster: <span x-text="shift.start.slice(0, 5)"></span>-<span x-text="shift.end.slice(0, 5)"></span></p>
                            <p class="text-xs text-slate-600">Master: <span x-text="shift.actual_start?.slice(0, 5) ?? 'tidak ditemukan'"></span>-<span x-text="shift.actual_end?.slice(0, 5) ?? '-'"></span></p>
                            <p class="mt-2 text-xs font-semibold" x-text="shift.status === 'cocok' ? 'Sesuai' : 'Belum sesuai'"></p>
                        </div>
                    </template>
                </div>
            </section>

            <section class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                <div>
                    <h3 class="font-bold text-[#1d2d50]">Roster <span x-text="preview.month"></span>/<span x-text="preview.year"></span></h3>
                    <p class="text-xs text-slate-500"><span x-text="validation?.valid_staff_count ?? 0"></span> dari <span x-text="validation?.staff_count ?? preview.staff.length"></span> petugas valid; <span x-text="preview.days_in_month"></span> tanggal</p>
                </div>
                <span class="rounded-full px-3 py-1.5 text-xs font-bold" :class="previewReady ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                      x-text="previewReady ? 'Seluruh roster valid' : `${validation?.invalid_staff_count ?? 0} petugas bermasalah; impor seluruh roster diblokir`"></span>
            </section>

            <template x-if="validation?.storage_blockers?.length">
                <section class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950" role="status">
                    <p class="font-bold">Penyimpanan masih diblokir</p>
                    <ul class="mt-2 list-inside list-disc space-y-1">
                        <template x-for="(blocker, index) in validation.storage_blockers" :key="`${index}-${blocker}`"><li x-text="blocker"></li></template>
                    </ul>
                </section>
            </template>

            <template x-for="staff in preview.staff" :key="`${staff.workbook_row}-${staff.nipkwt}`">
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <header class="flex flex-col gap-3 border-b border-slate-200 p-4 md:flex-row md:items-start md:justify-between">
                        <div class="min-w-0">
                            <h4 class="text-base font-bold text-[#1d2d50]" x-text="staff.name || 'Nama belum diisi'"></h4>
                            <p class="mt-1 text-sm text-slate-600">NIPKWT: <span class="font-semibold text-slate-800" x-text="staff.nipkwt || '-'"></span></p>
                            <p class="text-sm text-slate-600">Jabatan: <span x-text="staff.job_title || 'Kosong'"></span> | Baris <span x-text="staff.workbook_row"></span></p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="accountBadgeClass(staff.account.status)" x-text="`Akun: ${staff.account.label}`"></span>
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="assignmentBadgeClass(staff.assignment.status)" x-text="`Penempatan: ${staff.assignment.label}`"></span>
                            </div>
                            <p class="mt-2 text-xs text-slate-600" x-text="staff.account.detail"></p>
                            <p class="text-xs text-slate-600" x-text="staff.assignment.detail"></p>
                        </div>
                        <div class="shrink-0 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">JPL hasil pemetaan</p>
                            <p class="mt-1 font-bold text-[#1d2d50]" x-text="staff.assignment.jpl ? `${staff.assignment.jpl.code} - ${staff.assignment.jpl.name}` : 'Belum valid'"></p>
                        </div>
                    </header>

                    <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-4 lg:grid-cols-7">
                        <template x-for="day in staff.days" :key="day.date">
                            <div class="min-h-20 rounded-lg border p-2" :class="day.code === 'L' && day.valid ? 'border-blue-200 bg-blue-50' : (!day.valid || day.shift_status !== 'cocok' ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-white')">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-slate-500" x-text="day.date.slice(-2)"></span>
                                    <span class="text-sm font-black text-[#1d2d50]" x-text="day.code || '-'"></span>
                                </div>
                                <p class="mt-1 text-[11px] font-semibold" x-text="day.code === 'L' && day.valid ? 'Libur' : (day.valid ? ({P: 'Pagi', S: 'Siang', M: 'Malam'}[day.code] ?? 'Kode tidak dikenal') : 'Kode tidak valid')"></p>
                                <p class="mt-1 text-[10px] text-slate-600" x-text="day.valid && day.code !== 'L' ? `${day.shift_status === 'cocok' ? 'Shift cocok' : 'Shift belum sesuai'} | ${staff.assignment.jpl?.code ?? 'JPL tidak valid'}` : (day.code === 'L' ? 'Tidak ada shift' : 'Periksa kode')"></p>
                            </div>
                        </template>
                    </div>

                    <template x-if="staff.validation_errors.length > 0">
                        <div class="border-t border-red-100 bg-red-50 px-4 py-3 text-xs text-red-800">
                            <p class="font-bold">Kesalahan validasi petugas</p>
                            <ul class="mt-1 list-inside list-disc"><template x-for="error in staff.validation_errors" :key="error"><li x-text="error"></li></template></ul>
                        </div>
                    </template>
                </article>
            </template>

            <section class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="font-bold text-[#1d2d50]">Konfirmasi roster</h3>
                        <p class="mt-1 text-xs text-slate-600">Konfirmasi hanya tersedia jika semua data valid, periode belum terisi, tabel hari libur siap, dan sumber Jadwal KA historis untuk tanggal lampau dapat dibuktikan.</p>
                    </div>
                    <button type="button" @click="confirmImport()" :disabled="!previewReady || !canConfirmImport() || importing"
                            :aria-disabled="!previewReady || !canConfirmImport() || importing"
                            class="rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-600">
                        <span x-text="importing ? 'Menyimpan roster...' : 'Konfirmasi impor roster'"></span>
                    </button>
                </div>
                <template x-if="blockers.length > 0">
                    <ul class="mt-3 max-h-48 list-inside list-disc space-y-1 overflow-y-auto rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-950">
                        <template x-for="(blocker, index) in blockers" :key="`${index}-${blocker}`"><li x-text="blocker"></li></template>
                    </ul>
                </template>
            </section>

            <template x-if="importError">
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert" x-text="importError"></div>
            </template>
        </div>
    </template>
</div>
