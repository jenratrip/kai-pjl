<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountPasswordResetNotification;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AccountPasswordResetService
{
    public function __construct(private readonly Dispatcher $notifications) {}

    public function sendResetLink(string $nip, string $email): void
    {
        $normalizedNip = trim($nip);
        $normalizedEmail = mb_strtolower(trim($email));

        $user = User::query()
            ->where('nip', $normalizedNip)
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if (! $user) {
            return;
        }

        DB::transaction(function () use ($user, $normalizedEmail): void {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedUser || mb_strtolower(trim((string) $lockedUser->email)) !== $normalizedEmail) {
                return;
            }

            $existingToken = DB::table('user_password_reset_tokens')
                ->where('user_id', $lockedUser->getKey())
                ->lockForUpdate()
                ->first();

            if (
                $existingToken?->created_at
                && CarbonImmutable::parse($existingToken->created_at)->addSeconds(60)->isFuture()
            ) {
                return;
            }

            $mailer = (string) config('mail.default');

            if (! $this->supportsPrivateMailDelivery($mailer)) {
                throw new RuntimeException('Password reset requires a non-logging mail transport.');
            }

            $token = Str::random(64);

            DB::table('user_password_reset_tokens')->updateOrInsert(
                ['user_id' => $lockedUser->getKey()],
                [
                    'email_hash' => $this->emailHash($normalizedEmail),
                    'token' => hash('sha256', $token),
                    'created_at' => now(),
                ],
            );

            $this->notifications->send($lockedUser, new AccountPasswordResetNotification($token));
        });
    }

    public function hasValidToken(int $userId, string $token): bool
    {
        $user = User::query()->find($userId);

        if (! $user) {
            return false;
        }

        $storedToken = DB::table('user_password_reset_tokens')
            ->where('user_id', $user->getKey())
            ->first();

        return $storedToken !== null && $this->matchesToken($user, $storedToken, $token);
    }

    public function resetPassword(int $userId, string $token, string $password): bool
    {
        return DB::transaction(function () use ($userId, $token, $password): bool {
            $user = User::query()
                ->whereKey($userId)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return false;
            }

            $storedToken = DB::table('user_password_reset_tokens')
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();

            if ($storedToken === null || ! $this->matchesToken($user, $storedToken, $token)) {
                return false;
            }

            $user->password = $password;
            $user->save();

            DB::table('user_password_reset_tokens')
                ->where('user_id', $user->getKey())
                ->delete();

            return true;
        });
    }

    private function matchesToken(User $user, object $storedToken, string $token): bool
    {
        $currentEmail = mb_strtolower(trim((string) $user->email));

        if ($currentEmail === '' || ! hash_equals((string) $storedToken->email_hash, $this->emailHash($currentEmail))) {
            return false;
        }

        if (! hash_equals((string) $storedToken->token, hash('sha256', $token))) {
            return false;
        }

        $expiresAt = CarbonImmutable::parse($storedToken->created_at)
            ->addMinutes((int) config('auth.passwords.users.expire', 60));

        return $expiresAt->isFuture();
    }

    private function emailHash(string $email): string
    {
        return hash('sha256', $email);
    }

    /**
     * Reject log/array transports and failover configurations that could log reset links.
     *
     * @param  array<string, true>  $visited
     */
    private function supportsPrivateMailDelivery(string $mailerName, array $visited = []): bool
    {
        if (isset($visited[$mailerName]) || in_array($mailerName, ['log', 'array'], true)) {
            return false;
        }

        $visited[$mailerName] = true;
        $mailer = config('mail.mailers.'.$mailerName);

        if (! is_array($mailer) || in_array($mailer['transport'] ?? null, ['log', 'array'], true)) {
            return false;
        }

        if (in_array($mailer['transport'] ?? null, ['failover', 'roundrobin'], true)) {
            $fallbackMailers = $mailer['mailers'] ?? [];

            if ($fallbackMailers === []) {
                return false;
            }

            foreach ($fallbackMailers as $fallbackMailer) {
                if (! $this->supportsPrivateMailDelivery((string) $fallbackMailer, $visited)) {
                    return false;
                }
            }
        }

        return true;
    }
}
