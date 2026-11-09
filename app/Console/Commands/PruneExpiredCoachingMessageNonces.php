<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Signature('app:prune-expired-coaching-message-nonces')]
#[Description('Remove anti-replay nonces retained for more than 30 days after expiration.')]
class PruneExpiredCoachingMessageNonces extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = now()->subDays(30);
        $deletedCount = 0;

        DB::table('coaching_message_nonces')
            ->where('expires_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(500, function (Collection $nonces) use ($cutoff, &$deletedCount): void {
                $nonceIds = $nonces->pluck('id');

                $deletedCount += DB::table('coaching_message_nonces')
                    ->whereIn('id', $nonceIds)
                    ->where('expires_at', '<', $cutoff)
                    ->delete();
            });

        $this->info("Nonce kedaluwarsa yang dihapus: {$deletedCount}");

        return self::SUCCESS;
    }
}
