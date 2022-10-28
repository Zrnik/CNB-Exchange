<?php

declare(strict_types=1);

namespace Zrnik\Exchange;

use DateTimeImmutable;
use Money\Currency;
use Money\CurrencyPair;
use Money\Exchange;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Zrnik\Exchange\ExchangeRates\ExchangeRatesRepository;

class CnbExchange implements Exchange
{
    private ExchangeRatesRepository $exchangeRatesRepository;
    private DateTimeImmutable $exchangeDate;

    public function __construct(
        ExchangeRatesRepository $exchangeRatesRepository
    )
    {
        $this->exchangeRatesRepository = $exchangeRatesRepository;
        $this->exchangeDate = new DateTimeImmutable();
    }

    /**
     * @param DateTimeImmutable $dateTimeImmutable
     * @return void
     */
    public function setDate(DateTimeImmutable $dateTimeImmutable): void
    {
        $this->exchangeDate = $dateTimeImmutable;
    }

    /**
     * @param Currency $baseCurrency
     * @param Currency $counterCurrency
     * @return CurrencyPair
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency): CurrencyPair
    {
        $ratios = $this->exchangeRatesRepository->getExchangeRates(
            $this->exchangeDate
        );

        return new CurrencyPair(
            $baseCurrency,
            $counterCurrency,
            (float)$ratios->getRatioBetweenCurrencies(
                $baseCurrency,
                $counterCurrency,
            ),
        );
    }
}
