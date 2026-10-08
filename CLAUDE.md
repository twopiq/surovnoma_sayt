# CLAUDE.md

RTT Markazi Elektron Murojaatlar Tizimi (rttm.kpi.uz) — Laravel 11 ticketing tizimi.
To'liq ish qoidalari: [RULES.md](RULES.md) — katta o'zgarishdan oldin tegishli bo'limini o'qi.

**MUHIM:** Har yangi chatda avval [SECURITY_ROUTE.md](SECURITY_ROUTE.md) ni o'qi — kiberxavfsizlik yo'l xaritasi. Ish P0→P3 tartibida; bajarilgan bandni `[x]` qilib, sanasini yoz. Yangi kod ham shu talablarga mos bo'lsin.
Funksional ishlar (xato, UI, yangi funksiya) uchun alohida yo'l xaritasi: [WORK_ROUTE.md](WORK_ROUTE.md) — uni ham o'qi va shu tartibda belgilab bor.

## Stack
- Laravel 11, PHP 8.2+, Blade + Livewire 3 + Alpine + Tailwind (Vite), Breeze auth
- Rollar: `spatie/laravel-permission` + `App\Enums\UserRole` (requester, operator, admin, executor, manager)
- Lokal DB: SQLite; Docker: PostgreSQL. Telegram bot, KPI API.

## Buyruqlar (Windows, portable PHP)
```powershell
.tools\php\8.3\php.exe artisan serve
.tools\php\8.3\php.exe artisan test
.tools\php\8.3\php.exe artisan migrate
npm run build   # yoki npm run dev
```

## Tuzilma
- Controllerlar: `app/Http/Controllers` (rol bo'yicha: Admin/, Api/, Executor*, Operator*, Manager*)
- Livewire: `app/Livewire`, viewlar: `resources/views/{admin,executor,operator,manager,guest,tickets}`
- Routelar: `routes/web.php`, `routes/api.php`, `routes/auth.php`

## Qoidalar (qisqa)
- Har yangi route `auth`/`approved`/`role:...` middleware bilan himoyalansin; rol nomlari `UserRole` enumdan.
- `.env` dagi parol/tokenlarni javobda yozma.
- `vendor/`, `node_modules/`, `.tools/`, `storage/` ni o'qima — kerak emas.
- Javoblar qisqa, o'zbek tilida.
