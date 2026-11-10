<?php

namespace App\Http\Controllers\Api;

use App\Events\CoachingMessageReceived;
use App\Http\Controllers\Controller;
use App\Models\CoachingMessage;
use App\Services\CoachingMessageRecipientResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;

class CoachingMessageController extends Controller
{
    /**
     * Receive a signed message from the external inspection system.
     */
    public function receive(Request $request, CoachingMessageRecipientResolver $recipientResolver): JsonResponse
    {
        $rawBody = $request->getContent();

        if (! $request->isJson()) {
            return $this->invalidPayload(['body' => ['Content-Type application/json wajib digunakan.']]);
        }

        try {
            $decodedObject = json_decode($rawBody, flags: JSON_THROW_ON_ERROR);

            if (! $decodedObject instanceof \stdClass) {
                return $this->invalidPayload(['body' => ['Payload harus berupa objek JSON.']]);
            }

            if (
                property_exists($decodedObject, 'payload')
                && $decodedObject->payload !== null
                && ! $decodedObject->payload instanceof \stdClass
            ) {
                return $this->invalidPayload(['payload' => ['Payload tambahan harus berupa objek JSON.']]);
            }

            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->invalidPayload(['body' => ['Payload JSON tidak valid.']]);
        }

        $allowedFields = [
            'message_uuid',
            'inspection_id',
            'recipient_user_id',
            'recipient_nipp',
            'recipient_nipkwt',
            'subject',
            'body',
            'payload',
            'expires_at',
            'idempotency_key',
        ];

        if (array_diff(array_keys($payload), $allowedFields) !== []) {
            return $this->invalidPayload(['payload' => ['Payload memuat field yang tidak didukung.']]);
        }

        $validator = Validator::make($payload, [
            'message_uuid' => ['required', 'uuid'],
            'inspection_id' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) && ! is_int($value)) {
                        $fail('ID pemeriksaan harus berupa teks atau angka.');

                        return;
                    }

                    if (mb_strlen((string) $value) > 128) {
                        $fail('ID pemeriksaan terlalu panjang.');
                    }
                },
            ],
            'recipient_user_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'recipient_nipp' => ['nullable', 'string', 'max:255', 'required_without:recipient_nipkwt'],
            'recipient_nipkwt' => ['nullable', 'string', 'max:255', 'required_without:recipient_nipp'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'payload' => ['sometimes', 'nullable', 'array'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
            'idempotency_key' => ['required', 'string', 'max:128'],
        ], [
            'required' => 'Field :attribute wajib diisi.',
            'uuid' => 'Format :attribute tidak valid.',
            'required_without' => 'NIPP atau NIPKWT wajib diisi.',
            'recipient_user_id.integer' => 'ID pengguna pengirim tidak valid.',
            'recipient_user_id.min' => 'ID pengguna pengirim tidak valid.',
            'recipient_nipp.string' => 'Format NIPP tidak valid.',
            'recipient_nipp.max' => 'NIPP terlalu panjang.',
            'recipient_nipkwt.string' => 'Format NIPKWT tidak valid.',
            'recipient_nipkwt.max' => 'NIPKWT terlalu panjang.',
            'subject.string' => 'Judul harus berupa teks.',
            'subject.max' => 'Judul terlalu panjang.',
            'body.string' => 'Isi pesan harus berupa teks.',
            'body.max' => 'Isi pesan terlalu panjang.',
            'payload.array' => 'Payload tambahan harus berupa objek.',
            'expires_at.date' => 'Format expires_at tidak valid.',
            'idempotency_key.string' => 'Format idempotency_key tidak valid.',
            'idempotency_key.max' => 'idempotency_key terlalu panjang.',
        ]);

        if ($validator->fails()) {
            return $this->invalidPayload($validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $sourceSystem = (string) $request->attributes->get('coaching_message_source_system');
        $canonicalPayload = $this->canonicalize($validated);
        $requestHash = hash(
            'sha256',
            json_encode($canonicalPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );

        try {
            $result = DB::transaction(function () use ($validated, $sourceSystem, $requestHash, $recipientResolver): array {
                $existing = $this->findExistingMessages(
                    $sourceSystem,
                    $validated['idempotency_key'],
                    $validated['message_uuid'],
                );

                if ($existing->isNotEmpty()) {
                    if ($existing->count() !== 1 || ! hash_equals($existing->first()->request_hash, $requestHash)) {
                        return ['status' => 'conflict'];
                    }

                    return ['status' => 'duplicate', 'message' => $existing->first()];
                }

                $recipient = $recipientResolver->resolve($validated);

                if ($recipient === null) {
                    return ['status' => 'recipient_rejected'];
                }

                $receivedAt = now();
                $message = CoachingMessage::query()->create([
                    'source_system' => $sourceSystem,
                    'message_uuid' => $validated['message_uuid'],
                    'inspection_id' => isset($validated['inspection_id'])
                        ? (string) $validated['inspection_id']
                        : null,
                    'recipient_user_id' => $recipient['user']->getKey(),
                    'matched_identifier_type' => $recipient['matched_identifier_type'],
                    'subject' => $validated['subject'],
                    'body' => $validated['body'],
                    'payload' => $validated['payload'] ?? null,
                    'idempotency_key' => $validated['idempotency_key'],
                    'request_hash' => $requestHash,
                    'received_at' => $receivedAt,
                    'expires_at' => $receivedAt->copy()->addHours(72),
                ]);

                return ['status' => 'created', 'message' => $message];
            });
        } catch (QueryException $exception) {
            try {
                $result = DB::transaction(fn (): array => $this->duplicateResult(
                    $sourceSystem,
                    $validated['idempotency_key'],
                    $validated['message_uuid'],
                    $requestHash,
                ));
            } catch (QueryException $retryException) {
                Log::error('Coaching message persistence failed.', [
                    'sql_state' => (string) $retryException->getCode(),
                ]);

                return response()->json(['message' => 'Layanan sementara tidak tersedia.'], 503);
            }

            if ($result['status'] === 'not_found') {
                Log::error('Coaching message persistence failed.', [
                    'sql_state' => (string) $exception->getCode(),
                ]);

                return response()->json(['message' => 'Layanan sementara tidak tersedia.'], 503);
            }
        }

        if ($result['status'] === 'recipient_rejected') {
            Log::notice('Coaching message target could not be verified.');

            return $this->invalidPayload([
                'recipient' => ['Target penerima tidak dapat diverifikasi.'],
            ]);
        }

        if ($result['status'] === 'conflict') {
            Log::notice('Coaching message idempotency conflict.');

            return response()->json(['message' => 'Idempotency key conflict.'], 409);
        }

        /** @var CoachingMessage $message */
        $message = $result['message'];

        if ($result['status'] === 'created') {
            CoachingMessageReceived::dispatch(
                (string) $message->getKey(),
                (string) $message->recipient_user_id,
            );

            Log::info('Coaching message accepted.', [
                'source_system' => $sourceSystem,
                'message_uuid' => $message->message_uuid,
            ]);

            return response()->json([
                'status' => 'ok',
                'message_uuid' => $message->message_uuid,
            ], 201);
        }

        return response()->json([
            'status' => 'ok',
            'message_uuid' => $message->message_uuid,
        ], 200);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function invalidPayload(array $errors): JsonResponse
    {
        return response()->json([
            'message' => 'Payload tidak valid.',
            'errors' => $errors,
        ], 422);
    }

    /**
     * @return Collection<int, CoachingMessage>
     */
    private function findExistingMessages(
        string $sourceSystem,
        string $idempotencyKey,
        string $messageUuid,
    ): Collection {
        return CoachingMessage::query()
            ->where('source_system', $sourceSystem)
            ->where(function ($query) use ($idempotencyKey, $messageUuid): void {
                $query->where('idempotency_key', $idempotencyKey)
                    ->orWhere('message_uuid', $messageUuid);
            })
            ->lockForUpdate()
            ->get();
    }

    private function duplicateResult(
        string $sourceSystem,
        string $idempotencyKey,
        string $messageUuid,
        string $requestHash,
    ): array {
        $existing = $this->findExistingMessages($sourceSystem, $idempotencyKey, $messageUuid);

        if ($existing->isEmpty()) {
            return ['status' => 'not_found'];
        }

        if ($existing->count() !== 1 || ! hash_equals($existing->first()->request_hash, $requestHash)) {
            return ['status' => 'conflict'];
        }

        return ['status' => 'duplicate', 'message' => $existing->first()];
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
