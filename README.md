SnapStock — Sistem POS & Manajemen Instan

1. Deskripsi Masalah & Solusi

Masalah

Pelaku Usaha Mikro, Kecil, dan Menengah (UMKM) sering kali mengalami kendala operasional seperti:
Pencatatan Terpisah: Sistem kasir (POS) tidak terhubung langsung dengan persediaan bahan baku mentah, menyebabkan masalah bahan habis tak terduga (stock-out).
Inefisiensi Input Katalog: Proses mengunggah produk dan membuat deskripsi satu per satu secara manual membutuhkan waktu yang relatif lama.
Kerumitan Operasional: Antarmuka aplikasi POS yang kompleks membingungkan kasir dan staf produksi.

Solusi: SnapStock

SnapStock menghadirkan platform web manajemen POS dan persediaan cerdas dengan 3 keunggulan utama:
Otomatisasi Potong Stok Bahan Baku (Bill of Materials): Ketika produk di-restok/diproduksi, stok bahan baku mentah yang terkait akan otomatis terpotong sesuai takaran resep.
AI Instant Catalog Generation: Penginputan produk baru secara cepat melalui foto, deskripsi teks, atau berkas CSV berbasis OpenAI/Gemini API.
Simplicity-First Dashboard: Antarmuka ramah pengguna dengan 3 aksi cepat utama (Quick Actions): Penambahan Produk Baru, Transaksi Baru, dan Penambahan Bahan Baku.

2. Fitur Utama & Target Pengguna

Target Pengguna
Pemilik & Pengelola UMKM (F&B, Ritel, dan Industri Kreatif).
Kasir & Staf Operasional Toko.

Fitur Utama
Dashboard Navigasi Simpel: Akses 1-klik untuk 3 fungsi harian paling krusial.
AI Catalog Generation: Ekstraksi otomatis nama produk, kategori, perkiraan harga, dan stok dari unggahan gambar/teks/CSV.
Automated Stock Deduction (BOM): Pengurangan presisi stok bahan baku mentah (decimal 14,3) berbasis relasi resep produk.
POS & Checkout Module: Keranjang belanja interaktif dan kalkulasi pembayaran tunai secara instan.
Scan Struk Pembelian (AI Vision): Pembacaan struk pembelian bahan baku secara otomatis untuk memperbarui persediaan.

3. Tech Stack & Arsitektur Sistem

Stack Teknologi
Backend: PHP 8.3+, Laravel 13 Framework (MVC & Service Layer pattern)
Frontend: Blade Templating, Tailwind CSS 4, Alpine.js, Vite
Database: SQLite (Default lokal), didukung migrasi skema Laravel untuk MySQL/PostgreSQL
AI Engine: Gemini API (gemini-3.5-flash) via AiChatService
Auth & Security: Laravel Breeze (Session-based Auth & Email Verification)
Dev Tools: Laravel Boost, Pint (Code Formatter), PHPUnit

Arsitektur Perangkat Lunak

[ User Browser (Blade + Alpine.js) ]
                │
                │ HTTP Request (Session Auth & CSRF)
                ▼
      [ Routes (routes/web.php) ] ── (Middleware: Auth + Verified + Throttle)
                │
                ▼
     [ Controller Validation ] ── (Form Request Rule Check)
                │
                ├──► [ Service Layer (Business Logic) ]
                │       ├── TransactionCheckoutService  ──► Checkout POS & Pemotongan Stok
                │       ├── ProductProductionService    ──► Kalkulasi Resep (BOM) & Potong Bahan Baku
                │       ├── ProductCsvImportService    ──► Parsing Data CSV Massal
                │       └── AiChatService              ──► Integration dengan Gemini API
                │
                ▼
     [ Eloquent ORM (Models) ] ── (DB Transaction & Lock For Update)
                │
                ▼
    [ Basis Data (SQLite / MySQL) ]


💻 4. Cara Instalasi & Menjalankan Aplikasi

Ikuti langkah-langkah berikut untuk menjalankan SnapStock di lingkungan lokal Anda:

Prasyarat
PHP >= 8.3
Composer
Node.js >= 18.x & NPM

Langkah-Langkah Instalasi

Clone Repositori:

git clone https://github.com/username/snapstock.git

cd snapstock


Install Dependensi Backend (Composer):

composer install


Install Dependensi Frontend (NPM):

npm install


Konfigurasi Environment:
Salin file .env.example menjadi .env:

cp .env.example .env


Generate App Key:

php artisan key:generate


Persiapan Database & Migrasi:
Dari bawaannya, Laravel menggunakan SQLite. Buat file database SQLite kalau belum ada:

touch database/database.sqlite


Jalankan migrasi database beserta seeders jika ada:

php artisan migrate --seed


Jalankan Server Pengembang:
Buka 2 terminal terpisah:

Terminal 1 (Laravel Server):

php artisan serve


Terminal 2 (Vite Asset Bundler):

npm run dev


Akses Aplikasi:
Buka browser dan akses alamat http://127.0.0.1:8000.

5. Environment Variables

Berikut adalah konfigurasi kunci yang perlu disesuaikan pada berkas .env:

APP_NAME="SnapStock - Sistem POS & Manajemen Instan"
APP_ENV=local
APP_KEY=base64:M3f7neWQsucwiuYgL9c0J74NScLqxzSf4xY3GSQZCZw=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=sqlite
# DB_DATABASE=laravel

# Gemini API Integration Key
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta/openai/
GEMINI_API_KEY=your-gemini-api-key
GEMINI_MODEL=gemini-3.5-flash


6. Tautan Penting & Dokumentasi

Website / Live Demo: https://snapstock-production-1474.up.railway.app

Video Demo YouTube: https://drive.google.com/file/d/1UstVnHQA1TCyUxy3WF7sKMXi6IxWUTMy/view?usp=sharing

Repository GitHub: https://github.com/Felliyao/SnapStock

Akun Simulasi
Username: efa@gmail.com
Password: efacay07

📄 Lisensi

Proyek ini dikembangkan di bawah lisensi open-source MIT License.