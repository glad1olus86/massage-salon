<?php

use App\Helpers\CashboxCurrencyHelper;

if (!function_exists('formatCashboxCurrency')) {
    /**
     * Format amount with cashbox currency
     * Requirement 11.2: Format currency with proper symbols and positions
     *
     * @param float|int $amount The amount to format
     * @param string|null $currency Currency code (EUR, USD, PLN, CZK). If null, uses company setting.
     * @return string Formatted amount with currency symbol
     */
    function formatCashboxCurrency($amount, ?string $currency = null): string
    {
        return CashboxCurrencyHelper::format($amount, $currency);
    }
}

if (!function_exists('getCashboxCurrencySymbol')) {
    /**
     * Get cashbox currency symbol
     *
     * @param string|null $currency Currency code. If null, uses company setting.
     * @return string Currency symbol
     */
    function getCashboxCurrencySymbol(?string $currency = null): string
    {
        return CashboxCurrencyHelper::getSymbol($currency);
    }
}
