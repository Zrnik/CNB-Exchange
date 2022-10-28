<?php

declare(strict_types=1);

namespace Zrnik\Exchange\ExchangeRates;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Money\Currency;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class ExchangeRatesRepository
{
    private const CNB_BASE_URL = 'https://www.cnb.cz/cs/financni-trhy/devizovy-trh/kurzy-devizoveho-trhu/kurzy-devizoveho-trhu/denni_kurz.txt?date=';

    private CacheInterface $cache;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;

    public function __construct(
        CacheInterface          $cache,
        ClientInterface         $client,
        RequestFactoryInterface $requestFactory

    )
    {
        $this->cache = $cache;
        $this->client = $client;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     */
    public function getExchangeRates(DateTimeImmutable $date): ExchangeRates
    {
        $key = $this->createKey($date);

        if ($this->cache->has($key)) {
            /** @var ExchangeRates */
            return $this->cache->get($key);
        }

        $exchangeRates = $this->fetch($date);
        /** @noinspection PhpExpressionResultUnusedInspection */
        $this->cache->set($key, $exchangeRates);
        return $exchangeRates;
    }


    /**
     * @throws ClientExceptionInterface
     */
    private function fetch(DateTimeImmutable $date): ExchangeRates
    {
        $request = $this->requestFactory->createRequest(
            'GET',
            sprintf(
                '%s%s',
                self::CNB_BASE_URL,
                $date->format('d.m.Y')
            )
        );

        $response = $this->client->sendRequest($request);

        return ExchangeRates::fromResponse(
            $response
        );
    }

    /**
     * @throws Exception
     */
    private function createKey(DateTimeImmutable $date): string
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'));
        $date = $date->setTimezone(new DateTimeZone('Europe/Prague'));

        if($date > $now) {
            // Sorry, im not clairvoyant...
            $date = $now;
        }

        if($date->format('Y-m-d') === $now->format('Y-m-d'))
        {
            // CNB ratios are published every day at 14:30 CET,
            // that means, that until then, we are creating
            // cache key with postfix to allow autocorrection
            // after 14:30

            $hour = (int) $date->format('H');
            $minutes = (int) $date->format('i');

            if($hour <= 13 || ($hour === 14 && $minutes <= 30)) {
                return $now->format('Y-m-d') . '-before-14-30';
            }
        }

        return $now->format('Y-m-d');
    }
}
