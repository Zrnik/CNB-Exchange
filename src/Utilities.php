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

    const SupportedCurrencies = [
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


    /**
     * @param Money $money
     * @return string
     */
    public static function format(Money $money)
    {
        $currencies = new ISOCurrencies();

        if (!$currencies->contains($money->getCurrency()))
            throw new LogicException(
                "Currency '" . $money->getCurrency()->getCode() . "' is invalid!"
            );

        if (!isset(self::SupportedCurrencies[$money->getCurrency()->getCode()]))
            throw new LogicException(
                "Currency '" . $money->getCurrency()->getCode() . "' not supported!"
            );

        $numberFormatter = new NumberFormatter(self::SupportedCurrencies[$money->getCurrency()->getCode()], NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);
        return $moneyFormatter->format($money);
    }

    public static function formatFloat(
        float $floatAmount, Currency $currency
    ): string
    {
        // This will give us: 1.222,30 or 1,222.30 or 1 222.30 or 1 222,30 or whatever :D
        $correctFormat = self::format(new Money(122230, $currency));

        [$firstPart, $secondPart] = explode("222", $correctFormat);

        [$trash, $thousandsSeparator] = explode("1", $firstPart);
        unset($trash);

        [$decimalSeparator, $trash] = explode("30", $secondPart);
        unset($trash);

        $integerPart = floor($floatAmount);
        $decimalPart = $floatAmount - $integerPart;

        $decimalLength = strlen(strval($decimalPart)) - 2; // -2 = "0." in double representation

        $formattedExample = number_format(
            1222.3, 2, $decimalSeparator, $thousandsSeparator
        );
        $formattedNumber = number_format(
            $floatAmount, $decimalLength, $decimalSeparator, $thousandsSeparator
        );


        $result = str_replace($formattedExample, $formattedNumber, $correctFormat);

        /*
            var_dump([
                "original" => $floatAmount,
                "integer_part" => $integerPart,
                "decimal_part" => $decimalPart,
                "decimal_len" => $decimalLength,
                "formatted_example" => $formattedExample,
                "formatted" => $formattedNumber,
                "result" => $result,
                "thousands_separator" => $thousandsSeparator,
                "decimal_separator" => $decimalSeparator
            ]);
        */

        return $result;
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
        if ($When === null)
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
        if ($When === null)
            $When = time();

        return new Converter(new ISOCurrencies(), new CnbExchange($When));
    }


}
