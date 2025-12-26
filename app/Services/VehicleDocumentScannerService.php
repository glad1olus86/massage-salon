<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VehicleDocumentScannerService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('SCANNER_API_KEY');
    }

    /**
     * Scan vehicle registration document (tech passport) - supports multiple images
     * @param array $imagePaths Array of image file paths (front and back sides)
     */
    public function scanDocument(array $imagePaths): array
    {
        $parts = [];
        
        // Add prompt
        $parts[] = ['text' => $this->getPrompt()];
        
        // Add all images
        foreach ($imagePaths as $imagePath) {
            $imageData = file_get_contents($imagePath);
            $base64Image = base64_encode($imageData);
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $imagePath);
            finfo_close($finfo);
            
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $base64Image
                ]
            ];
        }

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$this->apiKey}",
                [
                    'contents' => [
                        ['parts' => $parts]
                    ]
                ]
            );

            if (!$response->successful()) {
                return ['error' => 'Gemini API error: ' . $response->body()];
            }

            $result = $response->json();
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
            
            if (!$text) {
                return ['error' => 'No response from Gemini'];
            }

            // Clean up response
            $text = preg_replace('/```json\s*/', '', $text);
            $text = preg_replace('/```\s*/', '', $text);
            $text = trim($text);

            $data = json_decode($text, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'error' => 'Failed to parse Gemini response',
                    'raw_text' => $text
                ];
            }

            return [
                'license_plate' => $data['license_plate'] ?? null,
                'registration_date' => $data['registration_date'] ?? null,
                'brand' => $data['brand'] ?? null,
                'color' => $data['color'] ?? null,
                'vin_code' => $data['vin_code'] ?? null,
                'engine_volume' => $data['engine_volume'] ?? null,
                'passport_fuel_consumption' => $data['passport_fuel_consumption'] ?? null,
            ];

        } catch (\Exception $e) {
            return ['error' => 'Error: ' . $e->getMessage()];
        }
    }

    private function getPrompt(): string
    {
        return 'Analyze these vehicle registration document photos. This can be a vehicle registration certificate from ANY country (Czech Republic, Germany, Poland, Slovakia, Ukraine, Russia, USA, etc.).

The document may have one or two sides. Extract the following data:

1. LICENSE PLATE NUMBER - the vehicle registration number/plate
2. FIRST REGISTRATION DATE - when the vehicle was first registered (not current owner date)
3. BRAND AND MODEL - manufacturer and model name combined (e.g. "BMW 320d", "Toyota Camry", "ŠKODA SUPERB")
4. COLOR - vehicle color if mentioned
5. VIN CODE - Vehicle Identification Number (usually 17 characters)
6. ENGINE VOLUME - engine displacement in cm³ (cubic centimeters), as integer
7. FUEL CONSUMPTION - combined/mixed fuel consumption in liters per 100km

Common field labels by country:
- Czech: Registrační značka, Datum první registrace, Tovární značka, VIN/E, Zdvihový objem (P.1), Spotřeba paliva (V.8)
- German: Kennzeichen, Erstzulassung, Hersteller/Typ, FIN, Hubraum, Kraftstoffverbrauch
- Polish: Numer rejestracyjny, Data pierwszej rejestracji, Marka/Model, VIN, Pojemność silnika
- Slovak: EČV, Dátum prvej evidencie, Značka/Typ, VIN, Zdvihový objem
- Russian/Ukrainian: Номерной знак, Дата регистрации, Марка/Модель, VIN, Объём двигателя

Return ONLY clean JSON without markdown formatting:
{
    "license_plate": "ABC1234",
    "registration_date": "2025-09-22",
    "brand": "BRAND MODEL",
    "color": "White",
    "vin_code": "WVWZZZ3CZWE123456",
    "engine_volume": 1998,
    "passport_fuel_consumption": 6.5
}

Rules:
- Date MUST be in YYYY-MM-DD format
- Engine volume as INTEGER in cm³
- Fuel consumption as DECIMAL number (liters/100km)
- If a field is not found or not readable, use null
- Combine brand and model into single "brand" field
- For license plate, include all characters as shown on document';
    }
}
