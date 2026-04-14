@include('errors._immersive', [
    'statusCode' => 503,
    'eyebrow' => 'Xizmat vaqtincha mavjud emas',
    'headline' => "Tizim tanaffusda",
    'lead' => "Texnik ishlar yoki vaqtinchalik yuklama sabab tizim hozir javob bera olmayapti. Iltimos, birozdan keyin qayta urinib ko'ring.",
    'details' => [
        'Service' => 'Temporarily unavailable',
        'Status' => 'Maintenance',
    ],
])
