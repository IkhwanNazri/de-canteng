# DeBatik — Pasaraya Batik Malaysia

Marketplace batik: pengguna boleh jadi seller, buka kedai sendiri dan jual secara live.
UI ikut fail Figma **DeBatik - Pasaraya Batik Malaysia** (desktop 1440 + mobile 390).

**Stack:** Laravel 13 · Vue 3 + Inertia · Tailwind 4 · MySQL · Redis · (nanti) Bayarcash, LiveKit, Ollama + Qwen

## Jalankan di laptop

```bash
composer install
npm install
cp .env.example .env        # tetapkan DB_* untuk MySQL (DB_DATABASE=debatik)
php artisan key:generate
php artisan migrate --seed  # data contoh: 6 kategori, 3 kedai, 9 produk, 3 live
php artisan storage:link    # untuk gambar produk yang dimuat naik
composer run dev            # atau: php artisan serve  +  npm run dev
```

Buka http://localhost:8000. Nak cuba tanpa MySQL? Set `DB_CONNECTION=sqlite` dan
`touch database/database.sqlite`.

## Struktur

| Bahagian | Fail |
|---|---|
| Route | `routes/web.php` |
| Controller | `app/Http/Controllers/{Home,Product,Shop}Controller.php` |
| Model | `app/Models/{Category,Shop,Product,LiveSession}.php` |
| Jadual DB | `database/migrations/2026_11_17_000001_create_marketplace_tables.php` |
| Data contoh | `database/seeders/DatabaseSeeder.php` |
| Layout (header, footer, bottom nav) | `resources/js/Layouts/AppLayout.vue` |
| Halaman | `resources/js/Pages/` — `Home`, `Products/Index` (Teroka), `Products/Show`, `Shops/Index`, `Shops/Show` |
| Komponen | `resources/js/Components/` |
| Warna & fon (token Figma) | `resources/css/app.css` |

Gambar produk/kategori: isi kolum `image` (URL). Kalau kosong, keluar latar batik placeholder.

## Akaun demo (selepas `migrate --seed`)
Semua kata laluan: `password`

| Emel | Role |
|---|---|
| admin@debatik.test | admin |
| pembeli@debatik.test | buyer |
| batik-citra@debatik.test | seller |

Auth guna **Laravel Breeze** (Vue + Inertia): `/login`, `/register`, lupa kata laluan, `/profile`, `/dashboard`.
Role: `buyer` (default bila daftar), `seller`, `admin` — enum `App\Enums\UserRole`.
Hadkan route ikut role dengan middleware `role`, contoh `->middleware(['auth', 'role:seller'])`.

## Penjual
- `/buka-kedai` — pembeli buka kedai (nama, bank) → role jadi `seller`
- `/penjual/produk` — tambah / edit / padam produk (dengan gambar)
- `/penjual/live` — cipta sesi live, pilih produk, buka **Studio**

## Troli, checkout & bayaran
- `/troli` → `/checkout` (alamat) → bayaran → `/pesanan`
- Satu bayaran untuk semua kedai; sistem pecahkan jadi **satu pesanan per kedai** (pos RM8 setiap kedai)
- Bayaran sekarang guna gateway **simulasi** (`PAYMENT_GATEWAY=simulasi`) — pilih "Berjaya" atau "Gagal".
  Bayarcash akan masuk melalui `App\Services\Payments\PaymentGateway`.
- Bila bayaran berjaya: stok ditolak, dan ledger `wallet_transactions` rekod jualan untuk kedai,
  **RM1 caj platform** ditolak dari kedai dan masuk ke akaun platform (`shop_id = null`).
  Tetapan dalam `config/marketplace.php`.

## Dashboard
- Penjual `/penjual`: baki, jualan bulan ini, pesanan perlu dihantar, carta 14 hari; `/penjual/pesanan` untuk tanda dihantar + no. tracking
- Admin `/admin`: komisen terkumpul, GMV, kedai teratas, pesanan terkini

## Live Shopping
- Studio penjual: Mula/Tamat Live, kamera (LiveKit), **pin produk** (kad keluar atas video penonton),
  suis **Auto-jawab komen**
- Penonton `/live/{id}`: video, kad produk dipin + butang Beli, komen (polling 2 saat — tiada server WebSocket)
- **Auto-jawab**: soalan harga / stok / pos / cara beli dijawab automatik dari data produk dipin.
  Set `OLLAMA_URL` (cth `http://127.0.0.1:11434`) untuk guna Qwen — kalau AI gagal, jatuh balik ke peraturan.

### Hidupkan video di laptop
1. Muat turun `livekit-server` (Windows) dari GitHub `livekit/livekit` → Releases, jalankan: `livekit-server --dev`
2. `.env`: `LIVEKIT_URL=ws://localhost:7880`, `LIVEKIT_API_KEY=devkey`, `LIVEKIT_API_SECRET=secret`
3. Buka app melalui **http://localhost:8000** (`php artisan serve`) — browser hanya benarkan kamera di `localhost` atau `https`.

## Halaman siap
`/` Utama · `/teroka` (cari, tapis kategori, susun) · `/produk/{slug}` · `/kedai` · `/kedai/{slug}`

## Belum dibina (papar "Akan Datang")
`/favorit`, `/ai` — seterusnya: Bayarcash sebenar, withdraw penjual, AI chatbot.

## Test
```bash
php artisan test
```
