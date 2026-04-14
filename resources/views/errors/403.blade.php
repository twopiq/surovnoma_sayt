@include('errors._immersive', [
    'statusCode' => 403,
    'eyebrow' => 'Ruxsat cheklangan',
    'headline' => "Kirish rad etildi",
    'lead' => "Bu bo'limni ko'rish uchun sizda yetarli ruxsat yo'q. Agar bu xato deb o'ylasangiz, administratorga murojaat qiling.",
    'details' => [
        'Role' => auth()->user()?->display_role ?? 'Guest',
        'Status' => 'Forbidden',
    ],
])
