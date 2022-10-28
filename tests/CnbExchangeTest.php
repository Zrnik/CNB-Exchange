<?php

namespace Tests;

use DateTimeImmutable;
use Http\Adapter\Guzzle7\Client;
use Http\Factory\Guzzle\RequestFactory;
use Money\Converter;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Zrnik\Exchange\CnbExchange;
use Zrnik\Exchange\ExchangeRates\ExchangeRatesRepository;

class CnbExchangeTest extends TestCase
{
    private CnbExchange $exchange;

    private Converter $converter;

    private ExchangeRatesRepository $exchangeRatesRepository;

    protected function setUp(): void
    {
        $devNullCache = new DevNullCache();
        $client = Client::createWithConfig([]);
        $requestFactory = new RequestFactory();

        $this->exchangeRatesRepository = new ExchangeRatesRepository(
            $devNullCache, $client, $requestFactory
        );

        $this->exchange = new CnbExchange($this->exchangeRatesRepository);
        $this->converter = new Converter(new ISOCurrencies(), $this->exchange);
    }

    /**
     * @return void
     */
    public function testExchange(): void
    {
        $this->exchange->setDate(
            (new DateTimeImmutable())->setDate(2016, 8, 22)
        );

        $USD_345 = new Money(34500, new Currency("USD"));

        $GBP_263_13 = $this->converter->convert($USD_345, new Currency("GBP"));

        $this->assertEquals(
            26313,
            $GBP_263_13->getAmount()
        );

        $PHP_100_00 = new Money(10000, new Currency("PHP"));

        $CZK_51_33 = $this->converter->convert($PHP_100_00, new Currency("CZK"));

        $this->assertEquals(
            5133,
            $CZK_51_33->getAmount()
        );
    }

    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     */
    public function testPreciseExchangeRatio(): void
    {
        $dateTimeImmutable = (new DateTimeImmutable())->setDate(2010, 1, 1);

        $ratio = $this->exchangeRatesRepository
            ->getExchangeRates($dateTimeImmutable)
            ->getRatioBetweenCurrencies(
                new Currency("EUR"),
                new Currency("CZK")
            );

        $this->assertSame(
            $ratio, '26.465'
        );
    }
}
