<?php

namespace Zrnik\Exchange;

use DateTime;

class DateConvert
{
    public static function fromDateTime(DateTime $dateTime): string
    {
        return $dateTime->format('d.m.Y');
    }
}
