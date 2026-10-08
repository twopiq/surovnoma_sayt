<?php

return [
    // Bitta qurilma (brauzer cookie) uchun limit
    'device_per_hour' => (int) env('GUEST_LIMIT_DEVICE_HOUR', 3),
    'device_per_day' => (int) env('GUEST_LIMIT_DEVICE_DAY', 5),

    // Bitta IP uchun limit (institut NAT ortida ko'p kompyuter bitta IP da bo'lishi mumkin)
    'ip_per_hour' => (int) env('GUEST_LIMIT_IP_HOUR', 10),
    'ip_per_day' => (int) env('GUEST_LIMIT_IP_DAY', 30),

    // Bitta email yoki telefon raqami uchun kunlik limit
    'contact_per_day' => (int) env('GUEST_LIMIT_CONTACT_DAY', 5),

    // Forma ochilgandan keyin yuborishgacha minimal vaqt (bot himoyasi)
    'min_fill_seconds' => (int) env('GUEST_MIN_FILL_SECONDS', 4),

    // Har qanday POST so'rov uchun daqiqalik limit (IP bo'yicha)
    'post_per_minute' => (int) env('GUEST_POST_PER_MINUTE', 10),

    'device_cookie' => 'rtt_guest_device',

    // Ishonchli IP/CIDR ro'yxati (vergul bilan): IP limitlari va IP bloklari ularga ta'sir qilmaydi
    'whitelist_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('GUEST_WHITELIST_IPS', ''))))),

    // Bir xil tavsif shu muddat ichida qayta yuborilsa rad etiladi (soat, 0 = o'chirilgan)
    'duplicate_window_hours' => (int) env('GUEST_DUPLICATE_WINDOW_HOURS', 24),

    // Guest uchun fayl cheklovi
    'max_files' => (int) env('GUEST_MAX_FILES', 3),
    'max_file_size_kb' => (int) env('GUEST_MAX_FILE_SIZE_KB', 2048),

    // Yangi blok haqida Telegram ogohlantirish (adminlarga, soatiga ko'pi bilan N ta)
    'alert_enabled' => (bool) env('GUEST_BLOCK_ALERT', true),
    'alert_per_hour' => (int) env('GUEST_BLOCK_ALERT_PER_HOUR', 10),
];
