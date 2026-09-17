# KAI-PJL

Sistem Pencatatan Dinasan PJL berbasis web yang dikembangkan menggunakan Laravel.

Project ini digunakan untuk membantu proses pencatatan dinasan, jadwal KA, realisasi perjalanan KA, dan proses serah terima dinasan.

---

## Teknologi

- Laravel 13
- PHP 8.3+
- MySQL
- Laragon
- Blade
- Tailwind CSS
- Vite
- Git & GitHub

---

## Persyaratan

Sebelum menjalankan project, pastikan sudah terinstall:

- PHP 8.3 atau lebih baru
- Composer
- Node.js dan npm
- Git
- Laragon
- MySQL

Cek instalasi dengan:

```bash
php --version
composer --version
node --version
npm --version
git --version
```

---

## Instalasi Project

### 1. Clone Repository

Clone repository ke komputer masing-masing:

```bash
git clone https://github.com/jenratrip/kai-pjl.git
```

Masuk ke folder project:

```bash
cd kai-pjl
```

---

### 2. Install Dependency PHP

Jalankan:

```bash
composer install
```

---

### 3. Install Dependency Frontend

Jalankan:

```bash
npm install
```

---

### 4. Konfigurasi Environment

Salin file `.env.example` menjadi `.env`.

Untuk Git Bash:

```bash
cp .env.example .env
```

Untuk PowerShell:

```powershell
Copy-Item .env.example .env
```

Kemudian generate application key:

```bash
php artisan key:generate
```

---

## Konfigurasi Database

### 1. Jalankan MySQL

Buka Laragon kemudian klik:

```text
Start All
```

Pastikan MySQL dalam keadaan running.

### 2. Buat Database

Buat database MySQL dengan nama:

```text
kai_pjl
```

Database dapat dibuat melalui phpMyAdmin atau database manager lainnya.

### 3. Konfigurasi `.env`

Sesuaikan bagian database pada file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kai_pjl
DB_USERNAME=root
DB_PASSWORD=
```

> Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` apabila konfigurasi MySQL pada komputer berbeda.

---

## Migration Database

Setelah database `kai_pjl` dibuat dan `.env` dikonfigurasi, jalankan:

```bash
php artisan migrate
```

Migration akan membuat tabel database sesuai struktur yang terdapat pada project.

> Jangan membuat tabel secara manual apabila tabel tersebut sudah disediakan melalui migration Laravel.

---

## Menjalankan Project

Jalankan:

```bash
composer run dev
```

Kemudian buka browser:

```text
http://localhost:8000
```

Untuk menghentikan server, tekan:

```text
Ctrl + C
```

---

## Git Workflow

Untuk menjaga agar pekerjaan antar anggota tim tidak saling bertabrakan, **jangan melakukan coding langsung pada branch `main`**.

### 1. Sebelum mulai bekerja

Pastikan berada pada branch `main`:

```bash
git checkout main
```

Ambil perubahan terbaru:

```bash
git pull origin main
```

---

### 2. Buat Branch untuk Fitur

Buat branch baru sesuai fitur yang dikerjakan:

```bash
git checkout -b feature/nama-fitur
```

Contoh:

```bash
git checkout -b feature/jadwal-ka
```

atau:

```bash
git checkout -b feature/realisasi-ka
```

---

### 3. Setelah Selesai Coding

Periksa perubahan:

```bash
git status
```

Tambahkan perubahan:

```bash
git add .
```

Buat commit:

```bash
git commit -m "Add fitur jadwal KA"
```

---

### 4. Push Branch

Push branch ke GitHub:

```bash
git push -u origin feature/nama-fitur
```

Contoh:

```bash
git push -u origin feature/jadwal-ka
```

---

### 5. Pull Request

Setelah branch berhasil di-push:

1. Buka repository GitHub.
2. Pilih **Pull requests**.
3. Klik **New pull request**.
4. Pilih branch fitur sebagai source.
5. Pilih `main` sebagai target.
6. Jelaskan perubahan yang dibuat.
7. Buat Pull Request.
8. Review perubahan bersama sebelum di-merge ke `main`.

---

## Aturan Penting

### Jangan commit file `.env`

File `.env` berisi konfigurasi lokal dan tidak boleh di-upload ke GitHub.

Gunakan:

```text
.env.example
```

sebagai template konfigurasi.

### Jangan commit dependency

Folder berikut tidak perlu di-upload:

```text
/vendor
/node_modules
```

Keduanya akan dibuat melalui:

```bash
composer install
npm install
```

### Jangan coding langsung di `main`

Gunakan branch fitur:

```text
main
│
├── feature/authentication
├── feature/jadwal-ka
├── feature/dinasan
├── feature/realisasi-ka
└── feature/serah-terima
```

---

## Struktur Branch

Branch utama:

```text
main
```

Branch pengembangan fitur:

```text
feature/nama-fitur
```

Contoh:

```text
feature/authentication
feature/jadwal-ka
feature/dinasan
feature/realisasi-ka
feature/serah-terima
```

Setiap fitur dikembangkan pada branch masing-masing dan digabungkan ke `main` melalui Pull Request.

---

## Catatan

Setiap anggota tim menggunakan database MySQL lokal masing-masing dengan nama:

```text
kai_pjl
```

Struktur database dibagikan melalui Laravel Migration, sehingga tidak perlu membagikan database MySQL secara langsung.

Apabila terdapat perubahan struktur database, buat atau ubah migration kemudian commit perubahan tersebut agar anggota tim lain dapat menjalankan:

```bash
git pull origin main
php artisan migrate
```

---

## Project Team

Project: **KAI-PJL**

Repository:

https://github.com/jenratrip/kai-pjl