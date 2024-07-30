<?php

namespace Zrnik\Exchange;

use DateTime;
use Exception;
use Money\Currency;
use Money\CurrencyPair;
use Money\Exchange;
use Psr\Http\Client\ClientExceptionInterface;

class CnbExchange implements Exchange
{
    public function __construct(
        private ExchangeRateProvider $exchangeRateProvider,
        private ?DateTime            $dateTime = null,
    )
    {
    }

    /**
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency): CurrencyPair
    {
        return new CurrencyPair(
            $baseCurrency,
            $counterCurrency,
            (string)$this->exchangeRateProvider->currencyRatioBetween(
                $this->dateTime ?? new DateTime(),
                $baseCurrency,
                $counterCurrency,
            )
        );
    }
}
