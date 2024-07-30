# ČNB Exchange

![GitHub](https://img.shields.io/github/license/zrnik/CNB-Exchange)
![Packagist Version](https://img.shields.io/packagist/v/zrnik/cnb-exchange)

**ČNB** = **Česká Národní Banka** (*czech national bank*)

This is an implementation of the `\Money\Exchange`
interface for `\Money\Converter` of the
[moneyphp/money](https://github.com/moneyphp/money) library.

Conversion rates are fetched from czech national bank
exchange rates published on their website.

## Requirements

- PHP 8+
- `moneyphp/money` 4+
- library implementing PSR-6 & PSR-16 (Cache)
- library implementing PSR-7, PSR-17 & PSR-18 (HTTP Client + HTTP Factory)

```json
{
    "require": {
        "PHP": "^8",
        "ext-intl": "*",
        "moneyphp/money": "^4",
        "psr/cache": "^3",
        "psr/http-client": "^1",
        "psr/http-factory": "^1"
    }
},
```

Version 2
---

Bumped PHP requirement to `^8` and `moneyphp/money` version `^4`.
Code got a full refactor. Custom file cache removed in favor of PSR caching interfaces.
CURL usage removed in favor of PSR http interfaces.
