# Kontrak Integrasi Pesan Pembinaan KAI-PJL

Dokumen ini menjelaskan kontrak berdasarkan implementasi penerima yang diperiksa pada repository. Nilai credential, akun, dan alamat server di bawah adalah placeholder fiktif.

## Endpoint

- Method: `POST`
- Path: `/api/v1/coaching-messages/receive`
- Content type: `application/json`
- Rate limit route: 60 request per menit. Pengirim tetap perlu memakai retry dengan jeda dan backoff.
- Gunakan HTTPS dengan sertifikat TLS yang valid. Jangan mengirim API key atau shared secret melalui HTTP. Infrastruktur dapat menambahkan firewall/IP allowlist, tetapi allowlist tidak dikonfigurasi oleh endpoint ini.

`source_system` ditetapkan dari konfigurasi server `COACHING_MESSAGE_SOURCE_SYSTEM`, bukan dari header atau field JSON. Panjangnya maksimal 64 karakter.

## Header autentikasi

Semua header berikut wajib:

| Header | Format |
| --- | --- |
| `X-Api-Key` | Harus sama persis dengan API key yang dikonfigurasi. Nilai konfigurasi minimal 32 byte; nilai header maksimal 256 byte. |
| `X-Timestamp` | Unix timestamp 10 digit dalam detik, atau timestamp RFC3339/ISO 8601 dengan zona waktu eksplisit (`Z` atau `±HH:MM`). Pecahan detik opsional, 1–6 digit. |
| `X-Nonce` | 1–128 karakter ASCII dari `[A-Za-z0-9._~-]`; tidak boleh dipakai ulang untuk `source_system` yang sama. |
| `X-Signature` | 64 digit hexadecimal SHA-256 HMAC; huruf besar/kecil diterima. |

`X-Api-Key` tidak dimasukkan ke string HMAC. API key dan shared secret harus disimpan di secret manager atau konfigurasi environment masing-masing aplikasi; jangan ditulis ke log atau dikirim ke pengguna.

### HMAC persis

1. Serialisasikan objek JSON satu kali dan simpan hasilnya sebagai string byte `rawBody`.
2. Gunakan nilai header `X-Timestamp` persis seperti dikirim, nonce persis seperti dikirim, dan byte body persis seperti dikirim.
3. Bentuk string bertanda tangan:

```text
timestamp + ":" + nonce + ":" + rawBody
```

4. Hitung `HMAC-SHA256(signedContent, sharedSecret)` dan kirim hasil heksadesimal lowercase pada `X-Signature`.

```php
$url = 'https://kai-pjl.example.test/api/v1/coaching-messages/receive';
$apiKey = getenv('COACHING_MESSAGE_API_KEY');
$sharedSecret = getenv('COACHING_MESSAGE_SHARED_SECRET');

$payload = [
    'message_uuid' => '5b3cf118-63d2-4b41-9a5f-03ce28f5a42e',
    'inspection_id' => 'INSPEKSI-FIKTIF-2026-001',
    'recipient_nipp' => 'NIPP-FIKTIF-001',
    'subject' => 'Tindak lanjut pemeriksaan',
    'body' => 'Mohon lakukan pemeriksaan ulang pada lokasi yang dicatat.',
    'payload' => ['kode_lokasi' => 'JPL-FIKTIF-12'],
    'idempotency_key' => '5b3cf118-63d2-4b41-9a5f-03ce28f5a42e',
];

// Jangan encode ulang $rawBody setelah signature dihitung.
$rawBody = json_encode(
    $payload,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
);
$timestamp = (string) time();
$nonce = bin2hex(random_bytes(24));
$signedContent = $timestamp.':'.$nonce.':'.$rawBody;
$signature = hash_hmac('sha256', $signedContent, $sharedSecret);

$response = Illuminate\Support\Facades\Http::withHeaders([
    'Accept' => 'application/json',
    'X-Api-Key' => $apiKey,
    'X-Timestamp' => $timestamp,
    'X-Nonce' => $nonce,
    'X-Signature' => $signature,
])->withBody($rawBody, 'application/json')->send('POST', $url);
```

`rawBody` harus dikirim apa adanya. Jangan memformat ulang JSON, mengubah urutan/representasi angka, normalisasi newline, atau mengubah encoding setelah signature dibuat. Perubahan satu byte saja membuat signature tidak cocok.

## JSON request

Root JSON wajib berupa object. Field root yang tidak tercantum di bawah ditolak.

| Field | Wajib | Tipe dan validasi | Perilaku penerima |
| --- | --- | --- | --- |
| `message_uuid` | Ya | String UUID valid | Identitas pesan unik per source system. |
| `inspection_id` | Tidak | String atau integer, maksimal 128 karakter setelah dijadikan string; boleh `null` | Disimpan sebagai referensi eksternal, bukan foreign key lokal. |
| `recipient_nipp` | Salah satu identitas wajib | String maksimal 255 karakter atau `null`; diwajibkan jika `recipient_nipkwt` tidak ada | Dicocokkan persis ke `users.nip`, dengan jenis petugas reguler atau legacy `null`. |
| `recipient_nipkwt` | Salah satu identitas wajib | String maksimal 255 karakter atau `null`; diwajibkan jika `recipient_nipp` tidak ada | Dicocokkan persis ke `users.nip`, dengan `jenis_petugas=wakilan`. |
| `recipient_user_id` | Tidak | Integer positif atau `null` | Tidak digunakan untuk memilih akun lokal. ID dari database pengirim tidak boleh dianggap sebagai ID KAI-PJL. |
| `subject` | Ya | String maksimal 255 karakter | Disimpan sebagai judul pesan. |
| `body` | Ya | String maksimal 10.000 karakter | Disimpan sebagai isi pesan. Batas keseluruhan body HTTP adalah 32.768 byte secara default dan dapat dikonfigurasi. |
| `payload` | Tidak | Object JSON atau `null` | Disimpan sebagai JSON tambahan; bukan pengganti field kontrak. |
| `expires_at` | Tidak | Tanggal/waktu yang dapat diparse Laravel atau `null` | Divalidasi, tetapi tidak menentukan expiry aktual. Penerima mengabaikannya untuk perhitungan masa aktif. |
| `idempotency_key` | Ya | String maksimal 128 karakter | Unik per source system dan dibandingkan case-sensitive pada skema yang dituju. |

Minimal contoh request:

```json
{
  "message_uuid": "5b3cf118-63d2-4b41-9a5f-03ce28f5a42e",
  "inspection_id": "INSPEKSI-FIKTIF-2026-001",
  "recipient_nipp": "NIPP-FIKTIF-001",
  "subject": "Tindak lanjut pemeriksaan",
  "body": "Mohon lakukan pemeriksaan ulang pada lokasi yang dicatat.",
  "payload": {
    "kode_lokasi": "JPL-FIKTIF-12"
  },
  "idempotency_key": "5b3cf118-63d2-4b41-9a5f-03ce28f5a42e"
}
```

`recipient_nipp` dan `recipient_nipkwt` dapat dikirim bersama. Jika keduanya menunjuk akun PJL berbeda, request ditolak. Jika hanya salah satunya cocok dan identitas lain tidak ditemukan, kandidat yang cocok dapat digunakan. Akun yang ditemukan tetapi non-PJL, nonaktif, atau jenis petugasnya tidak cocok menyebabkan target ditolak.

## Resolusi penerima

- NIPP dicocokkan persis ke kolom lokal `users.nip`; akun harus ber-role `pjl`, aktif (`is_active=true`), dan `jenis_petugas=reguler`. Nilai `jenis_petugas=null` juga diterima sebagai akun reguler legacy.
- NIPKWT dicocokkan persis ke `users.nip`; akun harus ber-role `pjl`, aktif, dan `jenis_petugas=wakilan`.
- `recipient_user_id` dari pengirim tidak dipakai dalam resolusi. Penerima yang disimpan adalah primary key akun lokal hasil pencocokan NIPP/NIPKWT.
- Jika tidak ada tepat satu akun lokal yang dapat diverifikasi, pesan tidak disimpan dan respons tidak mengungkap apakah identitas tertentu terdaftar.
- Jika kedua identifier mengarah ke dua akun berbeda, request ditolak. Tidak ada prioritas otomatis antara NIPP dan NIPKWT.

## Timestamp, nonce, dan retry

- Toleransi selisih timestamp default adalah ±300 detik; nilai aktual dikendalikan `COACHING_MESSAGE_TIMESTAMP_TOLERANCE_SECONDS` pada server penerima. Jam kedua server harus tersinkronisasi.
- Nonce disimpan dengan scope `source_system` dan unique constraint. Nonce yang sama ditolak sebagai replay, termasuk jika request sebelumnya gagal pada validasi payload setelah middleware autentikasi berhasil.
- Setiap percobaan HTTP baru harus memakai timestamp, nonce, dan signature baru. Untuk retry pesan yang sama, pertahankan semua nilai field JSON tervalidasi, termasuk `message_uuid`, `idempotency_key`, `recipient_user_id`, dan `expires_at`. Dua field terakhir tidak menentukan penerima/masa aktif, tetapi tetap ikut dalam hash idempotency.
- Pesan dianggap duplikat identik bila request tervalidasi menghasilkan hash canonical yang sama. Urutan key pada object JSON tidak memengaruhi hash; signature tetap harus dihitung atas raw body percobaan tersebut. Perubahan payload dengan UUID atau idempotency key yang sama ditolak sebagai konflik.
- Jangan mengirim dua request bersamaan dengan nonce yang sama. Unique nonce memastikan hanya satu yang diterima.
- Nonce baru dapat dibersihkan setelah `expires_at` lebih lama dari 30 hari. Pembersihan nonce tidak menghapus pesan. Jadwal command pembersih berjalan harian pukul 02:30 pada konfigurasi aplikasi.

## Masa aktif pesan

Waktu server KAI-PJL menjadi acuan: `received_at` ditetapkan saat pesan diterima dan `expires_at` dihitung sebagai `received_at + 72 jam`. Nilai `expires_at` dari pengirim tidak memperpanjang atau memperpendek masa aktif. Setelah kedaluwarsa, pesan tidak lagi ditampilkan sebagai pesan aktif, tetapi record tetap tersimpan untuk audit.

## Respons

### Pesan baru tersimpan

HTTP `201 Created`:

```json
{
  "status": "ok",
  "message_uuid": "5b3cf118-63d2-4b41-9a5f-03ce28f5a42e"
}
```

### Retry identik

HTTP `200 OK` dengan body yang sama. Tidak dibuat record pesan atau notifikasi kedua.

### Kegagalan yang relevan

| HTTP | Kondisi | Contoh body |
| --- | --- | --- |
| `401 Unauthorized` | API key salah, header autentikasi tidak valid, timestamp di luar toleransi, signature salah, atau nonce telah dipakai. | `{"message":"Request tidak valid."}` |
| `401 Unauthorized` | Signature salah (pesan respons khusus yang saat ini dikembalikan). | `{"message":"Signature tidak valid."}` |
| `413 Content Too Large` | Body melampaui batas konfigurasi. | `{"message":"Payload terlalu besar."}` |
| `422 Unprocessable Entity` | JSON/field tidak valid, ada field root tak dikenal, atau target tidak dapat diverifikasi. Respons target tidak membocorkan identitas yang gagal. | `{"message":"Payload tidak valid.","errors":{"recipient":["Target penerima tidak dapat diverifikasi."]}}` |
| `409 Conflict` | `message_uuid` atau `idempotency_key` digunakan ulang dengan payload berbeda, atau keduanya menunjuk record berbeda. | `{"message":"Idempotency key conflict."}` |
| `429 Too Many Requests` | Batas throttle route terlampaui. | Respons throttle Laravel; pengirim perlu menunggu sesuai header/aturan retry yang dikembalikan. |
| `503 Service Unavailable` | Credential penerima tidak dikonfigurasi/terlalu pendek atau penyimpanan nonce/pesan gagal. | `{"message":"Layanan penerimaan belum tersedia."}` atau `{"message":"Layanan sementara tidak tersedia."}` |

Untuk kesalahan validasi `422`, `errors` berisi nama field dan daftar pesan validasi. Bentuk persis detailnya mengikuti kesalahan yang ditemukan. Kesalahan server tak terduga dapat menghasilkan respons 5xx dari exception handler; jangan menganggapnya sebagai penerimaan sukses.

## Skema dan kesiapan penerima

Penerimaan memerlukan tabel `coaching_messages` dan `coaching_message_nonces`. Migration yang tersedia membuat unique key source/message UUID, source/idempotency key, source/nonce, index penerima/expiry/unread, serta foreign key `recipient_user_id` ke `users.id` dengan `RESTRICT` saat user dihapus. Migration tidak dijalankan dalam pemeriksaan kontrak ini; pastikan migration sudah diterapkan pada lingkungan target sebelum mengirim traffic. Tanpa tabel, endpoint tidak dapat menerima pesan dan akan gagal secara layanan.

## Persyaratan jaringan dan operasional

- Gunakan HTTPS end-to-end dan validasi sertifikat server. Hindari redirect HTTP ke HTTPS karena body/header harus tetap identik dan redirect dapat memengaruhi pengiriman POST.
- Sinkronkan waktu server pengirim dan penerima melalui NTP agar timestamp berada dalam toleransi.
- Pastikan koneksi outbound dari server Pemeriksaan JPL ke endpoint dapat melewati DNS, firewall, dan proxy produksi.
- Jangan mencatat API key, secret, signature, body, subject, body pesan, NIPP, atau NIPKWT pada log aplikasi pengirim.
- Terapkan retry hanya untuk kegagalan sementara (misalnya 429/5xx), dengan exponential backoff, nonce/timestamp baru, dan idempotency key yang tetap. Jangan retry 401/422/409 tanpa memperbaiki penyebabnya.
- Infrastruktur TLS, IP allowlist, rotasi credential, timeout sender, dan kebijakan retry lintas aplikasi perlu disepakati serta diuji pada staging.

## Pemeriksaan implementasi yang menjadi dasar

Kontrak ini diturunkan dari `routes/api.php`, `CoachingMessageController`, `VerifyCoachingMessageRequest`, `CoachingMessageRecipientResolver`, model/migration Pesan Pembinaan, dan `CoachingMessageReceiverTest`. Tes penerimaan mencakup HMAC/timestamp/replay, mapping NIPP/NIPKWT, konflik identitas, role/status penerima, validasi payload, retry idempotent, respons konflik, expiry 72 jam, serta sanitasi informasi sensitif pada respons/log.
