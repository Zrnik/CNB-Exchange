<?php

namespace Zrnik\Exchange;

use Exception;
use Money\Currency;
use Money\CurrencyPair;
use Money\Exception\UnresolvableCurrencyPairException;
use Money\Exchange;
use RuntimeException;

class CnbExchange implements Exchange
{
    /**
     * Temp directory, defaults to 'Next to `src` directory'.
     * @var string
     */
    public static string $tempDir = __DIR__ . '/../temp/';

    /**
     * @var int
     */
    private int $When;

    /**
     * CnbExchange constructor.
     * @param int|null $When
     */
    public function __construct(?int $When = null)
    {
        if ($When === null) {
            $When = time();
        }

        $this->When = (int) $When;
    }

    /**
     * @param Currency $baseCurrency
     * @param Currency $counterCurrency
     * @return CurrencyPair
     * @throws Exception
     */
    public function quote(Currency $baseCurrency, Currency $counterCurrency): CurrencyPair
    {
        return new CurrencyPair(
            $baseCurrency,
            $counterCurrency,
            self::currencyRatioBetween(
                $this->When,
                $baseCurrency,
                $counterCurrency
            )
        );
    }

    /**
     * @param int $When
     * @param Currency $baseCurrency
     * @param Currency $counterCurrency
     * @return numeric-string
     * @throws Exception
     */
    public static function currencyRatioBetween(int $When, Currency $baseCurrency, Currency $counterCurrency): string
    {
        $exchangeRates = self::getExchangeRates($When);


        //Do our currencies exist in CNB results?

        if (!isset($exchangeRates[$baseCurrency->getCode()])) {
            throw new UnresolvableCurrencyPairException(
                "Currency '" . $baseCurrency->getCode() . "' is not defined in CNB exchange rates file!"
            );
        }

        if (!isset($exchangeRates[$counterCurrency->getCode()])) {
            throw new UnresolvableCurrencyPairException(
                "Currency '" . $counterCurrency->getCode() . "' is not defined in CNB exchange rates file!"
            );
        }

        // I'm afraid we need to use...MATH
        // https://www.youtube.com/watch?v=gENVB6tjq_M

        //TODO: make this piece of sh..code more readable...

        /** @var int|float $result */
        $result =
            (
                $exchangeRates[$baseCurrency->getCode()][1]
                /
                $exchangeRates[$counterCurrency->getCode()][1]
            )
            *
            (
                $exchangeRates[$counterCurrency->getCode()][0]
                /
                $exchangeRates[$baseCurrency->getCode()][0]
            );

        return (string) $result;
    }

    /**
     * @param int $When
     * @return string
     */
    private static function getCnbDateKey(int $When): string
    {
        // CNB exchange rates are released every working day after 14:30
        // (GMT +2)
        //
        // We will load date from '$When' parameter, but
        // if '$When' is today, or in the future,
        // we will fallback to yesterday.

        $MaxWhen = strtotime("-1 day");
        $When = min($When, $MaxWhen);
        return date("d.m.Y", $When);

    }

    /**
     * @var array<string,array<string, array<int,float>>>
     */
    private static array $ratiosMemoryCache = [];

    /**
     * @param int $When
     * @return array<string, array<int,float>>
     * @throws Exception
     */
    private static function getExchangeRates(int $When): array
    {
        $cnbDateKey = self::getCnbDateKey($When);

        if (isset(self::$ratiosMemoryCache[$cnbDateKey])) {
            return self::$ratiosMemoryCache[$cnbDateKey];
        }

        //Not calculated? How about FileCache?

        $fileCacheDirectory = self::$tempDir . "/cache/cnb.exchange.ratios/";

        if (!file_exists($fileCacheDirectory) && !mkdir($fileCacheDirectory, 0777, true) && !is_dir($fileCacheDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $fileCacheDirectory));
        }

        // $fileCacheDirectory ends with slash already.
        // "spa" => serialized php array :)
        $keyFile = $fileCacheDirectory . $cnbDateKey . ".spa";

        if (file_exists($keyFile)) {
            // Oh, hey! We have them in the FileCache.
            // Once set, the ratios are not changing, so
            // we don't need any invalidation mechanism!

            $content = @file_get_contents($keyFile);

            if ($content === false) {
                throw new RuntimeException("Unable to read cache file!");
            }

            /**
             * @var array<string, array<int, float>> |false $decoded
             * @noinspection UnserializeExploitsInspection
             */
            $decoded = @unserialize($content);

            if ($decoded === false) {
                throw new RuntimeException("Corrupted cache file!");
            }

            // Save it to memory, so we don't need to
            // reach for file again for this case
            // (and unserialize again)

            self::$ratiosMemoryCache[$cnbDateKey] = $decoded;

            return $decoded;
        }

        // Oh, we don't even have it in the FileCache?

        // Damn, we have to ask for it OUR NATIONAL BANK
        // (why do i hear russian anthem? oh, its the neighbor...)

        $ratios = self::loadAndParse($cnbDateKey); //load and parse!

        // Now, that we have the ratios from CNB, we will
        // save it to our '$keyFile' and to memory for future
        // usage in this runtime.

        file_put_contents($keyFile, serialize($ratios));
        self::$ratiosMemoryCache[$cnbDateKey] = $ratios;

        return $ratios;
    }

    /**
     * @param string $key
     * @return array<string, array<int,float>>
     * @throws Exception
     */
    public static function loadAndParse(string $key): array
    {
        $url = "https://www.cnb.cz/cs/financni-trhy/devizovy-trh"
            . "/kurzy-devizoveho-trhu/kurzy-devizoveho-trhu/"
            . "denni_kurz.txt?date=" . $key;

        //TODO: create interface for downloading
        // mechanism and let user define method
        // themself!

        //For now, we are using CURL to download data:

        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandler, CURLOPT_URL, $url);
        $CnbResult = curl_exec($curlHandler);

        if (is_bool($CnbResult)) {
            throw new RuntimeException("'Curl' failed with error #" . curl_errno($curlHandler) . " '" . curl_error($curlHandler) . "'");
        }

        curl_close($curlHandler);

        //Then we parse the data:

        $Lines = explode("\n", $CnbResult);

        $skip = 2;
        $Ratios = [];
        $Ratios["CZK"] = [1, 1];
        foreach ($Lines as $Line) {
            if ($skip > 0) {
                $skip--;
                continue;
            }

            if (trim($Line) === "") {
                continue;
            }

            $Parts = explode("|", $Line);

            $Amount = (int)$Parts[2];
            $Short = $Parts[3];
            $Price = round((float)str_replace(",", ".", $Parts[4]), 4);
            $Ratios[$Short] = [$Amount, $Price];
        }

        return $Ratios;
    }
}
