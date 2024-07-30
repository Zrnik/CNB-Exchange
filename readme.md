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

- PHP Version `^8`
- `moneyphp/money` version `^4`
- library implementing `psr/cache`
- library implementing `psr/http-client` & `psr/http-factory`

## Version 2

Bumped PHP requirement to `^8` and `moneyphp/money` version `^4`.
Code got a full refactor. Custom file cache removed in favor of PSR caching interfaces.
CURL usage removed in favor of PSR http interfaces.
