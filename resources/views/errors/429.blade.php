@include('errors._immersive', [
    'statusCode' => 429,
    'eyebrow' => "So'rovlar limiti",
    'headline' => "Juda ko'p urinish",
    'lead' => "Tizim qisqa vaqt ichida juda ko'p so'rov qabul qildi. Iltimos, biroz kutib qayta urinib ko'ring.",
    'details' => [
        'Advice' => 'Wait and retry',
        'Status' => 'Too many requests',
    ],
])
