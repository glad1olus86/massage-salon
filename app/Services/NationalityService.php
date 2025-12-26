<?php

namespace App\Services;

class NationalityService
{
    /**
     * Get all nationalities with translation keys
     * Keys are in English, values will be translated via __()
     */
    public static function getAll(): array
    {
        return [
            'UA' => 'Ukraine',
            'RU' => 'Russia',
            'BY' => 'Belarus',
            'PL' => 'Poland',
            'CZ' => 'Czech Republic',
            'SK' => 'Slovakia',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'PT' => 'Portugal',
            'GB' => 'United Kingdom',
            'IE' => 'Ireland',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'AT' => 'Austria',
            'CH' => 'Switzerland',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'EE' => 'Estonia',
            'LV' => 'Latvia',
            'LT' => 'Lithuania',
            'HU' => 'Hungary',
            'RO' => 'Romania',
            'BG' => 'Bulgaria',
            'HR' => 'Croatia',
            'SI' => 'Slovenia',
            'RS' => 'Serbia',
            'BA' => 'Bosnia and Herzegovina',
            'ME' => 'Montenegro',
            'MK' => 'North Macedonia',
            'AL' => 'Albania',
            'GR' => 'Greece',
            'TR' => 'Turkey',
            'GE' => 'Georgia',
            'AM' => 'Armenia',
            'AZ' => 'Azerbaijan',
            'KZ' => 'Kazakhstan',
            'UZ' => 'Uzbekistan',
            'TJ' => 'Tajikistan',
            'KG' => 'Kyrgyzstan',
            'TM' => 'Turkmenistan',
            'MD' => 'Moldova',
            'US' => 'United States',
            'CA' => 'Canada',
            'MX' => 'Mexico',
            'BR' => 'Brazil',
            'AR' => 'Argentina',
            'CO' => 'Colombia',
            'PE' => 'Peru',
            'VE' => 'Venezuela',
            'CL' => 'Chile',
            'CN' => 'China',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'IN' => 'India',
            'PK' => 'Pakistan',
            'BD' => 'Bangladesh',
            'VN' => 'Vietnam',
            'TH' => 'Thailand',
            'ID' => 'Indonesia',
            'MY' => 'Malaysia',
            'PH' => 'Philippines',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            'EG' => 'Egypt',
            'MA' => 'Morocco',
            'DZ' => 'Algeria',
            'TN' => 'Tunisia',
            'NG' => 'Nigeria',
            'ZA' => 'South Africa',
            'KE' => 'Kenya',
            'ET' => 'Ethiopia',
            'IL' => 'Israel',
            'SA' => 'Saudi Arabia',
            'AE' => 'United Arab Emirates',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'SY' => 'Syria',
            'AF' => 'Afghanistan',
            'NP' => 'Nepal',
            'LK' => 'Sri Lanka',
            'MM' => 'Myanmar',
            'KH' => 'Cambodia',
            'LA' => 'Laos',
            'MN' => 'Mongolia',
            'CY' => 'Cyprus',
            'MT' => 'Malta',
            'LU' => 'Luxembourg',
            'IS' => 'Iceland',
        ];
    }

    /**
     * Get translated nationalities for current locale
     */
    public static function getTranslated(): array
    {
        $result = [];
        foreach (self::getAll() as $code => $name) {
            $result[$code] = __($name);
        }
        return $result;
    }

    /**
     * Get nationalities with both key (English) and translated name
     * Returns array of ['key' => 'Ukraine', 'name' => 'Україна']
     */
    public static function getWithKeys(): array
    {
        $result = [];
        foreach (self::getAll() as $code => $name) {
            $result[] = [
                'code' => $code,
                'key' => $name,  // English key for DB
                'name' => __($name),  // Translated for display
            ];
        }
        return $result;
    }

    /**
     * Get nationality name by code
     */
    public static function getName(string $code): ?string
    {
        $all = self::getAll();
        return isset($all[$code]) ? __($all[$code]) : null;
    }
}
