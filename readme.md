# ČNB Exchange

![GitHub](https://img.shields.io/github/license/zrnik/cnb-exchange)
![Packagist Downloads](https://img.shields.io/packagist/dm/zrnik/cnb-exchange)
![Travis (.com)](https://travis-ci.com/Zrnik/cnb-exchange.svg?branch=master)
![Packagist Version](https://img.shields.io/packagist/v/zrnik/cnb-exchange)  


**ČNB** = **Česká Národní Banka** (*czech national bank*)

This is a class implementing `\Money\Exchange` interface for `\Money\Converter` class.

## Requirements

```json
{
    "PHP": ">= 7.4",
    "ext-intl": "*",
    "ext-curl": "*",
    "moneyphp/money": "^v3.3"
}
```

## Caching

As ČNB ratios are not changing, we don't need to
invalidate our cache, so we use pure static file cache. 
Default cache location is in "temp" directory next to 
the "src" directory of this package. Path to cache 
directory can be changed like this:
`\Zrnik\Exchange\CnbExchange::$tempDir = "new/temp/dir";`.

## Usage

```php
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

$USDValue = $converter->convert($USD200, new \Money\Currency("EUR"));

echo $USDValue->getAmount()." ".$USDValue->getCurrency();
```

Will **always** return `14594 EUR`.


## Utilities

I have prepared some utilities you might consider useful.

#### Format

If you want to print a currency nicely you need to do it like this:

```php
$numberFormatter = new \NumberFormatter("de_DE", \NumberFormatter::CURRENCY);
$moneyFormatter = new \Money\Formatter\IntlMoneyFormatter($numberFormatter, new \Money\Currencies\ISOCurrencies());
echo $moneyFormatter->format(new \Money\Money(25000,new \Money\Currency("EUR")));

echo PHP_EOL;

$numberFormatter = new \NumberFormatter("en_US", \NumberFormatter::CURRENCY);
$moneyFormatter = new \Money\Formatter\IntlMoneyFormatter($numberFormatter, new \Money\Currencies\ISOCurrencies());
echo $moneyFormatter->format(new \Money\Money(25000,new \Money\Currency("USD")));
```

to get result like this:

```
250,00 €
$250.00
```

Notice its pretty long code, and you have 
to define correct language code for each 
currency? The same can be achieved with 
`\Zrnik\Exchange\Utilities::format(\Money\Money $Money);`  
method.

```php
echo \Zrnik\Exchange\Utilities::format(
    new \Money\Money(25000, new \Money\Currency("EUR"))
);
echo PHP_EOL;
echo \Zrnik\Exchange\Utilities::format(
    new \Money\Money(25000, new \Money\Currency("USD"))
);
```

BOOM! Tetris for Jeff!
... I mean, its done.

#### Static Converter

You can use the static converter for a quick conversion.
The third parameter is time, defaults to 'yesterday'.

```php
echo
\Zrnik\Exchange\Utilities::format(
    \Zrnik\Exchange\Utilities::convert(
        new \Money\Money(1500, new \Money\Currency("EUR")),
        new \Money\Currency("USD")
    )
);

echo PHP_EOL;

echo
\Zrnik\Exchange\Utilities::format(
    \Zrnik\Exchange\Utilities::convert(
        new \Money\Money(1500, new \Money\Currency("EUR")),
        new \Money\Currency("USD"),
        mktime(
            12, 0, 0,
            1, 1, 2000
        )
    )
);
```

Returns: 

```
$17.60  //This one depends on daily exchange rate
$15.06  //This one will have this value every time!
```

## Converter Factory

This is just a candy. It allows us to write this:

```php
$converterToday = \Zrnik\Exchange\Utilities::createConverter();
$converterNewYear =  \Zrnik\Exchange\Utilities::createConverter(
    mktime(12,0,0,1,1,2015)
);
```

instead of this:

```php
$converterToday = new \Money\Converter(new \Money\Currencies\ISOCurrencies(), new \Zrnik\Exchange\CnbExchange());
$converterNewYear = new \Money\Converter(
    new \Money\Currencies\ISOCurrencies(), 
    new \Zrnik\Exchange\CnbExchange(
        mktime(12,0,0,1,1,2015)
    )
);
```



