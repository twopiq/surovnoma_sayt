@include('errors._immersive', [
    'statusCode' => 500,
    'eyebrow' => 'Ichki server xatoligi',
    'headline' => "Nimadir noto'g'ri ketdi",
    'lead' => "Tizim so'rovni yakunlay olmadi. Muammo qaytalansa, administratorga vaqt va amal tafsilotlarini yuboring.",
    'details' => [
        'Service' => 'RTT Markazi',
        'Status' => 'Internal server error',
    ],
])
