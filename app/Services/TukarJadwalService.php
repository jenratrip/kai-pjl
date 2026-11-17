<?php

namespace App\Services;

use App\Exceptions\SwapExecutionException;
use App\Models\Dinas;
use App\Models\PermintaanTukarJadwal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Service untuk mengelola proses tukar jadwal antar-petugas PJL.
 *
 * Bertanggung jawab atas:
 * - Validasi kelayakan jadwal saat pengajuan.
 * - Validasi ulang saat persetujuan.
 * - Pertukaran atomik dengan database locking.
 * - Rollback jika pertukaran gagal.
 *
 * Tidak bertanggung jawab atas:
 * - Authorization (controller).
 * - Notification (controller).
 * - Activity logging (controller).
 */
class TukarJadwalService
{
    /**
     * Validasi kelayakan jadwal saat pengajuan.
     *
     * @return array{valid: bool, message: ?string}
     */
    public function validateSwapRequest(
        Dinas $dinasPemohon,
        Dinas $dinasTujuan,
        int $pemohonId,
        int $penerimaId
    ): array {
        if ((int) $dinasPemohon->user_id !== $pemohonId) {
            return $this->invalid('Jadwal tidak milik Anda.');
        }

        if ((int) $dinasTujuan->user_id !== $penerimaId) {
            return $this->invalid('Jadwal tidak milik penerima yang dipilih.');
        }

        if ($pemohonId === $penerimaId) {
            return $this->invalid(
                'Tidak dapat mengajukan tukar jadwal dengan diri sendiri.'
            );
        }

        if ($dinasPemohon->status !== 'dijadwalkan') {
            return $this->invalid(
                'Jadwal Anda tidak dalam status dijadwalkan dan tidak dapat ditukar.'
            );
        }

        if ($dinasTujuan->status !== 'dijadwalkan') {
            return $this->invalid(
                'Jadwal penerima tidak dalam status dijadwalkan dan tidak dapat ditukar.'
            );
        }

        if ($this->hasScheduleStarted($dinasPemohon)) {
            return $this->invalid(
                'Jadwal Anda sudah dimulai atau sudah lewat. Pertukaran tidak dapat dilakukan.'
            );
        }

        if ($this->hasScheduleStarted($dinasTujuan)) {
            return $this->invalid(
                'Jadwal penerima sudah dimulai atau sudah lewat. Pertukaran tidak dapat dilakukan.'
            );
        }

        // Periksa konflik jadwal setelah pertukaran.
        if ($this->hasAssignmentConflict($pemohonId, $dinasTujuan)) {
            return $this->invalid(
                'Pertukaran akan menyebabkan Anda memiliki jadwal yang bertabrakan.'
            );
        }

        if ($this->hasAssignmentConflict($penerimaId, $dinasPemohon)) {
            return $this->invalid(
                'Pertukaran akan menyebabkan penerima memiliki jadwal yang bertabrakan.'
            );
        }

        if ($this->isScheduleLockedBySerahTerima($dinasPemohon)) {
            return $this->invalid(
                'Jadwal Anda sudah terkunci oleh serah terima dinas yang telah disetujui.'
            );
        }

        if ($this->isScheduleLockedBySerahTerima($dinasTujuan)) {
            return $this->invalid(
                'Jadwal penerima sudah terkunci oleh serah terima dinas yang telah disetujui.'
            );
        }

        if ($this->hasRealizationData($dinasPemohon)) {
            return $this->invalid(
                'Jadwal Anda memiliki data realisasi yang tidak boleh diganggu.'
            );
        }

        if ($this->hasRealizationData($dinasTujuan)) {
            return $this->invalid(
                'Jadwal penerima memiliki data realisasi yang tidak boleh diganggu.'
            );
        }

        if ($this->hasCatatanDinasLocked($dinasPemohon)) {
            return $this->invalid(
                'Catatan dinas pada jadwal Anda telah dikunci oleh admin.'
            );
        }

        if ($this->hasCatatanDinasLocked($dinasTujuan)) {
            return $this->invalid(
                'Catatan dinas pada jadwal penerima telah dikunci oleh admin.'
            );
        }

        if ($this->hasConflictingSwapRequest(
            (int) $dinasPemohon->id,
            (int) $dinasTujuan->id
        )) {
            return $this->invalid(
                'Jadwal ini sudah terlibat dalam permintaan tukar jadwal lain yang belum selesai.'
            );
        }

        return $this->valid();
    }

    /**
     * Memeriksa kelayakan satu jadwal milik pengguna
     * untuk digunakan dalam pengajuan tukar jadwal.
     *
     * Digunakan untuk menyaring jadwal pemohon sebelum
     * memilih jadwal milik penerima.
     */
    public function isOwnScheduleEligibleForSwap(
        Dinas $dinas,
        int $userId
    ): bool {
        if ((int) $dinas->user_id !== $userId) {
            return false;
        }

        if ($dinas->status !== 'dijadwalkan') {
            return false;
        }

        if ($this->hasScheduleStarted($dinas)) {
            return false;
        }

        if ($this->isScheduleLockedBySerahTerima($dinas)) {
            return false;
        }

        if ($this->hasRealizationData($dinas)) {
            return false;
        }

        if ($this->hasCatatanDinasLocked($dinas)) {
            return false;
        }

        // Jadwal tidak boleh terlibat dalam permintaan
        // tukar jadwal lain yang masih menunggu.
        if ($this->hasConflictingSwapRequest(
            (int) $dinas->id,
            (int) $dinas->id
        )) {
            return false;
        }

        return true;
    }

    /**
     * Validasi ulang sebelum persetujuan.
     *
     * @return array{valid: bool, message: ?string}
     */
    public function validateSwapApproval(
        PermintaanTukarJadwal $permintaan
    ): array {
        $permintaan->loadMissing([
            'dinasPemohon',
            'dinasTujuan',
        ]);

        $dinasPemohon = $permintaan->dinasPemohon;
        $dinasTujuan = $permintaan->dinasTujuan;

        if (! $dinasPemohon || ! $dinasTujuan) {
            return $this->invalid(
                'Salah satu jadwal telah dihapus dari sistem.'
            );
        }

        if ($permintaan->status !== 'menunggu') {
            return $this->invalid(
                'Permintaan tukar jadwal tidak lagi dalam status menunggu.'
            );
        }

        if ($dinasPemohon->status !== 'dijadwalkan') {
            return $this->invalid(
                'Status jadwal pemohon telah berubah. Pertukaran tidak dapat dilanjutkan.'
            );
        }

        if ($dinasTujuan->status !== 'dijadwalkan') {
            return $this->invalid(
                'Status jadwal penerima telah berubah. Pertukaran tidak dapat dilanjutkan.'
            );
        }

        if ((int) $dinasPemohon->user_id !== (int) $permintaan->pemohon_id) {
            return $this->invalid(
                'Kepemilikan jadwal pemohon telah berubah. Pertukaran tidak dapat dilanjutkan.'
            );
        }

        if ((int) $dinasTujuan->user_id !== (int) $permintaan->penerima_id) {
            return $this->invalid(
                'Kepemilikan jadwal penerima telah berubah. Pertukaran tidak dapat dilanjutkan.'
            );
        }

        if ($this->hasScheduleStarted($dinasPemohon)) {
            return $this->invalid(
                'Jadwal pemohon sudah dimulai atau sudah lewat.'
            );
        }

        if ($this->hasScheduleStarted($dinasTujuan)) {
            return $this->invalid(
                'Jadwal penerima sudah dimulai atau sudah lewat.'
            );
        }

        if ($this->isScheduleLockedBySerahTerima($dinasPemohon)) {
            return $this->invalid(
                'Jadwal pemohon telah terkunci oleh serah terima dinas.'
            );
        }

        if ($this->isScheduleLockedBySerahTerima($dinasTujuan)) {
            return $this->invalid(
                'Jadwal penerima telah terkunci oleh serah terima dinas.'
            );
        }

        if ($this->hasRealizationData($dinasPemohon)) {
            return $this->invalid(
                'Data realisasi telah ditambahkan pada jadwal pemohon.'
            );
        }

        if ($this->hasRealizationData($dinasTujuan)) {
            return $this->invalid(
                'Data realisasi telah ditambahkan pada jadwal penerima.'
            );
        }

        if ($this->hasCatatanDinasLocked($dinasPemohon)) {
            return $this->invalid(
                'Catatan dinas pada jadwal pemohon telah dikunci oleh admin.'
            );
        }

        if ($this->hasCatatanDinasLocked($dinasTujuan)) {
            return $this->invalid(
                'Catatan dinas pada jadwal penerima telah dikunci oleh admin.'
            );
        }

        if ($this->hasAssignmentConflict(
            (int) $permintaan->pemohon_id,
            $dinasTujuan
        )) {
            return $this->invalid(
                'Pertukaran akan menyebabkan konflik jadwal pada pemohon.'
            );
        }

        if ($this->hasAssignmentConflict(
            (int) $permintaan->penerima_id,
            $dinasPemohon
        )) {
            return $this->invalid(
                'Pertukaran akan menyebabkan konflik jadwal pada penerima.'
            );
        }

        if ($this->hasConflictingSwapRequest(
            (int) $dinasPemohon->id,
            (int) $dinasTujuan->id,
            (int) $permintaan->id
        )) {
            return $this->invalid(
                'Jadwal ini sudah terlibat dalam permintaan tukar jadwal lain yang masih menunggu.'
            );
        }

        return $this->valid();
    }

    /**
     * Eksekusi pertukaran secara atomik.
     *
     * Mengunci:
     * 1. Permintaan tukar jadwal.
     * 2. Kedua jadwal Dinas dengan urutan ID.
     *
     * Hanya mengubah user_id pada kedua record Dinas.
     *
     * @return array{
     *     success: bool,
     *     error: ?string,
     *     is_swap_failure: bool
     * }
     */
    public function executeSwap(
        PermintaanTukarJadwal $permintaan
    ): array {
        try {
            return DB::transaction(function () use ($permintaan) {
                // 1. Lock permintaan.
                $permintaanFresh = PermintaanTukarJadwal::query()
                    ->lockForUpdate()
                    ->find($permintaan->id);

                if (! $permintaanFresh) {
                    throw new SwapExecutionException(
                        'Permintaan tukar jadwal tidak ditemukan.'
                    );
                }

                if ($permintaanFresh->status !== 'menunggu') {
                    throw new SwapExecutionException(
                        'Permintaan tukar jadwal tidak lagi dalam status menunggu.'
                    );
                }

                if (
                    ! $permintaanFresh->dinas_pemohon_id ||
                    ! $permintaanFresh->dinas_tujuan_id
                ) {
                    throw new SwapExecutionException(
                        'Referensi jadwal tidak lengkap. Pertukaran tidak dapat dilakukan.'
                    );
                }

                // 2. Lock kedua jadwal secara konsisten berdasarkan ID.
                $ids = [
                    (int) $permintaanFresh->dinas_pemohon_id,
                    (int) $permintaanFresh->dinas_tujuan_id,
                ];

                sort($ids);

                if ($ids[0] === $ids[1]) {
                    throw new SwapExecutionException(
                        'Kedua jadwal tidak boleh merupakan jadwal yang sama.'
                    );
                }

                $lockedDinas = Dinas::query()
                    ->whereIn('id', $ids)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $dinasPemohon = $lockedDinas->get(
                    (int) $permintaanFresh->dinas_pemohon_id
                );

                $dinasPenerima = $lockedDinas->get(
                    (int) $permintaanFresh->dinas_tujuan_id
                );

                if (! $dinasPemohon || ! $dinasPenerima) {
                    throw new SwapExecutionException(
                        'Salah satu jadwal telah dihapus dari sistem.'
                    );
                }

                // 3. Validasi ulang setelah kedua jadwal terkunci.
                $validation = $this->validateLockedSwap(
                    $permintaanFresh,
                    $dinasPemohon,
                    $dinasPenerima
                );

                if (! $validation['valid']) {
                    throw new SwapExecutionException(
                        $validation['message']
                            ?? 'Jadwal tidak memenuhi syarat pertukaran.'
                    );
                }

                // 4. Simpan ID pemilik sebelum pertukaran.
                $pemohonId = (int) $dinasPemohon->user_id;
                $penerimaId = (int) $dinasPenerima->user_id;

                // 5. Hanya tukar user_id.
                $dinasPemohon->user_id = $penerimaId;
                $dinasPenerima->user_id = $pemohonId;

                $dinasPemohon->save();
                $dinasPenerima->save();

                // 6. Tandai permintaan berhasil dalam transaksi yang sama.
                $permintaanFresh->status = 'berhasil';
                $permintaanFresh->dijawab_pada = now();
                $permintaanFresh->selesai_pada = now();
                $permintaanFresh->save();

                return [
                    'success' => true,
                    'error' => null,
                    'is_swap_failure' => false,
                ];
            });
        } catch (SwapExecutionException $e) {
            // Kegagalan bisnis: aman untuk dicatat sebagai status gagal.
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_swap_failure' => true,
            ];
        } catch (Throwable $e) {
            // Kegagalan sistem: jangan otomatis dianggap kegagalan bisnis.
            report($e);

            return [
                'success' => false,
                'error' => 'Terjadi kesalahan sistem saat memproses pertukaran jadwal.',
                'is_swap_failure' => false,
            ];
        }
    }

    /**
     * Validasi yang dijalankan saat kedua jadwal sudah terkunci.
     *
     * @return array{valid: bool, message: ?string}
     */
    private function validateLockedSwap(
        PermintaanTukarJadwal $permintaan,
        Dinas $dinasPemohon,
        Dinas $dinasPenerima
    ): array {
        if ($permintaan->status !== 'menunggu') {
            return $this->invalid(
                'Permintaan tukar jadwal tidak lagi dalam status menunggu.'
            );
        }

        if ($dinasPemohon->status !== 'dijadwalkan') {
            return $this->invalid(
                'Status jadwal pemohon telah berubah.'
            );
        }

        if ($dinasPenerima->status !== 'dijadwalkan') {
            return $this->invalid(
                'Status jadwal penerima telah berubah.'
            );
        }

        if ((int) $dinasPemohon->user_id !== (int) $permintaan->pemohon_id) {
            return $this->invalid(
                'Kepemilikan jadwal pemohon telah berubah.'
            );
        }

        if ((int) $dinasPenerima->user_id !== (int) $permintaan->penerima_id) {
            return $this->invalid(
                'Kepemilikan jadwal penerima telah berubah.'
            );
        }

        if ($this->hasScheduleStarted($dinasPemohon)) {
            return $this->invalid(
                'Jadwal pemohon sudah dimulai atau sudah lewat.'
            );
        }

        if ($this->hasScheduleStarted($dinasPenerima)) {
            return $this->invalid(
                'Jadwal penerima sudah dimulai atau sudah lewat.'
            );
        }

        if ($this->isScheduleLockedBySerahTerima($dinasPemohon)) {
            return $this->invalid(
                'Jadwal pemohon telah terkunci oleh serah terima dinas.'
            );
        }

        if ($this->isScheduleLockedBySerahTerima($dinasPenerima)) {
            return $this->invalid(
                'Jadwal penerima telah terkunci oleh serah terima dinas.'
            );
        }

        if ($this->hasRealizationData($dinasPemohon)) {
            return $this->invalid(
                'Data realisasi telah ditambahkan pada jadwal pemohon.'
            );
        }

        if ($this->hasRealizationData($dinasPenerima)) {
            return $this->invalid(
                'Data realisasi telah ditambahkan pada jadwal penerima.'
            );
        }

        if ($this->hasCatatanDinasLocked($dinasPemohon)) {
            return $this->invalid(
                'Catatan dinas pemohon telah dikunci oleh admin.'
            );
        }

        if ($this->hasCatatanDinasLocked($dinasPenerima)) {
            return $this->invalid(
                'Catatan dinas penerima telah dikunci oleh admin.'
            );
        }

        if ($this->hasAssignmentConflict(
            (int) $permintaan->pemohon_id,
            $dinasPenerima
        )) {
            return $this->invalid(
                'Pertukaran akan menyebabkan konflik jadwal pada pemohon.'
            );
        }

        if ($this->hasAssignmentConflict(
            (int) $permintaan->penerima_id,
            $dinasPemohon
        )) {
            return $this->invalid(
                'Pertukaran akan menyebabkan konflik jadwal pada penerima.'
            );
        }

        if ($this->hasConflictingSwapRequest(
            (int) $dinasPemohon->id,
            (int) $dinasPenerima->id,
            (int) $permintaan->id
        )) {
            return $this->invalid(
                'Jadwal ini sudah terlibat dalam permintaan tukar jadwal lain yang masih menunggu.'
            );
        }

        return $this->valid();
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /**
     * Membuat hasil validasi gagal.
     *
     * @return array{valid: false, message: string}
     */
    private function invalid(string $message): array
    {
        return [
            'valid' => false,
            'message' => $message,
        ];
    }

    /**
     * Membuat hasil validasi berhasil.
     *
     * @return array{valid: true, message: null}
     */
    private function valid(): array
    {
        return [
            'valid' => true,
            'message' => null,
        ];
    }

    /**
     * Periksa apakah waktu mulai dinas sudah tiba.
     *
     * Menggunakan cast datetime pada model Dinas.
     */
    private function hasScheduleStarted(Dinas $dinas): bool
    {
        return $dinas->jam_mulai !== null
            && $dinas->jam_mulai->lte(now());
    }

    /**
     * Periksa konflik penugasan setelah pertukaran.
     *
     * Mengecualikan jadwal target itu sendiri.
     */
    private function hasAssignmentConflict(
        int $userId,
        Dinas $targetDinas
    ): bool {
        return Dinas::query()
            ->where('user_id', $userId)
            ->where('id', '!=', $targetDinas->id)
            ->where('jpl_id', $targetDinas->jpl_id)
            ->whereDate('tanggal_dinas', $targetDinas->tanggal_dinas)
            ->where('shift_id', $targetDinas->shift_id)
            ->exists();
    }

    /**
     * Periksa apakah jadwal terkunci oleh serah terima
     * yang telah disetujui.
     */
    private function isScheduleLockedBySerahTerima(Dinas $dinas): bool
    {
        return $dinas->serahTerima()
            ->where('status_review', 'disetujui')
            ->exists();
    }

    /**
     * Periksa apakah jadwal memiliki data realisasi KA
     * atau realisasi inventaris.
     */
    private function hasRealizationData(Dinas $dinas): bool
    {
        return $dinas->realisasiKa()->exists()
            || $dinas->realisasiInventaris()->exists();
    }

    /**
     * Periksa apakah catatan dinas telah disetujui/dikunci.
     */
    private function hasCatatanDinasLocked(Dinas $dinas): bool
    {
        return $dinas->catatanDinas()
            ->where('status_review', 'disetujui')
            ->exists();
    }

    /**
     * Periksa permintaan tukar lain yang masih menunggu
     * dan menggunakan salah satu jadwal yang sedang diproses.
     */
    private function hasConflictingSwapRequest(
        int $dinasIdPemohon,
        int $dinasIdTujuan,
        ?int $excludePermintaanId = null
    ): bool {
        $query = PermintaanTukarJadwal::query()
            ->where('status', 'menunggu')
            ->where(function (Builder $q) use (
                $dinasIdPemohon,
                $dinasIdTujuan
            ) {
                $q->whereIn(
                    'dinas_pemohon_id',
                    [$dinasIdPemohon, $dinasIdTujuan]
                )->orWhereIn(
                    'dinas_tujuan_id',
                    [$dinasIdPemohon, $dinasIdTujuan]
                );
            });

        if ($excludePermintaanId !== null) {
            $query->where('id', '!=', $excludePermintaanId);
        }

        return $query->exists();
    }
}
