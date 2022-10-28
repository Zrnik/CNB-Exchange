# ČNB Exchange

![GitHub](https://img.shields.io/github/license/zrnik/CNB-Exchange)
![Packagist Downloads](https://img.shields.io/packagist/dm/zrnik/cnb-exchange)
![Github Action](https://img.shields.io/github/workflow/status/zrnik/cnb-exchange/tests/master)
![Packagist Version](https://img.shields.io/packagist/v/zrnik/cnb-exchange)  
 
 
**ČNB** = **Česká Národní Banka** (*czech national bank*)

This is a class implementing `\Money\Exchange` 
interface for `\Money\Converter` of package 
[moneyphp/money](https://github.com/moneyphp/money).
Source of conversion ratios is czech national bank 
exchange rates published on their website.

## Requirements

```json
{
  "PHP": ">= 7.4",
  "ext-intl": "*",
  "ext-curl": "*",
  "psr/simple-cache": "^1",
  "psr/http-client": "^1",
  "psr/http-factory": "^1",
  "moneyphp/money": "^3"
}
```

## Caching

This package uses [PSR-16](https://www.php-fig.org/psr/psr-16/) `CacheInterface` interface for caching.

## Usage

You should configure your DI to create `\Zrnik\Exchange\CnbExchange` instance for you.

```php
/** @var \Zrnik\Exchange\CnbExchange $exchange */
$exchange = $this->yourDIContainer->get(\Zrnik\Exchange\CnbExchange::class);



$EUR250 = new \Money\Money(25000, new \Money\Currency("EUR"));
$converter = new \Money\Converter(
    new \Money\Currencies\ISOCurrencies(),
    new \Zrnik\Exchange\CnbExchange()
);

$USDValue = $converter->convert($EUR250, new \Money\Currency("USD"));

echo $USDValue->getAmount()." ".$USDValue->getCurrency();
```

Returns new currency converted with CNB exchange ratio.
Something like `29326 USD`.

**But wait, there is more!**

Yeah, ČNB allows getting ratios retrospectively.
There are rules how this works. ČNB is releasing 
new exchange ratios every working day around 14:30.

They are valid until next **working** day, so 
if it's weekend day or holiday, it 
falls back to last working day. 

more info at [https://www.cnb.cz/en/financial-markets/foreign-exchange-market/central-bank-exchange-rate-fixing/central-bank-exchange-rate-fixing/](https://www.cnb.cz/en/financial-markets/foreign-exchange-market/central-bank-exchange-rate-fixing/central-bank-exchange-rate-fixing/)

if you want to, for example, convert $200 to € 
at 24.12.2013 you can do it like this:

```php
$USD200 = new \Money\Money(20000, new \Money\Currency("USD"));

$converter = new \Money\Converter(
    new \Money\Currencies\ISOCurrencies(),
    new \Zrnik\Exchange\CnbExchange(
        mktime(12,0,0,12,24,2013)
    )
);

$EURValue = $converter->convert($USD200, new \Money\Currency("EUR"));

echo $EURValue->getAmount()." ".$EURValue->getCurrency();
```

Will **always** return `14594 EUR`.
