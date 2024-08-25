<?php

use Beste\Cache\InMemoryCache;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Money\Converter;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Zrnik\Exchange\CnbExchange;
use Zrnik\Exchange\ExchangeRateProvider;
use \Psr\Cache\InvalidArgumentException;

class ExchangeRateProviderTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     */
    public function testGetExchangeRate(): void
    {
        $exchangeRateProvider = new ExchangeRateProvider(
            new InMemoryCache(null),
            new HttpFactory(),
            new Client(),
        );

        $rates = $exchangeRateProvider->getExchangeRates(
            new DateTime('2020-01-01')
        );

        $exampleRates = [
            'CZK' => $rates['CZK'],
            'USD' => $rates['USD'],
            'EUR' => $rates['EUR'],
        ];

        $this->assertSame(
            [
                'CZK' => [1, 1],
                'USD' => [1, 22.621],
                'EUR' => [1, 25.41],
            ],
            $exampleRates,
        );

        $USD_345 = new Money(34500, new Currency("USD"));

        $converter = new Converter(
            new ISOCurrencies(),
            new CnbExchange(
                $exchangeRateProvider,
                (new DateTime())->setTimestamp(
                    (int)mktime(
                        12, 0, 0,
                        8, 22, 2016
                    )
                )
            )
        );

        $this->assertEquals(
            26313,
            $converter->convert($USD_345, new Currency('GBP'))->getAmount()
        );

        $ratio = $exchangeRateProvider->currencyRatioBetween(
            (new DateTime())->setTimestamp(
                (int)mktime(
                    12, 0, 0,
                    1, 1, 2010
                )
            ),
            new Currency("EUR"),
            new Currency("CZK"),
        );

        $this->assertSame($ratio, 26.465);
    }
}