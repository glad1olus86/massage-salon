<?php

namespace App\Services;

class NationalityFlagService
{
    /**
     * Mapping of nationality keywords to country codes (ISO 3166-1 alpha-2)
     * Includes multiple languages: English, Russian, Ukrainian, Czech
     */
    private static array $nationalityMap = [
        // Ukraine
        'UA' => ['ukraine', 'украина', 'україна', 'ukrajina', 'ukrainian', 'украинец', 'українець', 'ukrajinec', 'украинка', 'українка'],
        
        // Russia
        'RU' => ['russia', 'россия', 'росія', 'rusko', 'russian', 'русский', 'російський', 'ruský', 'русская', 'російська'],
        
        // Czech Republic
        'CZ' => ['czech', 'чехия', 'чехія', 'česko', 'česká', 'чех', 'чешка', 'čech', 'češka', 'czech republic', 'чешская республика'],
        
        // Poland
        'PL' => ['poland', 'польша', 'польща', 'polsko', 'polish', 'поляк', 'полячка', 'polák', 'polka'],
        
        // Germany
        'DE' => ['germany', 'германия', 'німеччина', 'německo', 'german', 'немец', 'німець', 'němec', 'немка', 'німкеня'],
        
        // Slovakia
        'SK' => ['slovakia', 'словакия', 'словаччина', 'slovensko', 'slovak', 'словак', 'словачка', 'slovák', 'slovenka'],
        
        // Hungary
        'HU' => ['hungary', 'венгрия', 'угорщина', 'maďarsko', 'hungarian', 'венгр', 'угорець', 'maďar', 'венгерка', 'угорка'],
        
        // Romania
        'RO' => ['romania', 'румыния', 'румунія', 'rumunsko', 'romanian', 'румын', 'румун', 'rumun', 'румынка', 'румунка'],
        
        // Bulgaria
        'BG' => ['bulgaria', 'болгария', 'болгарія', 'bulharsko', 'bulgarian', 'болгарин', 'болгар', 'bulhar', 'болгарка'],
        
        // Moldova
        'MD' => ['moldova', 'молдова', 'молдавия', 'moldavsko', 'moldovan', 'молдаванин', 'молдаван', 'moldavan', 'молдаванка'],
        
        // Belarus
        'BY' => ['belarus', 'беларусь', 'білорусь', 'bělorusko', 'belarusian', 'белорус', 'білорус', 'bělorus', 'белоруска', 'білоруска'],
        
        // Lithuania
        'LT' => ['lithuania', 'литва', 'литва', 'litva', 'lithuanian', 'литовец', 'литовець', 'litevec', 'литовка'],
        
        // Latvia
        'LV' => ['latvia', 'латвия', 'латвія', 'lotyšsko', 'latvian', 'латыш', 'латиш', 'lotyš', 'латышка', 'латишка'],
        
        // Estonia
        'EE' => ['estonia', 'эстония', 'естонія', 'estonsko', 'estonian', 'эстонец', 'естонець', 'estonec', 'эстонка', 'естонка'],
        
        // Georgia
        'GE' => ['georgia', 'грузия', 'грузія', 'gruzie', 'georgian', 'грузин', 'грузин', 'gruzín', 'грузинка'],
        
        // Armenia
        'AM' => ['armenia', 'армения', 'вірменія', 'arménie', 'armenian', 'армянин', 'вірменин', 'armén', 'армянка', 'вірменка'],
        
        // Azerbaijan
        'AZ' => ['azerbaijan', 'азербайджан', 'азербайджан', 'ázerbájdžán', 'azerbaijani', 'азербайджанец', 'азербайджанець'],
        
        // Kazakhstan
        'KZ' => ['kazakhstan', 'казахстан', 'казахстан', 'kazachstán', 'kazakh', 'казах', 'казах', 'kazach', 'казашка'],
        
        // Uzbekistan
        'UZ' => ['uzbekistan', 'узбекистан', 'узбекистан', 'uzbekistán', 'uzbek', 'узбек', 'узбек', 'uzbek', 'узбечка'],
        
        // Tajikistan
        'TJ' => ['tajikistan', 'таджикистан', 'таджикистан', 'tádžikistán', 'tajik', 'таджик', 'таджик', 'tádžik', 'таджичка'],
        
        // Kyrgyzstan
        'KG' => ['kyrgyzstan', 'киргизия', 'киргизстан', 'kyrgyzstán', 'kyrgyz', 'киргиз', 'киргиз', 'kyrgyz', 'киргизка'],
        
        // Turkmenistan
        'TM' => ['turkmenistan', 'туркменистан', 'туркменістан', 'turkmenistán', 'turkmen', 'туркмен', 'туркмен'],
        
        // Turkey
        'TR' => ['turkey', 'турция', 'туреччина', 'turecko', 'turkish', 'турок', 'турок', 'turek', 'турчанка'],
        
        // United Kingdom
        'GB' => ['uk', 'united kingdom', 'великобритания', 'велика британія', 'velká británie', 'british', 'британец', 'британець', 'brit', 'england', 'англия', 'англія', 'anglie'],
        
        // USA
        'US' => ['usa', 'united states', 'сша', 'америка', 'spojené státy', 'american', 'американец', 'американець', 'američan'],
        
        // France
        'FR' => ['france', 'франция', 'франція', 'francie', 'french', 'француз', 'француз', 'francouz', 'француженка'],
        
        // Italy
        'IT' => ['italy', 'италия', 'італія', 'itálie', 'italian', 'итальянец', 'італієць', 'ital', 'итальянка', 'італійка'],
        
        // Spain
        'ES' => ['spain', 'испания', 'іспанія', 'španělsko', 'spanish', 'испанец', 'іспанець', 'španěl', 'испанка', 'іспанка'],
        
        // Portugal
        'PT' => ['portugal', 'португалия', 'португалія', 'portugalsko', 'portuguese', 'португалец', 'португалець', 'portugalec'],
        
        // Netherlands
        'NL' => ['netherlands', 'нидерланды', 'нідерланди', 'nizozemsko', 'dutch', 'голландец', 'голландець', 'holanďan', 'holland', 'голландия'],
        
        // Belgium
        'BE' => ['belgium', 'бельгия', 'бельгія', 'belgie', 'belgian', 'бельгиец', 'бельгієць', 'belgičan'],
        
        // Austria
        'AT' => ['austria', 'австрия', 'австрія', 'rakousko', 'austrian', 'австриец', 'австрієць', 'rakušan'],
        
        // Switzerland
        'CH' => ['switzerland', 'швейцария', 'швейцарія', 'švýcarsko', 'swiss', 'швейцарец', 'швейцарець', 'švýcar'],
        
        // Greece
        'GR' => ['greece', 'греция', 'греція', 'řecko', 'greek', 'грек', 'грек', 'řek', 'гречанка'],
        
        // Serbia
        'RS' => ['serbia', 'сербия', 'сербія', 'srbsko', 'serbian', 'серб', 'серб', 'srb', 'сербка'],
        
        // Croatia
        'HR' => ['croatia', 'хорватия', 'хорватія', 'chorvatsko', 'croatian', 'хорват', 'хорват', 'chorvat', 'хорватка'],
        
        // Slovenia
        'SI' => ['slovenia', 'словения', 'словенія', 'slovinsko', 'slovenian', 'словенец', 'словенець', 'slovinec'],
        
        // Bosnia
        'BA' => ['bosnia', 'босния', 'боснія', 'bosna', 'bosnian', 'босниец', 'боснієць', 'bosňák'],
        
        // Montenegro
        'ME' => ['montenegro', 'черногория', 'чорногорія', 'černá hora', 'montenegrin'],
        
        // North Macedonia
        'MK' => ['macedonia', 'македония', 'македонія', 'makedonie', 'macedonian', 'македонец', 'македонець'],
        
        // Albania
        'AL' => ['albania', 'албания', 'албанія', 'albánie', 'albanian', 'албанец', 'албанець', 'albánec'],
        
        // Kosovo
        'XK' => ['kosovo', 'косово', 'косово', 'kosovo'],
        
        // Vietnam
        'VN' => ['vietnam', 'вьетнам', 'в\'єтнам', 'vietnam', 'vietnamese', 'вьетнамец', 'в\'єтнамець'],
        
        // China
        'CN' => ['china', 'китай', 'китай', 'čína', 'chinese', 'китаец', 'китаєць', 'číňan'],
        
        // India
        'IN' => ['india', 'индия', 'індія', 'indie', 'indian', 'индиец', 'індієць', 'ind'],
        
        // Philippines
        'PH' => ['philippines', 'филиппины', 'філіппіни', 'filipíny', 'filipino', 'филиппинец', 'філіппінець'],
        
        // Indonesia
        'ID' => ['indonesia', 'индонезия', 'індонезія', 'indonésie', 'indonesian'],
        
        // Thailand
        'TH' => ['thailand', 'таиланд', 'таїланд', 'thajsko', 'thai', 'таец', 'таєць'],
        
        // Japan
        'JP' => ['japan', 'япония', 'японія', 'japonsko', 'japanese', 'японец', 'японець', 'japonec'],
        
        // South Korea
        'KR' => ['korea', 'корея', 'корея', 'korea', 'korean', 'кореец', 'кореєць', 'korejec', 'south korea'],
        
        // Mongolia
        'MN' => ['mongolia', 'монголия', 'монголія', 'mongolsko', 'mongolian', 'монгол', 'монгол'],
        
        // Nepal
        'NP' => ['nepal', 'непал', 'непал', 'nepál', 'nepalese', 'непалец', 'непалець'],
        
        // Bangladesh
        'BD' => ['bangladesh', 'бангладеш', 'бангладеш', 'bangladéš', 'bangladeshi'],
        
        // Pakistan
        'PK' => ['pakistan', 'пакистан', 'пакистан', 'pákistán', 'pakistani', 'пакистанец', 'пакистанець'],
        
        // Sri Lanka
        'LK' => ['sri lanka', 'шри-ланка', 'шрі-ланка', 'srí lanka'],
        
        // Egypt
        'EG' => ['egypt', 'египет', 'єгипет', 'egypt', 'egyptian', 'египтянин', 'єгиптянин'],
        
        // Morocco
        'MA' => ['morocco', 'марокко', 'марокко', 'maroko', 'moroccan'],
        
        // Tunisia
        'TN' => ['tunisia', 'тунис', 'туніс', 'tunisko', 'tunisian'],
        
        // Nigeria
        'NG' => ['nigeria', 'нигерия', 'нігерія', 'nigérie', 'nigerian'],
        
        // South Africa
        'ZA' => ['south africa', 'юар', 'південна африка', 'jihoafrická republika'],
        
        // Brazil
        'BR' => ['brazil', 'бразилия', 'бразилія', 'brazílie', 'brazilian', 'бразилец', 'бразилець'],
        
        // Argentina
        'AR' => ['argentina', 'аргентина', 'аргентина', 'argentina', 'argentinian'],
        
        // Mexico
        'MX' => ['mexico', 'мексика', 'мексика', 'mexiko', 'mexican', 'мексиканец', 'мексиканець'],
        
        // Canada
        'CA' => ['canada', 'канада', 'канада', 'kanada', 'canadian', 'канадец', 'канадець'],
        
        // Australia
        'AU' => ['australia', 'австралия', 'австралія', 'austrálie', 'australian', 'австралиец', 'австралієць'],
        
        // New Zealand
        'NZ' => ['new zealand', 'новая зеландия', 'нова зеландія', 'nový zéland'],
        
        // Israel
        'IL' => ['israel', 'израиль', 'ізраїль', 'izrael', 'israeli', 'израильтянин', 'ізраїльтянин'],
        
        // Iran
        'IR' => ['iran', 'иран', 'іран', 'írán', 'iranian', 'иранец', 'іранець'],
        
        // Iraq
        'IQ' => ['iraq', 'ирак', 'ірак', 'irák', 'iraqi'],
        
        // Syria
        'SY' => ['syria', 'сирия', 'сирія', 'sýrie', 'syrian', 'сириец', 'сирієць'],
        
        // Afghanistan
        'AF' => ['afghanistan', 'афганистан', 'афганістан', 'afghánistán', 'afghan', 'афганец', 'афганець'],
        
        // Cuba
        'CU' => ['cuba', 'куба', 'куба', 'kuba', 'cuban', 'кубинец', 'кубинець'],
        
        // Finland
        'FI' => ['finland', 'финляндия', 'фінляндія', 'finsko', 'finnish', 'финн', 'фін', 'fin'],
        
        // Sweden
        'SE' => ['sweden', 'швеция', 'швеція', 'švédsko', 'swedish', 'швед', 'швед', 'švéd'],
        
        // Norway
        'NO' => ['norway', 'норвегия', 'норвегія', 'norsko', 'norwegian', 'норвежец', 'норвежець', 'nor'],
        
        // Denmark
        'DK' => ['denmark', 'дания', 'данія', 'dánsko', 'danish', 'датчанин', 'датчанин', 'dán'],
        
        // Iceland
        'IS' => ['iceland', 'исландия', 'ісландія', 'island', 'icelandic', 'исландец', 'ісландець'],
        
        // Ireland
        'IE' => ['ireland', 'ирландия', 'ірландія', 'irsko', 'irish', 'ирландец', 'ірландець', 'ir'],
    ];

    /**
     * Get country code by nationality string
     */
    public static function getCountryCode(string $nationality): ?string
    

    {
        $normalizedNationality = mb_strtolower(trim($nationality));
        
        foreach (self::$nationalityMap as $countryCode => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalizedNationality, $keyword) || str_contains($keyword, $normalizedNationality)) {
                    return $countryCode;
                }
            }
        }
        
        return null;
    }

    /**
     * Get flag emoji by country code
     */
    public static function getFlagEmoji(string $countryCode): string
    {
        $countryCode = strtoupper($countryCode);
        
        // Convert country code to regional indicator symbols (flag emoji)
        $flag = '';
        for ($i = 0; $i < strlen($countryCode); $i++) {
            $flag .= mb_chr(ord($countryCode[$i]) - ord('A') + 0x1F1E6);
        }
        
        return $flag;
    }

    /**
     * Get flag image URL by country code
     * Uses flagcdn.com for SVG flags (works in all browsers including Chrome on Windows)
     */
    public static function getFlagImageUrl(string $countryCode): string
    {
        $countryCode = strtolower($countryCode);
        
        // Use flagcdn.com for SVG flags - works everywhere
        return "https://flagcdn.com/{$countryCode}.svg";
    }

    /**
     * Get flag HTML for nationality
     */
    public static function getFlagHtml(string $nationality, int $size = 20): string
    {
        $countryCode = self::getCountryCode($nationality);
        
        if (!$countryCode) {
            return '';
        }
        
        $flagUrl = self::getFlagImageUrl($countryCode);
        $height = round($size * 0.75); // Flags are typically 4:3 ratio
        
        return '<img src="' . $flagUrl . '" alt="' . strtoupper($countryCode) . '" style="width: ' . $size . 'px; height: ' . $height . 'px; object-fit: cover; vertical-align: middle; margin-right: 5px; border-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);" loading="lazy">';
    }
}
