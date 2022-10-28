<?php

declare(strict_types=1);

namespace Zrnik\Exchange\ExchangeRates;

use Money\Currency;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ExchangeRates
{
    /**
     * @var ExchangeRate[]
     */
    private array $exchangeRateByCurrencyCode = [];

    public static function fromResponse(ResponseInterface $response): ExchangeRates
    {
        $exchangeRates = new self();

        $responseText = (string)$response->getBody();

        $lines = explode("\n", $responseText);
        $lastIndex = count($lines) - 1;
        unset($lines[0] /* date */, $lines[1] /* header */, $lines[$lastIndex], /* last empty line */);

        $exchangeRates->addExchangeRate(
            ExchangeRate::czk()
        );

        foreach ($lines as $line) {
            $exchangeRates->addExchangeRate(
                ExchangeRate::parse($line)
            );
        }

        return $exchangeRates;
    }

    private function addExchangeRate(ExchangeRate $exchangeRate): void
    {
        $this->exchangeRateByCurrencyCode[$exchangeRate->getCurrencyCode()] = $exchangeRate;
    }

    private function getExchangeRateOfCurrency(Currency $currency): ExchangeRate
    {
        if (!array_key_exists($currency->getCode(), $this->exchangeRateByCurrencyCode)) {
            throw new RuntimeException(
                sprintf(
                    'Currency "%s" is not supported by CNB',
                    $currency->getCode()
                )
            );
        }

        return $this->exchangeRateByCurrencyCode[$currency->getCode()];
    }

    public function getRatioBetweenCurrencies(Currency $currency1, Currency $currency2): string
    {
        $exchangeRate1 = $this->getExchangeRateOfCurrency($currency1);
        $exchangeRate2 = $this->getExchangeRateOfCurrency($currency2);

        /** @var int|float $result */
        $result =
            (
                $exchangeRate1->getPrice()
                /
                $exchangeRate2->getPrice()
            )
            *
            (
                $exchangeRate1->getCzkAmountForPrice()
                /
                $exchangeRate2->getCzkAmountForPrice()
            );

        return (string)$result;
    }


}
