<?php declare(strict_types=1);
/*
 * Zrník.eu | AgronaroWebsite  
 * User: Programátor
 * Date: 02.10.2020 10:47
 */


namespace Zrnik\Money;


use LogicException;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;

class Money
{
    /**
     * @var \Money\Money
     */
    private \Money\Money $money;

    public function __construct($amount, Currency $currency)
    {
        $this->money = new \Money\Money($amount, $currency);
    }

    //region Operations

    public function add(Money ...$addends): Money
    {
        $money = $this->money;
        foreach ($addends as $addend)
            $money = $money->add($addend->money);

        return new Money(
            $money->getAmount(),
            $money->getCurrency()
        );
    }

    public function subtract(Money ...$subtrahends): Money
    {
        $money = $this->money;
        foreach ($subtrahends as $subtrahend)
            $money = $money->subtract($subtrahend->money);

        return new Money(
            $money->getAmount(),
            $money->getCurrency()
        );
    }

    public function multiply($multiplier, $roundingMode = \Money\Money::ROUND_HALF_UP): Money
    {
        $money = $this->money->multiply($multiplier, $roundingMode);

        return new Money(
            $money->getAmount(),
            $money->getCurrency()
        );
    }

    public function divide($divisor, $roundingMode = \Money\Money::ROUND_HALF_UP): Money
    {
        $money = $this->money->divide($divisor, $roundingMode);
        return new Money(
            $money->getAmount(),
            $money->getCurrency()
        );
    }

    //endregion

    //region Base Getters

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->money->getAmount();
    }

    /**
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->money->getCurrency();
    }

    /**
     * @return \Money\Money
     */
    public function getMoney(): \Money\Money
    {
        return $this->money;
    }

    //endregion

    //region Compare

    public function isSameCurrency(Money $other): bool
    {
        return $this->money->getCurrency()->equals($other->getCurrency());
    }

    public function equals(Money $other): bool
    {
        return $this->money->equals($other->money);
    }

    public function compare(Money $other): int
    {
        return $this->money->compare($other->money);
    }

    public function greaterThan(Money $other): bool
    {
        return $this->compare($other) > 0;
    }

    public function greaterThanOrEqual(Money $other): bool
    {
        return $this->compare($other) >= 0;
    }

    public function lessThan(Money $other): bool
    {
        return $this->compare($other) < 0;
    }

    public function lessThanOrEqual(Money $other): bool
    {
        return $this->compare($other) <= 0;
    }

    //endregion

    //region ToString
    public function __toString(): string
    {
        return $this->toString();
    }


    public function toString(): string
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
        if (!$currencies->contains($this->getCurrency()))
            throw new LogicException(
                "Currency '" . $this->getCurrency()->getCode() . "' is invalid!"
            );

        if (!isset($Formats[$this->getCurrency()->getCode()]))
            throw new LogicException(
                "Currency '" . $this->getCurrency()->getCode() . "' not supported!"
            );


        $numberFormatter = new \NumberFormatter($Formats[$this->getCurrency()->getCode()], \NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);
        return $moneyFormatter->format($this->getMoney());
    }
    //endregion

    //region ČNB Kurzovní Lístek

    public function convertTo(Currency $currency, ?int $When = null): Money
    {
        if ($When === null)
            $When = time();

        $exchangeRates = $this->getExchangeRates($When);

        $ConvertRatio =
            (
                $exchangeRates[$currency->getCode()][0]
                /
                $exchangeRates[$this->getCurrency()->getCode()][0]
            )
            *
            (
                $exchangeRates[$this->getCurrency()->getCode()][1]
                /
                $exchangeRates[$currency->getCode()][1]
            );

        $NewMoney = new Money(
            $this->getAmount(),
            $currency
        );

        $NewMoney = $NewMoney->multiply($ConvertRatio);

        return $NewMoney;
    }

    //region System: Get/Load Data

    private function getExchangeRates(int $When): array
    {
        $CnbResult = $this->cnbData($When);

        $Lines = explode("\n", $CnbResult);

        $skip = 2;
        $Ratios = [];
        $Ratios["CZK"] = [1, 1];
        foreach ($Lines as $Line) {
            if ($skip > 0) {
                $skip--;
                continue;
            }

            if (trim($Line) === "")
                continue;

            $Parts = explode("|", $Line);

            $Amount = intval($Parts[2]);
            $Short = $Parts[3];
            $Price = round(floatval(str_replace(",", ".", $Parts[4])), 4);
            $Ratios[$Short] = [$Amount, $Price];
        }
        return $Ratios;
    }

    private static ?Cache $cache = null;

    private function cnbData(int $When)
    {
        if (static::$cache === null) {
            //Next to "src" directory.
            $storageLocation = __DIR__ . '/../temp/';

            //Make sure it exists
            if (!file_exists($storageLocation))
                mkdir($storageLocation, 0777, true);

            static::$cache = new Cache(new FileStorage($storageLocation), "zrnik.money");
        }

        $key = $this->convertDate($When);
        return static::$cache->load($key, function () use ($key) {
            return $this->pullData($key);
        });
    }


    private function pullData(string $key): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->cnbUrl($key));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private function cnbUrl(string $key): string
    {
        return "https://www.cnb.cz/cs/financni-trhy/devizovy-trh"
            . "/kurzy-devizoveho-trhu/kurzy-devizoveho-trhu/"
            . "denni_kurz.txt?date=" . $key;
    }

    private function convertDate(int $When): string
    {
        $MaxWhen = strtotime("-1 day");
        $When = min($When, $MaxWhen);
        return date("d.m.Y", $When);
    }
    //endregion


    /*





    private function pullData(int $When): string
    {
        $Client = new Client(new ResponseFactory(), new StreamFactory());
        $Response = $Client->sendRequest(new Request("GET", $this->cnbUrl($When)));
        $Body = $Response->getBody();
        return $Body->read($Body->getSize());
    }*/


    //endregion


}