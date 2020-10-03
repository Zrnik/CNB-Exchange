<?php

namespace Zrnik\Exchange;

use LogicException;
use Money\Converter;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;

class Utilities
{
    /**
     * @param Money $money
     * @return string
     */
    public static function format(Money $money)
    {
        $Formats = [
            "CZK" => "cs_CZ",
            "AUD" => "en_AU",
            "BRL" => "pt_BR",
            "BGN" => "bg_BG",
            "CNY" => "bo_CN",
            "DKK" => "da_DK",
            "EUR" => "de_DE",
            "PHP" => "en_PH",
            "HKD" => "en_HK",
            "HRK" => "hr_HR",
            "INR" => "ar_IN",
            "IDR" => "id_ID",
            "ISK" => "is_IS",
            "ILS" => "en_IL",
            "JPY" => "ja_JP",
            "ZAR" => "af_ZA",
            "CAD" => "en_CA",
            "KRW" => "ko_KR",
            "HUF" => "hu_HU",
            "MYR" => "ms_MY",
            "MXN" => "es_MX",
            "NOK" => "nb_NO",
            "NZD" => "en_NZ",
            "PLN" => "pl_PL",
            "RON" => "ro_RO",
            "RUB" => "ru_RU",
            "SGD" => "zh_SG",
            "SEK" => "sv_SE",
            "CHF" => "fr_CH",
            "THB" => "th_TH",
            "TRY" => "tr_TR",
            "USD" => "en_US",
            "GBP" => "en_GB",
        ];

        $currencies = new ISOCurrencies();

        if (!$currencies->contains($money->getCurrency()))
            throw new LogicException(
                "Currency '" . $money->getCurrency()->getCode() . "' is invalid!"
            );

        if (!isset($Formats[$money->getCurrency()->getCode()]))
            throw new LogicException(
                "Currency '" . $money->getCurrency()->getCode() . "' not supported!"
            );

        $numberFormatter = new NumberFormatter($Formats[$money->getCurrency()->getCode()], NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);
        return $moneyFormatter->format($money);
    }

    /**
     * @param Money $money
     * @param Currency $counterCurrency
     * @param int|null $When
     * @return Money
     */
    public static function convert(
        Money $money,
        Currency $counterCurrency,
        ?int $When = null
    ): Money
    {
        if($When === null)
            $When = time();

        $converter = static::createConverter($When);

        return $converter->convert($money, $counterCurrency);
    }

    /**
     * @param int|null $When
     * @return Converter
     */
    public static function createConverter(?int $When = null): Converter
    {
        if($When === null)
            $When = time();

        return new Converter(new ISOCurrencies(), new CnbExchange($When));
    }

}
