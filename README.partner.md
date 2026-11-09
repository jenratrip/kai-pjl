# README Proyek Serah Terima Dinas

## Integrasi Pesan Pembinaan antara Sistem Pemeriksaan JPL dan Sistem Penerima

Dokumen ini merupakan panduan teknis dan operasional untuk Proyek Serah Terima Dinas dalam implementasi integrasi Pesan Pembinaan antara Sistem Pemeriksaan JPL sebagai sisi pengirim dan sistem penerima yang menjadi sasaran pembinaan sebagai sisi penerima.

Tujuan utama dokumen ini adalah untuk memastikan:
- kontrak API antar sistem berjalan konsisten,
- keamanan dan validasi penerima terpenuhi,
- target pesan ditentukan secara tepat,
- masa berlaku pesan dikelola dengan benar,
- proses pemeriksaan utama tetap berjalan meskipun pengiriman antar aplikasi gagal.

## 1. Gambaran umum

Sistem Pemeriksaan JPL akan menghasilkan pesan pembinaan yang ditujukan kepada petugas atau akun tertentu. Pesan tersebut dikirim secara terarah ke sistem penerima, dan hanya ditampilkan pada sasaran yang benar-benar berhak menerima.

Alur bisnis yang didukung adalah:
- pejabat pemeriksa membuat pesan pembinaan terhadap petugas tertentu,
- pesan dikirim ke sistem penerima secara terarah,
- pesan hanya muncul pada akun atau petugas yang sesuai,
- pesan berlaku selama 72 jam sejak dibuat,
- setelah masa berlaku habis, pesan tidak lagi tampil di daftar aktif tetapi tetap disimpan sebagai riwayat.

Semua komunikasi antar aplikasi dilakukan melalui API yang telah ditetapkan, tanpa akses langsung ke basis data antar sistem.

## 2. Prinsip utama integrasi

Integrasi ini harus mengikuti prinsip-prinsip berikut:

1. Penerima pesan harus dibatasi hanya kepada target yang sah.
2. Tidak diperbolehkan pengiriman massal atau broadcast ke seluruh pengguna.
3. Checklist dan proses pemeriksaan utama tidak boleh terganggu akibat kegagalan pengiriman antar aplikasi.
4. Pengiriman dilakukan secara asinkron melalui antrian.
5. Pesan memiliki masa aktif maksimum 72 jam.
6. Setiap payload harus memiliki `idempotency_key`.
7. Setiap request wajib divalidasi menggunakan HMAC signature.
8. Sistem penerima wajib memvalidasi sasaran sebelum menampilkan pesan.

## 3. Peran masing-masing sistem

### 3.1 Sistem Pemeriksaan JPL
Sistem ini berperan sebagai pengirim pesan pembinaan. Sistem ini bertanggung jawab untuk:
- membuat pesan pembinaan,
- menentukan target penerima yang valid,
- menyimpan data pesan dalam outbox/antrian,
- mengirim request ke endpoint penerima,
- menjaga proses checklist tetap berjalan walaupun pengiriman gagal.

### 3.2 Sistem Penerima
Sistem ini berperan sebagai penerima pesan. Sistem ini bertanggung jawab untuk:
- menerima request dari pengirim,
- memvalidasi signature dan payload,
- memastikan target valid,
- menyimpan pesan sebagai data aktif atau riwayat,
- menampilkan pesan hanya pada akun tujuan yang benar.

## 4. Arsitektur integrasi

Integrasi dilakukan dengan pendekatan berikut:

- sistem pengirim menyimpan pesan ke tabel outbox / message store,
- proses pengiriman berjalan melalui job antrian,
- request dikirim ke endpoint penerima menggunakan HTTP,
- sistem penerima memvalidasi token, signature, dan target penerima,
- sistem penerima menyimpan pesan serta memfilter tampilan sesuai target dan masa berlaku.

Diagram alur logis:

Sistem Pemeriksaan JPL
  -> buat pesan pembinaan
  -> validasi target
  -> simpan ke outbox
  -> dispatch job queue
  -> HTTP POST ke endpoint sistem penerima

Sistem Penerima
  -> validasi header dan HMAC
  -> validasi payload
  -> validasi idempotency key
  -> validasi target pengguna/petugas
  -> simpan pesan
  -> tampilkan di akun sasaran
  -> simpan riwayat jika expired

## 5. Endpoint penerima

Sistem penerima wajib menyediakan endpoint berikut:

- `POST /api/v1/coaching-messages/receive`

### 5.1 Header wajib
Semua request harus membawa header berikut:

- `X-Timestamp`
- `X-Nonce`
- `X-Signature`
- `X-Api-Key` (apabila digunakan)

## 6. Mekanisme keamanan HMAC

### 6.1 Format signature
Signature dihitung berdasarkan kombinasi berikut:

`timestamp + ':' + nonce + ':' + raw_json_body`

Lalu dilakukan hash:

`HMAC_SHA256(shared_secret, payload_string)`

### 6.2 Validasi yang wajib dilakukan
Sistem penerima harus memastikan:
- `X-Timestamp` masih valid dan tidak terlalu lama,
- `X-Nonce` unik dan belum dipakai dalam replay attack,
- `X-Signature` sesuai dengan payload mentah yang diterima,
- `shared_secret` sesuai konfigurasi yang disepakati,
- body tidak diubah saat signature dihitung.

## 7. Struktur payload

Contoh request payload:

```json
{
  "message_uuid": "4ab85db1-ec10-4d67-94d3-9814ef39b1e4",
  "inspection_id": 123,
  "recipient_user_id": 45,
  "recipient_nipp": "12345",
  "recipient_nipkwt": null,
  "subject": "Pesan Pembinaan",
  "body": "Mohon periksa kembali kewaspadaan saat patroli.",
  "payload": {
    "source_system": "pjl-kai",
    "inspection_code": "JPL-2026-0001"
  },
  "expires_at": "2026-11-08T16:00:00+07:00",
  "idempotency_key": "coaching-message-001"
}
```

### 7.1 Penjelasan field utama

- `message_uuid`: UUID unik untuk setiap pesan.
- `inspection_id`: identitas pemeriksaan terkait jika tersedia.
- `recipient_user_id`: ID pengguna target di sistem penerima jika sudah termapping.
- `recipient_nipp`: NIPP target bila sasaran berbentuk akun pengguna.
- `recipient_nipkwt`: NIPKWT bila sasaran adalah petugas PJL.
- `subject`: judul singkat pesan pembinaan.
- `body`: isi pesan pembinaan.
- `payload`: data tambahan yang relevan dengan kebutuhan penerima.
- `expires_at`: waktu kedaluwarsa pesan.
- `idempotency_key`: kunci unik untuk mencegah duplikasi pengiriman.

## 8. Mapping identitas sasaran

Sebelum pesan ditampilkan, sistem penerima wajib memastikan sasaran pesan memang benar-benar valid.

Metode mapping yang diperbolehkan meliputi:
- `recipient_user_id` terhadap pengguna di sistem penerima,
- `recipient_nipp` terhadap akun autentikasi,
- `recipient_nipkwt` terhadap master petugas PJL,
- kombinasi validasi yang telah disepakati bersama.

Field mapping yang umum dipertimbangkan:
- `source_system`
- `source_user_id`
- `source_nipp`
- `source_nipkwt`
- `target_user_id`
- `target_nipp`
- `target_nipkwt`
- `status`
- `verified_at`
- `is_active`

Aturan pengesahan:
- mapping hanya valid bila `status = verified` dan `verified_at` terisi,
- `pending` atau `rejected` tidak boleh dipakai untuk menampilkan pesan aktif,
- semua penerimaan pesan wajib mengecek mapping target sebelum menampilkan data di UI.

## 9. Idempotency dan deduplikasi

Setiap pesan wajib memiliki `idempotency_key` yang unik.

Sistem penerima harus:
- menyimpan `idempotency_key` dengan unique constraint,
- mengecek duplikasi sebelum menyimpan record baru,
- mengembalikan respons yang konsisten untuk request yang sama,
- mencegah tampilan ganda di antarmuka pengguna.

## 10. Masa berlaku pesan selama 72 jam

Pesan pembinaan memiliki masa aktif maksimum 72 jam sejak dibuat.

Aturan penerima:
- pesan aktif hanya ditampilkan bila `now() <= expires_at`,
- pesan yang telah expired tidak ditampilkan pada daftar aktif,
- pesan expired tetap disimpan sebagai riwayat,
- expired message tidak dianggap sebagai pesan aktif.

## 11. Validasi target penerima

Sistem penerima wajib memastikan bahwa sasaran pesan adalah pengguna atau petugas yang benar-benar berhak menerima.

Hal yang tidak diperbolehkan:
- mengirim pesan ke seluruh pengguna,
- menyebarkan pesan ke semua petugas tanpa pengecekan target,
- menampilkan pesan tanpa validasi sasaran yang sah.

## 12. Respons yang diharapkan

### 12.1 Respons sukses

```json
{
  "status": "ok",
  "message_uuid": "4ab85db1-ec10-4d67-94d3-9814ef39b1e4"
}
```

Status HTTP yang disarankan:
- `200` atau `201`

### 12.2 Respons invalid signature

```json
{
  "message": "Signature tidak valid."
}
```

Status HTTP:
- `401`

### 12.3 Respons payload tidak valid

```json
{
  "message": "Payload tidak valid.",
  "errors": {
    "body": ["Isi pesan pembinaan tidak boleh kosong."]
  }
}
```

Status HTTP:
- `422`

## 13. Persyaratan antarmuka penerima

UI atau modul penerima harus memenuhi persyaratan berikut:

- hanya menampilkan pesan yang sesuai dengan target valid,
- membedakan daftar pesan aktif dan riwayat,
- mengecualikan pesan expired dari tampilan aktif,
- menyimpan riwayat walaupun pesan sudah tidak aktif,
- hanya memberi notifikasi pada target yang berhak bila fitur realtime digunakan.

## 14. Realtime dan private channel

Apabila sistem penerima mendukung fitur realtime, implementasi yang disarankan adalah:
- private channel per pengguna atau petugas,
- bukan broadcast publik ke seluruh pengguna,
- event dikirim hanya ke sasaran yang valid,
- notifikasi disesuaikan dengan akun penerima.

## 15. Batasan implementasi

Hal-hal yang tidak boleh dilanggar:

- Tidak diizinkan akses langsung ke database antar aplikasi.
- Tidak diizinkan pengiriman pesan ke seluruh pengguna.
- Tidak diizinkan pembuatan duplikasi pesan.
- Tidak diizinkan menampilkan pesan expired sebagai pesan aktif.
- Tidak diizinkan menurunkan kelangsungan proses inspeksi karena kegagalan pengiriman antar aplikasi.

## 16. Konfigurasi lingkungan

Pada sisi pengirim, konfigurasi yang dipakai adalah:

```env
COACHING_MESSAGE_ENDPOINT=https://sistem-target.example/api/v1/coaching-messages/receive
COACHING_MESSAGE_API_KEY=your_api_key
COACHING_MESSAGE_SHARED_SECRET=your_shared_secret
```

Sistem penerima juga perlu menyiapkan secret yang sama agar validasi HMAC dapat berjalan dengan benar.

## 17. Persyaratan kesiapan implementasi

Untuk memastikan sinkronisasi yang tepat, Proyek Serah Terima Dinas perlu menyiapkan:
- endpoint penerima yang stabil,
- validasi HMAC,
- tabel idempotency,
- pengelolaan mapping target berdasarkan NIPP, NIPKWT, atau identifier lain yang telah disepakati,
- tampilan daftar pesan aktif dan riwayat,
- channel realtime private per target jika realtime dibutuhkan.

## 18. Status implementasi saat ini

Pada repo pengirim, komponen yang telah disiapkan mencakup:
- model dan tabel pesan pembinaan,
- mekanisme idempotency,
- validasi payload dan body,
- pengiriman melalui job queue,
- HMAC signature dan header keamanan,
- resolver target yang membatasi penerima pada sasaran valid,
- dokumentasi teknis integrasi.

Tahap yang masih perlu diselesaikan di sisi penerima adalah:
- implementasi endpoint `POST /api/v1/coaching-messages/receive`,
- validasi HMAC dan header request,
- verifikasi target user atau petugas,
- penyimpanan riwayat dan filter pesan aktif/expired,
- integrasi realtime apabila dibutuhkan pada sisi penerima.

## 19. Kesimpulan

Dokumen ini menjadi acuan teknis dan operasional bagi Proyek Serah Terima Dinas dalam implementasi integrasi Pesan Pembinaan. Dengan penerapan prinsip dan prosedur yang sudah ditetapkan, integrasi dapat berjalan aman, terukur, dan tidak mengganggu proses utama pemeriksaan JPL.

Dokumen teknis yang lebih detail terkait skema, endpoint, dan implementasi dapat dilihat pada [docs/PESAN_PEMBINAAN_INTEGRASI.md](/Users/twilight/Documents/KAI/PJL-KAI/docs/PESAN_PEMBINAAN_INTEGRASI.md).
