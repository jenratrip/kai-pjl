<?php

namespace App\Console\Commands;

use App\Services\JadwalKaVersionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('jadwal-ka:activate-due-batches')]
#[Description('Activate scheduled Jadwal KA batches whose effective date has arrived.')]
class ActivateDueJadwalKaBatches extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(JadwalKaVersionService $versionService): int
    {
        $failed = false;

        foreach ($versionService->dueScheduledBatchIds() as $batchId) {
            try {
                if ($versionService->activateScheduledBatch((int) $batchId)) {
                    $this->info("Batch jadwal KA #{$batchId} diproses.");
                }
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Batch jadwal KA #{$batchId} gagal diproses.");
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
