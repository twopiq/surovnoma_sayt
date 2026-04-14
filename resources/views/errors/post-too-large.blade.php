@include('errors._immersive', [
    'statusCode' => 413,
    'eyebrow' => "Ma'lumot hajmi chegaradan oshdi",
    'headline' => "Fayl hajmi juda katta",
    'lead' => "Yuborilgan fayllar umumiy hajmi ruxsat etilgan 25 MB limitdan oshib ketdi. Fayllarni kamaytirib yoki siqib qayta yuboring.",
    'details' => [
        'Ruxsat etilgan jami fayl hajmi' => $maxSize ?? '25 MB',
        'Har bir fayl' => '5 MB gacha',
        'Fayllar soni' => '5 tagacha',
        'Server so\'rov limiti' => $serverLimit ?? '32M',
    ],
    'homeLabel' => 'Asosiy sahifa',
])
