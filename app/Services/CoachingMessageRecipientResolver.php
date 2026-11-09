<?php

namespace App\Services;

use App\Models\User;

class CoachingMessageRecipientResolver
{
    /**
     * Resolve a payload to exactly one PJL account using its staff identity.
     *
     * @param  array<string, mixed>  $payload
     * @return array{user: User, matched_identifier_type: string}|null
     */
    public function resolve(array $payload): ?array
    {
        $matches = [];

        foreach (['recipient_nipp' => 'reguler', 'recipient_nipkwt' => 'wakilan'] as $field => $jenisPetugas) {
            $identifier = $payload[$field] ?? null;

            if (! is_string($identifier) || $identifier === '') {
                continue;
            }

            $user = User::query()
                ->with('role')
                ->where('nip', $identifier)
                ->first();

            if ($user === null) {
                continue;
            }

            $jenisTersimpan = $user->jenis_petugas;
            $jenisCocok = $jenisPetugas === 'reguler'
                ? in_array($jenisTersimpan, ['reguler', null], true)
                : $jenisTersimpan === $jenisPetugas;

            if (! $user->hasRole('pjl') || ! $user->is_active || ! $jenisCocok) {
                return null;
            }

            $matches[$user->getKey()] = [
                'user' => $user,
                'matched_identifier_type' => $jenisPetugas === 'reguler' ? 'nipp' : 'nipkwt',
            ];
        }

        if (count($matches) !== 1) {
            return null;
        }

        return array_values($matches)[0];
    }
}
