# RTT Markazi Elektron Murojaatlar Tizimi

Laravel 11 asosidagi MVP ticketing tizimi. Loyihada requester, operator, admin, executor va manager rollari, guest tracking, SLA kalendari, audit log va CSV eksport mavjud.

## Lokal ishga tushirish

Portable PHP bilan:

```powershell
.tools\php\8.3\php.exe artisan migrate:fresh --seed
.tools\php\8.3\php.exe artisan serve
```

## Baza backup va restore

Avtomatik backup:

- tizim har 6 soatda `db:backup --label=scheduled` ni schedule orqali tayyorlab qo'yadi
- schedule ishlashi uchun Windows Task Scheduler yoki boshqa scheduler har daqiqada quyidagini ishga tushirishi kerak:

```powershell
.tools\php\8.3\php.exe artisan schedule:run
```

Qo'lda backup olish:

```powershell
.tools\php\8.3\php.exe artisan db:backup --label=manual
```

Mavjud backup ro'yxatini ko'rish:

```powershell
.tools\php\8.3\php.exe artisan db:backup-list
```

Oxirgi backupdan tiklash:

```powershell
.tools\php\8.3\php.exe artisan db:restore latest --force
```

Aniq fayldan tiklash:

```powershell
.tools\php\8.3\php.exe artisan db:restore scheduled-20260406-120000.sqlite --force
```

Backup fayllar joyi:

- `storage/app/backups/database`
- restore vaqtida tizim avtomatik `pre-restore-...sqlite` safety-backup ham yaratadi
- hozirgi kod `sqlite` baza uchun ishlaydi

<!--
.tools\php\8.3\php.exe artisan db:backup --label=manual
.tools\php\8.3\php.exe artisan db:backup-list
.tools\php\8.3\php.exe artisan db:restore latest --force
.tools\php\8.3\php.exe artisan db:restore scheduled-20260406-120000.sqlite --force
-->

Frontend assetlar uchun:

```powershell
npm install
npm run build
```

## Demo accountlar

- `admin@rtt.local` / `password`
- `operator@rtt.local` / `password`
- `executor@rtt.local` / `password`
- `manager@rtt.local` / `password`
- `requester@rtt.local` / `password`

## Docker

Compose fayl `docker-compose.yml` ichida. Docker engine tayyor bo‘lsa:

```powershell
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan migrate:fresh --seed
```

## Testlar

```powershell
.tools\php\8.3\php.exe artisan test
```
