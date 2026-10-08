@include('errors._immersive', [
    'statusCode' => 413,
    'eyebrow' => "Ma'lumot hajmi chegaradan oshdi",
    'headline' => "Fayl hajmi juda katta",
    'lead' => __('Yuborilgan fayllar umumiy hajmi ruxsat etilgan :size limitdan oshib ketdi. Fayllarni kamaytirib yoki siqib qayta yuboring.', ['size' => $maxSize ?? '25 MB']),
    'details' => [
        'Ruxsat etilgan jami fayl hajmi' => $maxSize ?? '25 MB',
        'Har bir fayl' => __(':size gacha', ['size' => $maxFileSize ?? '5 MB']),
        'Fayllar soni' => __(':n tagacha', ['n' => $maxFiles ?? 5]),
        'Server so\'rov limiti' => $serverLimit ?? '32M',
    ],
    'homeLabel' => 'Asosiy sahifa',
])
