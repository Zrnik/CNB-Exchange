<?php

declare(strict_types=1);

namespace Zrnik\Exchange\ExchangeRates;

class ExchangeRate
{
    private string $currencyCode;
    private float $czkAmountForPrice;
    private float $price;

    public function __construct(
        string $currencyCode,
        float  $czkAmountForPrice,
        float  $price
    )
    {
        $this->currencyCode = $currencyCode;
        $this->czkAmountForPrice = $czkAmountForPrice;
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * @return float
     */
    public function getCzkAmountForPrice(): float
    {
        return $this->czkAmountForPrice;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }


    public static function parse(string $line): ExchangeRate
    {
        [$countryName, $currencyName, $czkAmountForPrice, $currencyCode, $unparsedPrice] = explode('|', $line);
        $price = (float)str_replace(',', '.', $unparsedPrice);
        return new ExchangeRate($currencyCode, (float)$czkAmountForPrice, $price);
    }

    public static function czk(): ExchangeRate
    {
        return new ExchangeRate('CZK', 1, 1);
    }
}