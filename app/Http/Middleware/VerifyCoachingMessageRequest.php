<?php

namespace App\Http\Middleware;

use App\Models\CoachingMessageNonce;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class VerifyCoachingMessageRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('services.coaching_messages.api_key');
        $sharedSecret = config('services.coaching_messages.shared_secret');
        $sourceSystem = config('services.coaching_messages.source_system');

        if (
            ! is_string($apiKey) || $apiKey === ''
            || ! is_string($sharedSecret) || $sharedSecret === ''
            || strlen($apiKey) < 32
            || strlen($sharedSecret) < 32
            || ! is_string($sourceSystem) || $sourceSystem === '' || mb_strlen($sourceSystem) > 64
        ) {
            Log::critical('Coaching message receiver is not configured.');

            return response()->json(['message' => 'Layanan penerimaan belum tersedia.'], 503);
        }

        $providedApiKey = $request->header('X-Api-Key');

        if (
            ! is_string($providedApiKey)
            || strlen($providedApiKey) > 256
            || ! hash_equals($apiKey, $providedApiKey)
        ) {
            return $this->reject('invalid_api_key');
        }

        $timestampHeader = $request->header('X-Timestamp');
        $nonce = $request->header('X-Nonce');
        $signature = $request->header('X-Signature');
        $maxBodyBytes = max(1, (int) config('services.coaching_messages.max_body_bytes', 32768));

        if (strlen($request->getContent()) > $maxBodyBytes) {
            return response()->json(['message' => 'Payload terlalu besar.'], 413);
        }

        if (
            ! is_string($timestampHeader)
            || ! is_string($nonce)
            || preg_match('/\A[A-Za-z0-9._~-]{1,128}\z/D', $nonce) !== 1
            || ! is_string($signature)
            || preg_match('/\A[a-fA-F0-9]{64}\z/D', $signature) !== 1
        ) {
            return $this->reject('invalid_authentication_headers');
        }

        $timestamp = $this->parseTimestamp($timestampHeader);
        $toleranceSeconds = max(0, (int) config('services.coaching_messages.timestamp_tolerance_seconds', 300));

        if (
            $timestamp === null
            || abs(now()->getTimestamp() - $timestamp->getTimestamp()) > $toleranceSeconds
        ) {
            return $this->reject('invalid_timestamp');
        }

        $signedContent = $timestampHeader.':'.$nonce.':'.$request->getContent();
        $expectedSignature = hash_hmac('sha256', $signedContent, $sharedSecret);

        if (! hash_equals($expectedSignature, strtolower($signature))) {
            return $this->reject('invalid_signature', 'Signature tidak valid.');
        }

        try {
            $nonceInserted = DB::table((new CoachingMessageNonce)->getTable())->insertOrIgnore([
                'source_system' => $sourceSystem,
                'nonce' => $nonce,
                'request_timestamp' => $timestamp->utc(),
                'expires_at' => now()->addSeconds(($toleranceSeconds * 2) + 60),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $exception) {
            Log::error('Coaching message anti-replay storage failed.', [
                'sql_state' => (string) $exception->getCode(),
            ]);

            return response()->json(['message' => 'Layanan sementara tidak tersedia.'], 503);
        }

        if ($nonceInserted !== 1) {
            return $this->reject('replayed_nonce');
        }

        $request->attributes->set('coaching_message_source_system', $sourceSystem);

        return $next($request);
    }

    private function parseTimestamp(string $timestamp): ?CarbonImmutable
    {
        if (preg_match('/\A[0-9]{10}\z/D', $timestamp) === 1) {
            return CarbonImmutable::createFromTimestampUTC((int) $timestamp);
        }

        if (preg_match(
            '/\A([0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2})(?:\.([0-9]{1,6}))?(Z|[+-][0-9]{2}:[0-9]{2})\z/D',
            $timestamp,
            $matches,
        ) !== 1) {
            return null;
        }

        $fraction = str_pad($matches[2] ?? '', 6, '0');
        $timezone = $matches[3] === 'Z' ? '+00:00' : $matches[3];
        $parsed = \DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i:s.uP',
            $matches[1].'.'.$fraction.$timezone,
        );
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            $parsed === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        ) {
            return null;
        }

        return CarbonImmutable::instance($parsed)->utc();
    }

    private function reject(string $reason, string $message = 'Request tidak valid.'): JsonResponse
    {
        Log::warning('Coaching message request rejected.', ['reason' => $reason]);

        return response()->json(['message' => $message], 401);
    }
}
