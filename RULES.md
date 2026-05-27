# Loyiha qoidalari (surovnoma_sayt)

Har qanday AI yordamchi (Claude, Codex va h.k.) bu loyihada ish boshlashdan oldin shu faylni o'qib chiqsin va quyidagi qoidalarga amal qilsin. Foydalanuvchi har suhbatda qoidalarni qaytadan tushuntirib o'tirmaydi; shu fayl loyiha bo'yicha asosiy ish tartibi hisoblanadi.

---

## 1. Loyiha konteksti

Bu loyiha **RTT Markazi Elektron Murojaatlar Tizimi**: Laravel 11 asosidagi murojaat/ticketing tizimi.

Asosiy stack:

- Backend: Laravel 11, PHP 8.2+, Eloquent, database queue/session/cache.
- Frontend: Blade, Livewire 3, Alpine.js, Tailwind CSS, Vite.
- Auth: Laravel Breeze.
- Rollar: `spatie/laravel-permission`, `App\Enums\UserRole`.
- Mavjud rollar: `requester`, `operator`, `admin`, `executor`, `manager`.
- Integratsiyalar: Telegram bot/webhook, KPI API endpointlari.
- Lokal DB odatda SQLite; Docker compose ichida PostgreSQL ishlatiladi.
- Muhim domenlar: ticket yaratish, operator kiritishi, admin dispatch, executor ijrosi, manager dashboard, guest tracking, SLA kalendari, audit log, notification va file attachment.

Ish boshlashdan oldin vazifaga tegishli fayllarni o'qib chiq:

- [README.md](README.md)
- [.env.example](.env.example)
- [composer.json](composer.json)
- [package.json](package.json)
- [routes/web.php](routes/web.php)
- [routes/api.php](routes/api.php)
- [app/Enums/UserRole.php](app/Enums/UserRole.php)
- [app/Models/User.php](app/Models/User.php)
- Kerak bo'lsa: `config/services.php`, `config/filesystems.php`, `config/queue.php`, `config/cache.php`, `config/session.php`

`.env` faylini faqat kerak bo'lsa o'qi; undagi token, parol va webhook secretlarni javobda yozma.

---

## 2. Rollar va access control

Bu loyihada asosiy ruxsat modeli hozircha **role-based**. Yangi route, sahifa, controller action yoki tugma qo'shilganda:

- Route ochiq qolmasin: kerakli joyda `auth`, `approved`, `role:...` middleware ishlatilsin.
- Rol nomlari hardcode qilinmasin; iloji boricha `App\Enums\UserRole` dan foydalanilsin.
- Admin funksiyalar `role:admin`, manager statistikasi `role:admin|manager`, executor/requester/operator bo'limlari o'z rollari bilan himoyalansin.
- Controller ichida qo'shimcha tekshiruv kerak bo'lsa `hasSystemRole(...)`, `hasRole(...)` yoki aniq policy/guard ishlatilsin.
- Blade navigation, menu va tugmalar backend route himoyasiga mos bo'lsin; ko'rinishi ham `hasRole(...)` yoki model metodlari bilan boshqarilsin.
- Yangi rol qo'shilsa `UserRole` enum, seeder, route middleware, testlar va UI nav birga yangilansin.
- Agar kelajakda granular permissionlar (`can:...`, `Permission` modeli) faol ishlatilsa, permission yaratish, admin roliga biriktirish va seederda tiklash majburiy bo'ladi.

Access control keyinga qoldirilmasin; feature bilan bir vaqtda yopilsin.

---

## 3. UI o'zgarishlari

UI bu loyihada Blade, Livewire, Alpine va Tailwind orqali qurilgan. O'zgarishlar mavjud dizayn tiliga mos bo'lsin:

- Mavjud layout, component va partiallardan foydalan: `resources/views/layouts`, `resources/views/components`, `resources/views/partials`.
- Katta yoki bahsli UI o'zgarishlarda avval 2-3 variant taklif qil; foydalanuvchi tanlagandan keyin kodga o't.
- Mayda tuzatishlar (xato matn, spacing, bitta klass, obvious bug) uchun ortiqcha to'xtab qolma.
- Yangi rang, shrift yoki butunlay yangi visual uslub kiritishdan oldin sababini tushuntir.
- Formlarda validation error, success/error flash, loading/disabled state va mobile ko'rinish hisobga olinsin.
- Admin, operator, executor, requester va guest ekranlarida bir xil terminlar ishlatilsin.
- Frontend o'zgarsa `npm run build` bilan asset build tekshirilsin.
- **Til qoidasi:** Jadval ustuni nomlari (`<th>`) ingliz tilida qolishi mumkin. Boshqa barcha interfeys matnlari (sarlavhalar, tugmalar, labellar, placeholder, filter variantlar, status badgelar, bo'sh holat matnlari) o'zbek tilida bo'lsin.

---

## 4. Xavfli o'zgarishlardan oldin ogohlantir

Quyidagi ishlarni boshlashdan oldin foydalanuvchini ogohlantir, sababini tushuntir va aniq tasdiq ol:

- Migrationda ustun o'chirish, ustun turini o'zgartirish, `NOT NULL` qo'shish, katta indekslar.
- `migrate:fresh`, `db:wipe`, `db:restore --force`, backupni ustidan yozish.
- `docker compose down -v`, volume o'chirish, database containerni tozalash.
- Production yoki real foydalanuvchi ma'lumotlariga ta'sir qiluvchi `.env` o'zgarishlari.
- Telegram webhook, bot token, KPI API CORS/cookie sozlamalarini o'zgartirish.
- Composer/npm paketini olib tashlash yoki major upgrade qilish.
- Queue, session, cache, filesystem driverlarini o'zgartirish.
- Katta jadvallarga sync yozish, og'ir job/command yoki N+1 so'rov paydo qilishi mumkin bo'lgan o'zgarish.

Ogohlantirish formati:

> Bu ish X muammoga olib kelishi mumkin, sababi Y. Davom etaymi?

---

## 5. Env va config qoidalari

- Controller, model yoki service ichida `env(...)` chaqirma; qiymatlarni `config(...)` orqali o'qi.
- Yangi env o'zgaruvchisi kerak bo'lsa `.env.example` ham yangilansin.
- Telegram sozlamalari `config/services.php` orqali yuritilsin.
- File upload/storage ishlari `config/filesystems.php`, `Storage` facade va mavjud helperlar orqali qilinsin.
- URLlar `route()`, `url()`, `asset()` kabi Laravel helperlari bilan qurilsin; domen/port hardcode qilinmasin.
- Fayl yo'llari `storage_path()`, `base_path()`, `public_path()` orqali qurilsin.
- SQLite va PostgreSQL farqlarini hisobga ol; imkon qadar Eloquent yoki Query Builder ishlat.
- `config:cache` productionda env qiymatlarini muzlatishini unutma; env o'zgarsa cache clear kerak.

---

## 6. Lokal ishga tushirish va Docker

Lokal portable PHP varianti:

```powershell
.tools\php\8.3\php.exe artisan migrate:fresh --seed
.tools\php\8.3\php.exe artisan serve
```

Test:

```powershell
.tools\php\8.3\php.exe artisan test
```

Frontend:

```powershell
npm install
npm run build
```

Docker faqat lokal compose uchun:

```powershell
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan migrate:fresh --seed
```

Qoidalar:

- `docker-compose.yml` lokal ish uchun; production sxemasi alohida tasdiqlanmagan bo'lsa taxmin qilinmasin.
- `docker compose down -v` faqat foydalanuvchi tasdig'i bilan.
- Lokal tekshiruv uchun `artisan test`, `npm run build`, `docker compose ps/logs/exec` kabi nondestructive buyruqlarni ishlatish mumkin.
- `migrate:fresh --seed` ma'lumotlarni o'chiradi; avval ogohlantir.

---

## 7. Ma'lumot, backup va file upload

- Backup/restore funksiyalari mavjud: `db:backup`, `db:backup-list`, `db:restore`.
- Restore qilishdan oldin foydalanuvchi tasdig'i shart.
- Attachment va upload limitlari uchun mavjud koddan foydalan: `App\Support\TicketFileUpload`.
- Public fayllar uchun `php artisan storage:link` kerak bo'lishi mumkin; symlink holatini tekshir.
- Audit log, status history, notification va ticket assignment kabi iz qoldiruvchi jadvallarni feature bilan birga yangilash kerak bo'lishi mumkin.

---

## 8. Test va sifat nazorati

- Mavjud testlar sindirilmasin.
- Backend o'zgarishdan keyin kamida tegishli test yoki to'liq `artisan test` ishlatilsin.
- Frontend yoki Blade asset o'zgarsa `npm run build` ishlatilsin.
- Access control o'zgarsa `tests/Feature/RoleAccessTest.php` va tegishli flow testlar ko'rib chiqilsin.
- Ticket lifecycle o'zgarsa `ReturnRequestFlowTest`, `ApprovalFlowTest`, `NotificationAndAttachmentTest` kabi testlarni ishga tushirishni ko'rib chiq.
- Telegram bot action testlari `TelegramBotActionTest.php` da yozilsin. Mocking tartibi: `TelegramSdkBot` `Mockery::mock` bilan almashtirilsin, `services.telegram_bot.webhook_secret` `null` qilinsin, bot action ikkita `postJson` (callback + text) bilan simulyatsiya qilinsin.

---

## 9. Telegram bot qoidalari

Bu loyihada Telegram bot faqat bildirishnoma yoki ma'lumot ko'rish kanali emas; u saytning amaliy ish oqimlari uchun ikkinchi interfeys hisoblanadi.

- Saytga yangi feature, status, action yoki rolga tegishli workflow qo'shilsa, shu imkoniyat Telegram botda ham kerak-kerak emasligi albatta tekshirilsin.
- Ticket lifecycle o'zgarishlari botda ham aks etsin: yaratish, ko'rish, qabul qilish, izoh qoldirish, qaytarish, bajarish, admin/manager xulosalari.
- Botda bajarilgan actionlar sayt bilan bir xil service/domain logikadan foydalansin; controllerdagi validatsiyani nusxalamasdan, umumiy service ishlatilsin.
- Bot actionlari role-based access bilan himoyalansin: requester faqat o'z murojaatlari, executor o'ziga ochiq vazifalar, admin/manager o'z rollari doirasida ishlasin.
- Bot callback va state nomlari aniq namespace bilan yozilsin (`executor:*`, `admin:*`, `guest:*`, `requester:*`) va eski callbacklar bilan to'qnashmasin.
- Yangi bot imkoniyati qo'shilsa, `telegram:status`, webhook sozlamalari, cache/config va kamida bitta tegishli test ko'rib chiqilsin.
- Webhook productionga chiqqandan keyin `php artisan telegram:status` bilan URL, pending updates va oxirgi xato tekshirilsin.

---

## 10. Kod yozish qoidalari

- Avval mavjud fayl va patternlarni o'qi; taxmin bilan kod yozma.
- Ortiqcha abstraksiya, ishlatilmaydigan helper yoki "kelajak uchun" parametr qo'shma.
- Kod commentlari qisqa bo'lsin; faqat nima uchun shunday qilingani noaniq bo'lsa yoz.
- Git commit yoki push faqat foydalanuvchi aniq so'raganda qilinsin.
- Foydalanuvchi o'zgartirgan fayllarni revert qilma; mavjud o'zgarishlar bilan ishlashga harakat qil.
- Javoblar qisqa, aniq va bajarilgan ishga bog'langan bo'lsin.

---

## 11. Javob berish tartibi

Ish oxirida qisqa hisobot ber:

- Qaysi fayllar o'zgardi.
- Nima olib tashlandi yoki moslashtirildi.
- Qanday tekshiruv bajarildi.
- Agar test/build ishlatilmagan bo'lsa, sababini ayt.

Eski LMS/HEMIS/Nuxt yoki ikki serverli production qoidalarini bu loyihaga tatbiq qilma, agar foydalanuvchi buni alohida so'ramasa.

---

## 12. Xato sahifalari va exception handling

Loyihada xato holatlari uchun alohida Blade view tizimi mavjud.

- Mavjud xato viewlari `resources/views/errors/` ichida: `403`, `404`, `419`, `429`, `500`, `503`, `post-too-large`, `assets-missing`.
- Yangi exception type uchun maxsus view kerak bo'lsa, quyidagi to'rt qadam bir vaqtda bajarilsin:
  1. `resources/views/errors/` ichiga view yaratilsin.
  2. `bootstrap/app.php` → `withExceptions()` ichida handler ro'yxatga olinsin.
  3. `routes/web.php` → `/_errors/...` pattern bo'yicha preview route qo'shilsin.
  4. `tests/Feature/ErrorPagesTest.php` ga shu preview route uchun test qo'shilsin.
- `ViteManifestNotFoundException` → `errors.assets-missing`: frontend build topilmaganda ko'rsatiladi; `npm run build` + `php artisan optimize:clear` bilan tuzatiladi.
- Xato viewlari `@extends` yoki mavjud layout ishlatmasdan standalone HTML yozishi mumkin — chunki layout o'zi Vite assetlarga tayangan, assetlar yo'q bo'lganda layout ham ishlamas edi.
- `/_errors/{code}` debug routelari faqat lokal tekshiruv uchun; haqiqiy xatoni trigger qilmasdan viewni ko'rish imkonini beradi.
