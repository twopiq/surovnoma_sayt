# WORK_ROUTE — Funksional ishlar yo'l xaritasi (rttm.kpi.uz)

> Kiberxavfsizlikdan tashqari ishlar (xato tuzatish, UI/UX, yangi funksiyalar). Xavfsizlik ishlari → [SECURITY_ROUTE.md](SECURITY_ROUTE.md).
> Har yangi chatda shu faylni ham o'qi. Bandni bajargach `[ ]` → `[x]`, sana va qisqa izoh yoz. Yangi vazifa kelsa ro'yxat oxiriga qo'sh.

## Ochiq vazifalar

### 1. Parolni tiklash qismini to'g'rilash
- [ ] `PasswordResetTest` dagi 3 ta test yiqilyapti: test `Illuminate\Auth\Notifications\ResetPassword` ni kutadi, `User::sendPasswordResetNotification()` esa `App\Notifications\ResetPasswordNotification` yuboradi.
- [ ] To'liq oqimni tekshirish: forgot-password → email (SMTP sozlamasi, memory: email keyinga qoldirilgan) → `reset-password/{token}` → yangi parol → login. Xabar matnlari o'zbekcha, token muddati, throttle.
- [ ] Topilgan xatolarni tuzatish va testlarni yangi notification'ga moslash, ularni yashil holatga keltirish.
- Fayllar: `app/Http/Controllers/Auth/PasswordResetLinkController.php`, `NewPasswordController.php`, `app/Notifications/ResetPasswordNotification.php`, `app/Models/User.php:165`, `resources/views/auth/{forgot,reset}-password.blade.php`, `tests/Feature/Auth/PasswordResetTest.php`.

### 2. Breadcrumb (Admin / Ticket management / Tickets) ni to'g'rilash
Fayl: `resources/views/partials/breadcrumbs.blade.php` (`layouts/app.blade.php:37` da ulanadi).
- [ ] Har bir oraliq element **ishlaydigan havola** bo'lsin (oxirgisi — joriy sahifa, havolasiz).
- [ ] **Faqat foydalanuvchi kira oladigan** sahifalar ko'rsatilsin: havola berishdan oldin rol/ruxsatni tekshirish (masalan `Admin` → `dashboard` faqat admin uchun; manager/executor/operator/requester uchun o'z bosh sahifasi). Ruxsati yo'q bo'g'in ko'rsatilmasin.
- [ ] **Ortiqcha bo'g'inlarni olib tashlash**: masalan `Ticket management` va `Tickets` ikkalasi ham `admin.dispatch.tickets` ga olib boradi — takrorlanmasin; bir xil URL'li qo'shni elementlar birlashtirilsin.
- [ ] Yorliqlarni o'zbekchaga o'tkazish (hozir inglizcha: Admin, Users, Tickets...), sayt tiliga mos.
- [ ] Barcha rollar uchun barcha sahifalarda tekshiruv (test: har rol uchun breadcrumb'dagi havolalar 200 qaytarishi).

## Bajarilganlar jurnali
<!-- 2026-10-08: fayl yaratildi. -->
