<?php

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Zrnik\Exchange\Utilities;

class CnbExchangeTest extends TestCase
{
    public function testExchange()
    {
        $When = mktime(
            12, 0, 0,
            8, 22, 2016
        );

        $USD_345 = new Money(34500, new Currency("USD"));

        $GBP_263_13 = Utilities::convert(
            $USD_345, new Currency("GBP"), $When
        );

        $this->assertEquals(
            26313,
            $GBP_263_13->getAmount()
        );
    }
}
