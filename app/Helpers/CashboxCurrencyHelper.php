<?php

namespace App\Helpers;

use App\Models\Utility;

/**
 * Helper class for formatting cashbox currency
 * Requirement 11.1: Support EUR, USD, PLN, CZK currencies
 * Requirement 11.2: Format amounts with proper symbols and positions
 */
class CashboxCurrencyHelper
{
    /**
     * Currency configuration
     * Each currency has: symbol, position (before/after), decimals, thousand_separator, decimal_separator
     */
    public const CURRENCIES = [
        'EUR' => [
            'symbol' => '€',
            'position' => 'after',
            'decimals' => 2,
            'thousand_separator' => ' ',
            'decimal_separator' => ',',
            'name' => 'Euro',
        ],
        'USD' => [
            'symbol' => '$',
            'position' => 'before',
            'decimals' => 2,
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'name' => 'US Dollar',
        ],
        'PLN' => [
            'symbol' => 'zł',
            'position' => 'after',
            'decimals' => 2,
            'thousand_separator' => ' ',
            'decimal_separator' => ',',
            'name' => 'Polish Zloty',
        ],
        'CZK' => [
            'symbol' => 'Kč',
            'position' => 'after',
            'decimals' => 2,
            'thousand_separator' => ' ',
            'decimal_separator' => ',',
            'name' => 'Czech Koruna',
        ],
    ];

    /**
     * Format amount with currency symbol
     *
     * @param float|int $amount The amount to format
     * @param string|null $currency Currency code (EUR, USD, PLN, CZK). If null, uses company setting.
     * @return string Formatted amount with currency symbol
     */
    public static function format($amount, ?string $currency = null): string
    {
        if ($currency === null) {
            $settings = Utility::settings();
            $currency = $settings['cashbox_currency'] ?? 'EUR';
        }

        $config = self::CURRENCIES[$currency] ?? self::CURRENCIES['EUR'];

        $formattedNumber = number_format(
            (float) $amount,
            $config['decimals'],
            $config['decimal_separator'],
            $config['thousand_separator']
        );

        if ($config['position'] === 'before') {
            return $config['symbol'] . $formattedNumber;
        }

        return $formattedNumber . ' ' . $config['symbol'];
    }

    /**
     * Get currency symbol
     *
     * @param string|null $currency Currency code. If null, uses company setting.
     * @return string Currency symbol
     */
    public static function getSymbol(?string $currency = null): string
    {
        if ($currency === null) {
            $settings = Utility::settings();
            $currency = $settings['cashbox_currency'] ?? 'EUR';
        }

        return self::CURRENCIES[$currency]['symbol'] ?? '€';
    }

    /**
     * Get all available currencies for select dropdown
     *
     * @return array Array of currency code => display name
     */
    public static function getAvailableCurrencies(): array
    {
        $currencies = [];
        foreach (self::CURRENCIES as $code => $config) {
            $currencies[$code] = $config['symbol'] . ' - ' . $config['name'] . ' (' . $code . ')';
        }
        return $currencies;
    }

    /**
     * Get current company's cashbox currency
     *
     * @return string Currency code
     */
    public static function getCurrentCurrency(): string
    {
        $settings = Utility::settings();
        return $settings['cashbox_currency'] ?? 'EUR';
    }

    /**
     * Check if currency code is valid
     *
     * @param string $currency Currency code to validate
     * @return bool
     */
    public static function isValidCurrency(string $currency): bool
    {
        return array_key_exists($currency, self::CURRENCIES);
    }
}
