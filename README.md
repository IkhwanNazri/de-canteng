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

## Halaman siap
`/` Utama · `/teroka` (cari, tapis kategori, susun) · `/produk/{slug}` · `/kedai` · `/kedai/{slug}`

## Belum dibina (papar "Akan Datang")
`/troli`, `/favorit`, `/buka-kedai`, `/live`, `/ai` — Fasa 1 seterusnya: dashboard seller, troli & checkout Bayarcash, ledger komisen RM1.

## Test
```bash
php artisan test
```
