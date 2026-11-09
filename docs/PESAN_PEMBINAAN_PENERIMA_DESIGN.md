# Rancangan Teknis Pesan Pembinaan — Sistem Penerima KAI-PJL

## Status dan ruang lingkup

Dokumen ini merancang **sisi penerima** Pesan Pembinaan di KAI-PJL. Sistem Pemeriksaan JPL adalah sistem pengirim eksternal. KAI-PJL tidak membuat Pesan Pembinaan dan tidak mengakses database sistem pengirim.

Keputusan bisnis pada tugas ini menjadi acuan bila berbeda dari `README.partner.md`:

- Hanya akun ber-role `pjl` yang boleh menerima dan melihat pesan.
- Identitas sasaran adalah NIPP atau NIPKWT; nama tidak pernah menjadi kunci pencarian.
- Masa tampil aktif adalah 72 jam sejak KAI-PJL menerima pesan.
- Record kedaluwarsa tetap disimpan untuk audit teknis, tetapi tidak ada halaman arsip untuk role mana pun.
- Notifikasi realtime bersifat wajib, melalui private channel penerima saja.

Dokumen ini tidak mengimplementasikan kode atau menetapkan keputusan yang pada bagian **Keputusan yang masih perlu disetujui**.

## 1. Temuan struktur aplikasi aktif

Pemeriksaan dilakukan pada branch `feature/superadmin-backend`. Implementasi yang relevan saat ini:

| Area | Kondisi yang ditemukan |
| --- | --- |
| Identitas akun | [app/Models/User.php](../app/Models/User.php) menyimpan satu kolom `nip`; tidak ada kolom terpisah `nipp` dan `nipkwt`. Model memiliki relasi `role()`, `hasRole()` dan `isSuperadmin()`. |
| Jenis petugas | Migration [2026_10_22_000001_add_jenis_petugas_to_users_table.php](../database/migrations/2026_10_22_000001_add_jenis_petugas_to_users_table.php) menambahkan `jenis_petugas`. Komentarnya menetapkan `reguler` memakai NIPP, `wakilan` memakai NIPKWT, dan nilai `NULL` pada akun PJL lama diperlakukan sebagai reguler oleh LoginController. |
| Login dan role | [app/Http/Controllers/Auth/LoginController.php](../app/Http/Controllers/Auth/LoginController.php) mencari akun melalui `users.nip`, lalu memisahkan role `pjl`, `admin`, dan `superadmin`. [app/Http/Middleware/RoleMiddleware.php](../app/Http/Middleware/RoleMiddleware.php) memeriksa nama role. |
| Route API | `routes/api.php` belum ada. [bootstrap/app.php](../bootstrap/app.php) hanya mendaftarkan `web.php`, `console.php`, dan health route; belum mendaftarkan route API khusus. |
| Queue | [config/queue.php](../config/queue.php) memakai `QUEUE_CONNECTION=database` sebagai default dan mengonfigurasi tabel `jobs`. Tabel queue disediakan migration Laravel. [composer.json](../composer.json) menjalankan `queue:listen --tries=1` pada skrip development; belum membuktikan konfigurasi worker produksi. |
| Broadcasting/realtime | `.env.example` menetapkan `BROADCAST_CONNECTION=log`. Tidak ditemukan `config/broadcasting.php`, `routes/channels.php`, `ShouldBroadcast`, Laravel Echo, Pusher JS, atau konfigurasi Reverb aktif. `@laravel/multiplex` hanya tercantum sebagai optional dependency frontend dan tidak terpasang di `node_modules`. |
| Monitoring | [app/Http/Controllers/Superadmin/SystemMonitoringController.php](../app/Http/Controllers/Superadmin/SystemMonitoringController.php) memeriksa queue secara umum dan menegaskan bahwa worker tidak dapat dipastikan tanpa heartbeat. Monitoring tersebut bukan monitoring pengiriman Pesan Pembinaan. |
| Pemisahan fitur yang berdekatan | Catatan Dinas dan Serah Terima adalah fitur operasional lokal. [app/Models/Dinas.php](../app/Models/Dinas.php) memiliki snapshot Jadwal KA; `SerahTerima.ringkasan` menyimpan ringkasan historis. Pesan Pembinaan harus memakai tabel dan alur terpisah. |
| Implementasi pesan | Tidak ditemukan model, tabel, controller, service, route, event, job, mapping, nonce, atau test Pesan Pembinaan di repository aktif. Tidak ada konfigurasi endpoint, API key, maupun shared secret di `.env.example`; nilai rahasia `.env` tidak dibaca atau dicantumkan. |

Versi yang diperiksa: PHP `^8.3`, Laravel Framework `13.31.0`, Pest `4.7.8`; dependency frontend yang tercantum antara lain Alpine.js `^3.17.4` dan Tailwind CSS `^4.0.0`. Broadcasting provider belum tersedia dari konfigurasi atau dependency aktif yang diperiksa.

## 2. Gambaran arsitektur

```text
Sistem Pemeriksaan JPL (pengirim)
  buat pesan sesuai proses pemeriksaan yang sudah ada
  simpan ke outbox dan antrean pada sistem pengirim
  kirim HTTPS POST dengan API key, timestamp, nonce, HMAC
                     |
                     v
KAI-PJL (penerima)
  batasi ukuran request -> autentikasi key -> validasi HMAC/timestamp
  validasi JSON -> klaim nonce/idempotency -> resolve identitas PJL
  simpan pesan + catatan outbox notifikasi dalam satu transaksi
                     |
           +---------+----------+
           v                    v
  API daftar/detail PJL    worker/outbox broadcaster
  scope ke akun sendiri    private channel akun PJL itu saja
```

Pengirim tetap bertanggung jawab agar kegagalan pengiriman tidak menghentikan pemeriksaan utama. KAI-PJL menyediakan respons yang cepat dan konsisten setelah menerima atau mengenali idempotent request; tidak melakukan panggilan balik sinkron ke sistem pengirim.

Pesan tidak dikaitkan dengan `dinas_id`, snapshot Dinas, atau `SerahTerima.ringkasan`. Penerima ditentukan oleh identitas akun PJL, sehingga pengiriman tidak berubah akibat versi Jadwal KA, login PJL pengganti, atau pergantian Dinas.

## 3. Alur request penerimaan

1. Terima hanya HTTPS `POST` dan JSON; batasi panjang body, header, subject, body, dan payload sebelum pemrosesan berat.
2. Pilih konfigurasi credential pengirim berdasarkan `X-Api-Key` atau key identifier yang disepakati. Jangan menyimpan secret dalam kode atau log.
3. Validasi format/waktu `X-Timestamp` dan batas skew yang disetujui.
4. Hitung HMAC atas **byte body mentah** dengan format tepat `timestamp:nonce:raw_json_body`; bandingkan signature dengan fungsi constant-time (`hash_equals`). Jangan parse lalu encode ulang JSON untuk verifikasi.
5. Parse JSON dan validasi schema, tipe, batas panjang, field wajib, dan field tambahan yang diizinkan. `body` ditampilkan escaped sebagai teks; jangan render HTML dari pengirim.
6. Dalam transaksi, klaim nonce dengan unique constraint, periksa idempotency, resolve kandidat penerima PJL dari identitas, simpan message dan outbox realtime. Constraint database menjadi pengaman terakhir untuk request bersamaan.
7. Jika key idempotency sudah ada dengan payload yang sama, kembalikan hasil yang stabil tanpa membuat message/notifikasi baru. Jika key sama tetapi payload berbeda, jangan menimpa record; respons konflik perlu ditetapkan pada kontrak.
8. Commit record message dan outbox sebelum worker mengirim notifikasi. Kegagalan broadcaster tidak membatalkan message yang sudah diterima; worker/outbox mencoba lagi sesuai kebijakan retry.
9. Kembalikan respons JSON sukses hanya setelah pesan tersimpan atau request dikenali sebagai duplikat yang sama.

Validasi signature dilakukan sebelum mempercayai isi payload. Penyimpanan message dan outbox harus atomic. Konsumsi nonce untuk request terautentikasi harus cukup kuat agar nonce yang sama tidak dapat menjalankan efek berulang; kebijakan nonce pada payload invalid perlu ditentukan dalam implementasi supaya retry menggunakan nonce baru.

## 4. Spesifikasi endpoint dan header

Endpoint yang menjadi acuan:

```http
POST /api/v1/coaching-messages/receive
Content-Type: application/json
X-Timestamp: <format yang disepakati>
X-Nonce: <nilai acak sekali pakai>
X-Signature: <HMAC-SHA256 hex>
X-Api-Key: <credential pengenal pengirim, bila digunakan>
```

Canonical string:

```text
<timestamp>:<nonce>:<raw JSON body>
```

Signature:

```text
HMAC-SHA256(shared_secret, canonical_string)
```

Gunakan HTTPS, request-size limit, throttling khusus endpoint, response JSON generik, dan jangan membocorkan apakah key, signature, atau target tertentu yang gagal. Route API harus stateless dan tidak memakai session login PJL; autentikasi inbound menggunakan credential antar-sistem. Di branch saat ini perlu menambahkan pendaftaran API routing karena `bootstrap/app.php` belum menunjuk file API.

Respons yang sudah tercantum di README:

| Kondisi | Status yang menjadi acuan |
| --- | --- |
| Pesan baru tersimpan | `201` atau `200`, JSON berisi `status` dan `message_uuid` |
| Idempotency request identik | Respons sukses konsisten; pilih `200` untuk duplikat yang sudah diterima |
| Signature/key/timestamp/nonce tidak sah | `401` dengan pesan generik |
| Payload/schema tidak valid | `422` dengan error validasi yang tidak membocorkan data akun |
| Target tidak ditemukan atau identitas konflik | Belum ditetapkan kontraknya; lihat keputusan terbuka |
| Idempotency key dipakai untuk payload berbeda | Belum ditetapkan statusnya; rekomendasi `409 Conflict` |

## 5. Contoh payload

Contoh ini mengikuti bentuk README, tetapi `recipient_user_id` lintas aplikasi **tidak dipakai sebagai ID lokal** tanpa mapping eksplisit:

```json
{
  "message_uuid": "4ab85db1-ec10-4d67-94d3-9814ef39b1e4",
  "inspection_id": "JPL-2026-0001",
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

Penerima menghitung `received_at` menggunakan waktu server KAI-PJL. `expires_at` yang dikirim pihak luar tidak menjadi sumber masa aktif; masa tampil diturunkan dari `received_at + 72 jam`. Perlu persetujuan apakah field `expires_at` diabaikan, ditolak, atau hanya dipakai sebagai batas yang lebih singkat. Field `payload` harus allow-listed dan dibatasi; simpan hanya data yang memang dibutuhkan UI/audit.

## 6. HMAC, timestamp, dan anti-replay

- Pilih credential aktif secara aman; jika API key digunakan, API key mengidentifikasi credential dan secret HMAC tetap disimpan di secret manager/environment config.
- Signature dihitung dari raw request body dan nilai header timestamp/nonce yang persis diterima. Gunakan representasi signature tunggal yang disepakati, misalnya hex lowercase.
- Bandingkan signature secara constant-time. Jangan menampilkan signature, API key, shared secret, raw body, atau pesan pada exception/log.
- Tolak timestamp terlalu jauh dari waktu server. Nilai toleransi, zona waktu, format (Unix epoch atau ISO-8601), dan perilaku saat clock skew belum ditetapkan.
- Simpan nonce dalam tabel anti-replay dengan unique constraint yang scoped ke identitas credential/sender. Simpan waktu kedaluwarsa nonce sekurangnya sepanjang jendela validasi timestamp; pembersihan nonce lama dapat dijalankan terpisah.
- Nonce yang sama tidak boleh menghasilkan efek penerimaan kedua. Retry yang sah dari pengirim memakai timestamp dan nonce baru, tetapi mempertahankan idempotency key.
- API key rotation perlu mendukung pergantian credential terkontrol tanpa memasukkan secret ke source control. Cara dual-key/masa overlap belum disetujui.

## 7. Mapping NIPP/NIPKWT ke akun PJL

Struktur aktual hanya mempunyai `users.nip`, `users.role_id`, dan `users.jenis_petugas`; tidak ada `users.nipp` atau `users.nipkwt` terpisah. `users.nip` mempunyai unique index pada migration pembuatan tabel. Migration `jenis_petugas` menyatakan `reguler` berarti login NIPP, `wakilan` berarti login NIPKWT; akun PJL lama yang `jenis_petugas = NULL` dianggap reguler oleh LoginController.

Resolver penerima yang direkomendasikan:

1. Jangan mencari berdasarkan nama.
2. Cocokkan NIPP/NIPKWT secara exact terhadap `users.nip`, bukan `LIKE` atau normalisasi yang mengubah identitas.
3. Batasi hasil ke akun yang relasi role-nya bernama `pjl`; akun `admin`/`superadmin` tidak pernah menjadi kandidat.
4. Gunakan `jenis_petugas` sebagai pemeriksaan jenis identitas: NIPP ke `reguler` (termasuk legacy `NULL` sesuai perilaku login yang terdokumentasi), NIPKWT ke `wakilan`. Perlu persetujuan jika data pengirim lama tidak mengirim jenis secara konsisten.
5. Abaikan `recipient_user_id` pengirim kecuali identifier tersebut memang ID lokal KAI-PJL yang kontraknya diverifikasi; ID antar database tidak boleh dianggap sama.
6. Jika tepat satu akun PJL teridentifikasi, simpan `recipient_user_id` lokal hasil resolver. Jika tidak ada kandidat, jangan simpan sebagai pesan aktif dan jangan tampilkan ke akun mana pun.
7. Jika NIPP dan NIPKWT yang diberikan menghasilkan akun berbeda, jangan memilih salah satunya; tolak atau karantina tanpa notifikasi. Jangan menggunakan nama untuk menyelesaikan konflik.

`users.nip` bersifat unique sehingga satu nilai persis tidak dapat cocok ke beberapa akun selama constraint aktif. Konflik masih mungkin terjadi antar dua field identitas dalam payload yang menunjuk dua akun berlainan. Respons dan apakah request konflik perlu disimpan sebagai audit teknis memerlukan keputusan.

## 8. Rancangan tabel pesan dan idempotency

Belum ada tabel pesan atau mapping coaching pada branch aktif. Rancangan migration yang perlu dibuat pada tahap implementasi:

### `coaching_messages`

Kolom yang disarankan:

- primary key.
- `source_system`/credential scope yang berasal dari konfigurasi credential tervalidasi.
- `message_uuid` dengan unique constraint sesuai lingkup sender.
- `inspection_id` sebagai identifier eksternal bertipe string nullable; jangan membuat foreign key ke tabel lokal yang tidak ada.
- `recipient_user_id` foreign key ke `users.id`, hanya hasil resolver PJL lokal.
- `matched_identifier_type` (`nipp` atau `nipkwt`) untuk jejak resolusi tanpa menyalin identitas tambahan bila tidak dibutuhkan.
- `subject`, `body` (plain text, panjang terbatas), dan `payload` JSON yang telah divalidasi/di-allow-list bila dibutuhkan.
- `idempotency_key` dan digest payload/request untuk membedakan retry identik dengan reuse key berisi payload lain.
- `received_at` dan `expires_at` sebagai timestamp server.
- timestamps.

Unique idempotency disarankan pada `(source_system, idempotency_key)` agar key dari sender berbeda tidak saling bertabrakan. `message_uuid` juga perlu unique dalam lingkup source. Index daftar aktif disarankan untuk `(recipient_user_id, expires_at)`; semua detail tetap di-scope ke `recipient_user_id`. Pesan tidak boleh cascade-delete bersama Dinas, Jadwal KA, atau Serah Terima karena tidak mempunyai relasi ke modul tersebut. Kebijakan delete user harus menjaga jejak teknis, misalnya melarang delete akun berpesan atau memakai strategi retensi yang disetujui.

### `coaching_message_nonces`

- credential/sender scope.
- nonce.
- timestamp request dan `expires_at`.
- unique `(credential_id, nonce)`.

### Outbox realtime

Karena realtime wajib dan harus tahan terhadap kegagalan worker/broker, disarankan `coaching_message_outbox` atau pola outbox setara yang menyimpan event pending/retry dalam transaksi yang sama dengan message. Worker mengirim event setelah commit, memperbarui status delivery/attempt tanpa memuat isi pesan di log.

Outbox bukan “arsip pesan” dan tidak membuka UI. Tabel ini hanya status pengiriman teknis; aksesnya bukan melalui halaman PJL/Admin/Superadmin.

## 9. Masa berlaku 72 jam dan data kedaluwarsa

- Tetapkan `received_at` dari clock server ketika request sah diterima.
- Hitung `expires_at = received_at + 72 jam`; jangan percaya waktu pembuatan atau `expires_at` dari sender sebagai dasar masa aktif.
- API list/detail aktif menggunakan kondisi `recipient_user_id = auth user` dan `expires_at > now()`; pagination wajib.
- Permintaan detail juga memakai scope pemilik dan waktu aktif, bukan hanya ID pesan.
- Kedaluwarsa tidak menghapus record. Tidak dibuat menu arsip, endpoint arsip, atau riwayat untuk PJL, Admin, maupun Superadmin.
- Pembersihan nonce/outbox lama boleh dilakukan dengan kebijakan operasional terpisah; pembersihan itu tidak menghapus `coaching_messages`.
- Retensi permanen/berjangka, penghapusan sesuai kewajiban hukum, dan apakah data pesan harus dianonimkan belum ditentukan.

## 10. Strategi private channel dan realtime

Realtime adalah kebutuhan bisnis wajib, tetapi transport belum tersedia pada konfigurasi aktif: `BROADCAST_CONNECTION=log`, tidak ada konfigurasi broadcasting/channel auth, Laravel Echo, Reverb/Pusher server package, atau browser client terpasang. Karena itu realtime belum dapat dipenuhi hanya dengan queue database.

Rancangan transport:

- Gunakan Laravel broadcasting melalui **private channel per user lokal**, misalnya `coaching-messages.{userId}`.
- Authorization channel memastikan user yang telah login memiliki role `pjl` dan ID-nya sama persis dengan ID pada channel. Admin, Superadmin, PJL lain, serta anonymous tidak dapat subscribe.
- Jangan gunakan public channel, presence channel untuk semua petugas, atau broadcast ke daftar role.
- Event membawa ID message/correlation minimal saja; UI mengambil message lewat endpoint yang tetap melakukan authorization dan filter expiry. Isi pesan tidak perlu disalin ke channel event.
- Dispatch dilakukan setelah record tersimpan dan commit; outbox/queue menangani retry. Notifikasi gagal tidak membatalkan penerimaan pesan.
- Frontend PJL menampilkan pembaruan aktif tanpa mengubah Dashboard PJL, snapshot Dinas, ataupun ringkasan Serah Terima.

**Pilihan transport:** belum ada transport runtime yang dapat dipastikan. Laravel Reverb adalah kandidat self-hosted bila server mendukung process WebSocket persisten, port/proxy/TLS, dan worker terpantau. Pusher atau provider hosted cocok bila infrastruktur tidak dapat menjalankan daemon. `@laravel/multiplex` tercantum sebagai optional package tetapi tidak terpasang/terkonfigurasi, jadi tidak dianggap sebagai fasilitas yang tersedia. Pilihan final harus menunggu informasi hosting, operasi worker, firewall/proxy, kapasitas, dan kebijakan data.

## 11. Pengamanan endpoint daftar dan detail

Endpoint UI berada pada grup web terautentikasi dengan middleware `role:pjl`, terpisah dari endpoint inbound `/api/v1/...`.

- Daftar hanya mengambil `coaching_messages` milik `auth()->id()` dan belum expired.
- Detail harus melakukan lookup sekaligus ownership scope; jangan melakukan `find(id)` lalu mengandalkan tombol UI.
- Admin/Superadmin tidak punya route list/detail dan tidak dapat melihat isi melalui Monitoring Sistem atau Activity Log.
- ID pesan acak/UUID mengurangi enumerasi tetapi bukan pengganti authorization.
- Jangan menerima `recipient_user_id`/user ID dari form PJL untuk mengubah pemilik pesan.
- Validasi render mencegah HTML/script tersimpan dari sender dieksekusi; gunakan output escaped.
- Throttle endpoint penerima dan endpoint PJL; API inbound memiliki batas request berbeda dari endpoint sesi PJL.

## 12. Pembatasan role dan pemisahan modul

Implementasi mengikuti pola role aktif (`RoleMiddleware` dan route group di `routes/web.php`):

- **PJL:** menerima pesan yang tervalidasi dan hanya melihat pesan sendiri yang belum expired.
- **Admin:** tidak menerima, mencari, melihat, mengubah, atau mengarsipkan pesan.
- **Superadmin:** tidak menerima atau melihat pesan; monitoring umum tidak menampilkan isi, target, atau detail message.
- **Sender eksternal:** hanya dapat mengirim request terautentikasi pada endpoint API; tidak mengakses tabel/database.

Tidak ada fitur edit/hapus/status pesan dari area operasional KAI-PJL dalam lingkup keputusan bisnis ini. Kebijakan koreksi pesan harus menjadi keputusan tambahan sebelum implementasi.

## 13. Pencegahan duplikasi dan konkurensi

- `UNIQUE(source_system, idempotency_key)` menjadi kontrol atomik; pemeriksaan awal di PHP saja tidak cukup untuk race condition.
- Simpan digest raw body yang telah ditandatangani. Retry dengan key dan digest sama mengembalikan message lama; key sama dengan digest berbeda tidak menimpa data.
- `UNIQUE(source_system, message_uuid)` mencegah UUID pesan berulang bila disepakati sender sebagai identifier stabil.
- `UNIQUE(credential_id, nonce)` membuat request nonce sama hanya dapat menang sekali, termasuk request paralel.
- Penulisan message dan outbox dalam satu transaksi; kegagalan salah satunya rollback keduanya.
- Pada pelanggaran unique akibat request paralel, baca ulang record idempotency dan tentukan hasil dari digest; jangan membuat record kedua.
- Retrying event realtime boleh mengulang event, tetapi konsumen memakai message ID sebagai dedupe key dan UI mengambil data melalui endpoint scoped.

## 14. Kegagalan API, queue, dan realtime

- Sistem pengirim menangani retry asinkronnya sendiri sehingga pemeriksaan utamanya tidak menunggu KAI-PJL. KAI-PJL tidak bisa menjamin proses internal sender; kontrak timeout/retry dan status acknowledgement harus diselaraskan.
- Sender retry memakai nonce/timestamp baru dan idempotency key tetap.
- KAI-PJL merespons setelah persistensi sukses; jika persistensi gagal, kirim 5xx generik agar sender dapat retry. Jangan mengakui sukses sebelum record durable.
- Kegagalan realtime tidak menghapus message; outbox berstatus pending/retry dan worker mengirim ulang dengan backoff serta batas percobaan yang disepakati.
- Dead-letter/failed delivery membutuhkan alarm operasional, tetapi detail pesan dan penerima tidak ditampilkan ke Admin/Superadmin.
- Queue proyek default `database`, tetapi `after_commit` pada koneksi database di config bernilai `false`. Implementasi outbox harus menghindari job melihat record yang belum committed; pilih outbox/dispatch-after-commit yang terbukti dengan versi Laravel 13.31.
- Skrip dev menjalankan `queue:listen --tries=1`; nilai ini tidak cukup untuk menyimpulkan kebijakan worker produksi. Konfigurasi produksi perlu dipastikan.

## 15. Logging dan monitoring aman

Catat hanya metadata minimum: request/correlation ID, source credential ID yang tidak rahasia, status validasi (kelas umum), message UUID/idempotency hash, target internal ID bila akses log dibatasi, latency, attempt, status queue/broadcast, dan waktu.

Jangan mencatat:

- API key, shared secret, signature mentah, token, atau body mentah.
- Isi `subject`, `body`, atau payload bebas.
- NIPP/NIPKWT mentah bila tidak mutlak diperlukan.
- Stack trace yang menyisipkan request penuh.

Monitoring Superadmin sekarang mem-parsing error log umum dan menampilkan pesan/stack ter-sanitasi. Karena Superadmin tidak boleh memiliki akses ke pesan, kode penerima harus memastikan message body/identitas target tidak masuk log yang bisa tampil di Monitoring. Jangan menambahkan daftar pesan ke Monitoring Sistem atau Activity Log. Bila metrik pengiriman ditampilkan, gunakan agregat teknis yang tidak mengungkap konten/target dan minta persetujuan bahwa agregat tidak dianggap akses ke pesan.

## 16. Risiko terhadap fitur aktif

| Fitur | Risiko | Batas rancangan |
| --- | --- | --- |
| Snapshot Dinas | Mengaitkan message dengan Dinas aktif dapat mengganti target saat login pengganti atau mengubah snapshot. | Resolver hanya memilih akun PJL dari identity; tidak membaca/menulis snapshot atau `dinas_id`. |
| Serah Terima | Memakai `SerahTerima.ringkasan` untuk menyimpan pesan mengubah dokumen historis. | Gunakan tabel message/outbox terpisah; jangan menghitung ulang atau menulis ringkasan Serah Terima. |
| Versioning Jadwal KA | Mengambil penerima melalui jadwal/JPL dapat berubah saat batch diaktifkan. | Target ditentukan dari identitas akun, bukan jadwal KA/JPL/batch. |
| Dashboard PJL | Perubahan dashboard besar berisiko regresi scope Dinas. | Integrasikan panel/inbox terpisah dan query pesan sendiri; jangan mengubah resolver dashboard/Dinas. |
| Inventaris | Tidak ada dependensi domain. | Tidak mengaitkan inspeksi inventaris kecuali sender mengirim `inspection_id` yang hanya disimpan sebagai referensi eksternal. |
| Hak akses | Route dalam grup Admin/Superadmin bersama dapat membuka data sensitif. | Pisahkan route PJL dengan middleware `auth,role:pjl`; API inbound pakai credential antarsistem. |
| Monitoring | Log umum dapat menampilkan message body bila exception menyertakan request. | Redaksi di sumber log dan hanya agregat non-konten. |
| Database | Idempotency check saja dapat berlomba pada request serentak. | Unique constraints + transaksi; uji pada database engine yang sama dengan deployment. |

## 17. Test yang diperlukan

### Unit/service

- Canonical HMAC menghasilkan nilai yang sama untuk raw body identik dan gagal jika byte body/header berbeda.
- Perbandingan signature, malformed signature, API key tidak dikenal, timestamp salah format/expired/future, dan nonce kosong/format salah.
- Resolver exact match NIPP reguler, NIPKWT wakilan, PJL legacy `jenis_petugas=NULL`, no match, nilai case/whitespace, dan account role Admin/Superadmin.
- Dua identifier menunjuk akun sama, satu identifier cocok, tidak ada yang cocok, dan dua identifier konflik.
- Masa aktif dihitung dari `received_at`, tepat pada batas 72 jam, dan setelah batas.

### Feature API

- Hanya POST endpoint yang tersedia; header wajib; HMAC valid menerima pesan.
- Signature invalid/key invalid/replay nonce tidak membuat message/outbox dan memberi status sesuai kontrak.
- Payload invalid/ukuran body terlampaui/tipe field salah ditolak.
- Target tidak dikenal atau konflik tidak tampil dan tidak mengirim event.
- Idempotency retry identik membuat satu record dan satu notifikasi; key sama payload berbeda tidak overwrite.
- Request konkuren untuk nonce/idempotency sama tetap menghasilkan satu record.
- Persistensi message/outbox atomik saat salah satu operasi gagal.
- Response tidak memuat isi pesan/rahasia yang tidak perlu.

### Feature UI dan authorization

- PJL hanya melihat pesan miliknya yang `expires_at > now()`; pagination dan detail tetap scoped.
- PJL tidak dapat membaca record pengguna lain dengan mengganti ID atau parameter.
- Admin, Superadmin, guest, dan PJL lain tidak dapat membuka daftar/detail.
- Expired message tidak muncul di list maupun detail; tidak ada halaman/route arsip untuk role mana pun.
- Subject/body dirender sebagai teks escaped.

### Realtime/outbox

- Channel private hanya dapat diotorisasi oleh PJL dengan user ID channel yang cocok.
- PJL lain, Admin, Superadmin, dan guest gagal authorize channel.
- Tidak ada public/global broadcast.
- Event dikirim sesudah commit dan membawa message ID minimum.
- Broadcast failure membuat retry/outbox pending tanpa menghapus pesan.
- Duplicate job/event tidak menggandakan notifikasi tampilan.

### Regresi integrasi

- Existing test Snapshot Dinas, Login PJL, Realisasi KA, Serah Terima snapshot/recipient, Dashboard PJL, dan System Monitoring tetap lulus.
- Membuka dashboard, daftar/detail Pesan Pembinaan tidak mengubah snapshot Dinas atau `SerahTerima.ringkasan`.
- Untuk test concurrency/constraint, gunakan database terisolasi dan engine yang mendukung unique/index/transaction behavior setara deployment; SQLite in-memory saja tidak membuktikan seluruh perilaku MySQL concurrency.

Belum ada test Pesan Pembinaan pada repository aktif. Test yang telah ada untuk snapshot, dashboard, Serah Terima, dan monitoring tidak membuktikan keamanan endpoint pesan atau kanal private.

## 18. Tahapan implementasi yang disarankan

1. **Kontrak dan keputusan:** finalkan identifier payload, timestamp format/tolerance, sender credential/key rotation, status response target conflict/idempotency mismatch, retention, dan hosting realtime.
2. **Skema terisolasi:** migration message, nonce, outbox; index/unique/FK ditinjau tanpa mengubah tabel Dinas, Jadwal KA, Realisasi, atau Serah Terima.
3. **Penerimaan API:** pendaftaran API route, validasi credential/HMAC/raw body/timestamp/nonce/schema, resolver PJL, idempotency, transaksi, dan response contract; uji sebelum UI.
4. **Read endpoints:** route PJL-only untuk list/detail aktif dengan ownership scope dan expiry berbasis `received_at`.
5. **Realtime:** pasang transport yang disepakati, private channel auth, event minimum, outbox worker/retry, dan monitoring operasional tanpa konten.
6. **UI PJL:** tambahkan tampilan pesan aktif yang terpisah dari Catatan Dinas, Serah Terima, dan Realisasi; tidak ada tampilan arsip.
7. **Hardening dan rollout:** uji key rotation, replay, race idempotency, failure broker/worker, log redaction, clock skew, rate limit, backup, dan deploy sender/receiver bertahap.

## 19. Keputusan yang masih perlu disetujui

| Keputusan | Mengapa belum dapat dipastikan | Rekomendasi untuk diputuskan |
| --- | --- | --- |
| Konflik NIPP dan NIPKWT mengarah ke dua akun | README tidak menetapkan prioritas/response. | Jangan kirim ke salah satu; tolak tanpa tampilan dan catat metadata aman. Tentukan status HTTP dan prosedur koreksi mapping. |
| Satu identity cocok, identity lain tidak dikenal | “Salah satu identitas cocok” mengizinkan target, tetapi kebijakan payload parsial tidak tertulis rinci. | Terima jika tepat satu kandidat PJL dan tidak ada kandidat konflik; persetujuan eksplisit atas aturan ini. |
| Identifier `recipient_user_id` | Nilai contoh tidak menjelaskan apakah ID lokal atau ID sender. | Abaikan untuk mapping sampai ada kontrak namespace/verified mapping. |
| Timestamp format dan toleransi | Header format dan skew tidak ditentukan. | Tetapkan format canonical dan batas skew, termasuk sinkronisasi waktu kedua server. |
| API key/shared-secret rotation | Mekanisme rotasi/overlap tidak dijelaskan. | Tetapkan credential ID, periode dual-key, cara revoke, dan prosedur emergency rotation. |
| Expiry dari payload sender | README memberi contoh `expires_at`, sementara aturan terbaru mulai dari waktu diterima. | Jadikan waktu server penerima otoritatif; setujui apakah field sender diabaikan atau dibatasi ke nilai lebih awal. |
| Retensi setelah 72 jam | Record harus disimpan untuk audit, tetapi masa retensi tidak ada. | Tetapkan retensi, legal basis, backup, dan prosedur penghapusan/anonymization. UI arsip tetap tidak tersedia. |
| Transport realtime | Tidak ada driver/server/client terkonfigurasi. Hosting runtime belum diketahui. | Pilih Reverb jika daemon WebSocket dapat dioperasikan; jika tidak, pilih provider hosted seperti Pusher. Konfirmasi infrastruktur sebelum dependency dipilih. |
| Semantik target tak dikenal/konflik | README hanya menetapkan respons signature dan payload invalid. | Tentukan status dan retryability tanpa mengungkap keberadaan akun ke pihak yang tidak berwenang. |
| Payload tambahan dan ukuran konten | Struktur `payload` bebas dan batas panjang tidak ditetapkan. | Tetapkan allow-list field, ukuran maksimum, sanitasi plain text, dan batas body. |
| Apakah agregat pesan boleh terlihat di Monitoring | Admin/Superadmin dilarang mengakses pesan; definisi agregat belum ada. | Default: jangan tampilkan pesan atau metrik per-user di Monitoring; bila perlu, sepakati agregat sistem saja. |

## 20. Referensi implementasi yang diperiksa

- `README.partner.md` — kontrak integrasi pengirim/penerima, endpoint, header, HMAC, payload, mapping, idempotency, expiry, dan status implementasi di repo pengirim.
- `app/Models/User.php`, `app/Models/Role.php`.
- `app/Http/Middleware/RoleMiddleware.php`, `app/Http/Middleware/SuperadminMiddleware.php`.
- `app/Http/Controllers/Auth/LoginController.php`.
- `app/Models/Dinas.php`, `app/Models/SerahTerima.php`.
- `routes/web.php`, `bootstrap/app.php`; `routes/api.php` dan `routes/channels.php` tidak ditemukan.
- `config/queue.php`, `.env.example`, `composer.json`, `package.json`, `resources/js/app.js`.
- `app/Http/Controllers/Superadmin/SystemMonitoringController.php`.
- `tests/Feature/DinasJadwalSnapshotTest.php`, `PjlLoginSnapshotTest.php`, `RealisasiKaSnapshotTest.php`, `SerahTerimaSnapshotTest.php`, `SerahTerimaRecipientWorkflowTest.php`, `PjlDashboardTest.php`, `SuperadminSystemMonitoringTest.php`.

