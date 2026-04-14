@include('errors._immersive', [
    'statusCode' => 404,
    'eyebrow' => 'Sahifa topilmadi',
    'headline' => "Manzil topilmadi",
    'lead' => "Siz izlagan sahifa mavjud emas yoki boshqa manzilga ko'chirilgan. Asosiy menyudan kerakli bo'limga qaytishingiz mumkin.",
    'details' => [
        'Request URL' => request()->fullUrl(),
        'Status' => 'Page not found',
    ],
])
