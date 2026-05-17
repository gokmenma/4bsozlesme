<?php

class AiService {
    private static $apiKey = 'YOUR_GEMINI_API_KEY'; // Buraya Google Gemini API anahtarı gelecek

    public static function scanImage($base64Image) {
        if (self::$apiKey === 'YOUR_GEMINI_API_KEY') {
            // Mock veri döndürelim (Geliştirme aşaması için)
            // Gerçek kullanımda burası Gemini API'sine istek atacak
            return [
                'success' => true,
                'data' => [
                    'tc_kimlik' => '12345678901',
                    'ad_soyad' => 'DEMO PERSONEL',
                    'telefon' => '0500 000 00 00'
                ]
            ];
        }

        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . self::$apiKey;

        $prompt = "Bu bir kimlik kartı veya personel belgesi görselidir. Lütfen görselden şu bilgileri ayıkla ve SADECE JSON formatında döndür: 
        tc_kimlik (11 haneli sayı), 
        ad_soyad (tam ad ve soyad), 
        telefon (varsa). 
        Format: {\"tc_kimlik\": \"...\", \"ad_soyad\": \"...\", \"telefon\": \"...\"}";

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt],
                        [
                            "inline_data" => [
                                "mime_type" => "image/jpeg",
                                "data" => $base64Image
                            ]
                        ]
                    ]
                ]
            ],
            "generationConfig" => [
                "response_mime_type" => "application/json"
            ]
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => 'API hatası: ' . $response];
        }

        $result = json_decode($response, true);
        $jsonText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        
        return [
            'success' => true,
            'data' => json_decode($jsonText, true)
        ];
    }
}
