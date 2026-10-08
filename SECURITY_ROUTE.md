# SECURITY_ROUTE — Kiberxavfsizlik yo'l xaritasi (rttm.kpi.uz)

> Har yangi chatda AVVAL shu faylni o'qi. Ish shu ro'yxat bo'yicha, yuqoridan pastga (P0 → P3) olib boriladi.
> Bandni bajargach `[ ]` → `[x]` qil, sanani va qisqa izohni yoz. Yangi topilma bo'lsa tegishli bo'limga qo'sh.
> Audit sanasi: 2026-10-08.

## 0. Me'yoriy asos (qisqa)
- "Kiberxavfsizlik to'g'risida"gi Qonun (O'RQ-764, 15.04.2022) — davlat axborot tizimlari kiberxavfsizlik talablariga muvofiqlik ekspertizasidan o'tadi; vakolatli organ — DXX, ijrochi — "Kiberxavfsizlik markazi" DUK (csec.uz). Intsidentlar markazga xabar qilinadi.
- "Shaxsga doir ma'lumotlar to'g'risida"gi Qonun (O'RQ-547) + 27¹-modda — O'zbekiston fuqarolari ma'lumotlari **O'zbekiston hududidagi serverlarda** saqlanadi, baza **Shaxsga doir ma'lumotlar bazalari davlat reyestri**da ro'yxatdan o'tkaziladi.
- O'z DSt ISO/IEC 27001/27002 — axborot xavfsizligini boshqarish (siyosat, kirish nazorati, log, zaxira, intsident).
- Amaliy texnik bazis: OWASP Top 10 / ASVS L2.

## P0 — Kritik (darhol)
- [x] **(2026-10-08)** Fayllar endi `local` diskda; `GET /attachments/{id}` (`TicketAttachmentController`, rol/egalik tekshiruvi + audit log); eski fayllar uchun `php artisan attachments:make-private`; test: `AttachmentAccessTest`. Qoldi: guest/executor/admin sahifalarida fayl ro'yxati umuman ko'rsatilmaydi (kerak bo'lsa qo'shish). Prod'da buyruqni ishga tushirish kerak.
- [ ] **KPI API login rate limitsiz.** `routes/api.php` `/auth/login` — brute-force mumkin. `throttle` (masalan 5/min login+IP) qo'shish.
- [ ] **KPI CORS default `*` + credentials.** `app/Http/Middleware/KpiApiCors.php` — env bo'lmasa `*`. Default'ni bo'sh ro'yxat qilish, `*` ni credentials bilan taqiqlash. `env()` → `config()` ga ko'chirish (config:cache da `env()` null qaytaradi!). `KpiAuthController` dagi `env()` ham shunday.
- [ ] **Telegram webhook secretsiz ochiq.** `TelegramWebhookController` — secret bo'sh bo'lsa har kim update yubora oladi. Prod'da secret majburiy (yo'q bo'lsa 403). Path'dagi secret (`/webhook/{secret}`) loglarga tushadi — faqat header varianti qolsin.
- [ ] **Prod `.env` tekshiruvi:** `APP_DEBUG=false`, `APP_ENV=production`, `LOG_LEVEL=warning`, `TELESCOPE_ENABLED=false`, `SESSION_SECURE_COOKIE=true`, `APP_URL=https://...`. `.env.example` da xavfsiz default'lar.

## P1 — Yuqori
- [ ] **Xavfsizlik HTTP sarlavhalari** yo'q. Global middleware: `Strict-Transport-Security`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` (yoki CSP `frame-ancestors`), `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, `Content-Security-Policy` (Livewire/Alpine/Vite ga mos, avval Report-Only).
- [ ] **Parol siyosati** — `Password::defaults()` sozlanmagan (faqat 8 belgi). `AppServiceProvider` da: min 12, katta/kichik harf, raqam, belgi, `uncompromised()` (internet bo'lsa).
- [ ] **2FA** — admin/manager/operator uchun majburiy (TOTP yoki email kod). Rejasi memory'da (email/2FA keyinga qoldirilgan).
- [ ] **Sessiya:** `SESSION_ENCRYPT=true`, `SESSION_LIFETIME` ≤ 30 (admin uchun), login/logout'da sessiya regeneratsiyasi (Breeze bor — tekshirish), parol o'zgarganda boshqa sessiyalarni o'chirish (`logoutOtherDevices`).
- [ ] **KPI token bekor qilinmaydi** — logout faqat cookie'ni o'chiradi, token 14 kungacha amal qiladi. Token'ni DB'da saqlash/versiyalash (yoki Sanctum) va logout'da revoke.
- [ ] **Audit log qamrovi** — `AuditLog`/`SystemLog` bor. Tekshirish va qo'shish: muvaffaqiyatli/muvaffaqiyatsiz login, parol tiklash, rol/ruxsat o'zgarishi, user tasdiqlash, eksport (CSV/XLSX), fayl yuklab olish. IP + user-agent + vaqt. Saqlash muddati ≥ 1 yil, o'chirish faqat tizim tomonidan.
- [ ] **Nginx (docker/nginx/default.conf):** HTTPS (TLS 1.2+/1.3), 80→443 redirect, `server_tokens off`, dotfile'larni bloklash (`location ~ /\.`), `/storage` ichida PHP bajarilishini taqiqlash, sarlavhalar, `limit_req` login/guest uchun.
- [ ] **Telescope** — gate bo'sh ro'yxat (yaxshi), lekin prod'da paketni umuman yuklamaslik (`dont-discover` yoki `register` faqat local).

## P2 — O'rta
- [ ] **Fayl yuklash:** DOC/DOCX makros xavfi — ClamAV skan (yoki faqat PDF/JPG/PNG), MIME'ni kontent bo'yicha tekshirish, rasmlarni qayta kodlash (EXIF tozalash), saqlanadigan nom random (hozir `store()` — OK).
- [ ] **Zaxira nusxalar** (`config/database-backup.php`) — shifrlash, server tashqarisiga (O'zbekiston hududidagi) nusxa, restore'ni har oy sinash, kirish huquqi faqat admin.
- [ ] **`/_errors/{code}` preview route** ochiq — faqat `local` muhitda yoki admin uchun.
- [ ] **Ro'yxatdan o'tish** — CAPTCHA (yoki honeypot) + throttle; email tasdiqlash (memory: SMTP keyinga qoldirilgan).
- [ ] **Account lockout** — 5 xatodan keyin vaqtinchalik blok bor (Breeze RateLimiter); adminlarga bildirishnoma qo'shish.
- [ ] **Eksportlar** (`TableExport::download`) — CSV injection (`=`,`+`,`-`,`@` bilan boshlanuvchi kataklarni qochirish), audit log.
- [ ] **Bog'liqliklar:** `composer audit`, `npm audit` — CI/oyiga bir marta; Laravel/PHP yangilanishlari.

## P3 — Tashkiliy / me'yoriy
- [ ] Hosting O'zbekiston hududida (27¹-modda) — tasdiqlash va hujjatlashtirish.
- [ ] Shaxsga doir ma'lumotlar bazasini davlat reyestrida ro'yxatdan o'tkazish (pd.gov.uz).
- [ ] Saytda **maxfiylik siyosati** (qanday ma'lumot, maqsad, muddat, rozilik) + guest formada rozilik belgisi.
- [ ] Axborot xavfsizligi siyosati, mas'ul shaxs tayinlash, intsidentga javob reglamenti (Kiberxavfsizlik markaziga xabar berish tartibi).
- [ ] Kiberxavfsizlik markazi ekspertizasi / pentest uchun ariza; topilmalarni shu faylga qo'shish.
- [ ] Ma'lumotlarni saqlash muddati va avtomatik tozalash (eski murojaatlar, fayllar, loglar).

## Bajarilganlar jurnali
<!-- 2026-10-08: audit o'tkazildi, route yaratildi. -->
- [x] Mehmon (guest) so'rovlari uchun rate-limit + IP blok (`GuestRequestGuard`, `throttle:guest-post`) — mavjud.
- [x] Rol asosidagi kirish (spatie) va ticket `show` larda egalik tekshiruvi (IDOR yo'q) — tekshirildi.
- [x] Blade'da `{!! !!}` ishlatilmagan; raw SQL'lar parametrsiz konstantalar — xavfsiz.
