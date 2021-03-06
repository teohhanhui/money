<?php

namespace Money\Parser;

use Money\Currencies;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Money;
use Money\MoneyParser;
use Money\Number;

/**
 * Parses a decimal string into a Money object.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class DecimalMoneyParser implements MoneyParser
{
    const DECIMAL_PATTERN = '/^(?P<sign>-)?(?P<digits>0|[1-9]\d*)(?:\.(?P<fraction>\d+))?$/';

    /**
     * @var Currencies
     */
    private $currencies;

    /**
     * @param Currencies $currencies
     */
    public function __construct(Currencies $currencies)
    {
        $this->currencies = $currencies;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($money, $forceCurrency = null)
    {
        if (!is_string($money)) {
            throw new ParserException('Formatted raw money should be string, e.g. 1.00');
        }

        if ($forceCurrency === null) {
            throw new ParserException(
                'DecimalMoneyParser cannot parse currency symbols. Use forceCurrency argument'
            );
        }

        $currency = new Currency($forceCurrency);
        $decimal = trim($money);
        $subunit = $this->currencies->subunitFor($currency);

        if (!preg_match(self::DECIMAL_PATTERN, $decimal, $matches)) {
            throw new ParserException(sprintf(
                'Cannot parse "%s" to Money.',
                $decimal
            ));
        }

        $negative = isset($matches['sign']) && $matches['sign'] === '-';

        $decimal = $matches['digits'];
        if ($negative) {
            $decimal = '-'.$decimal;
        }

        if (isset($matches['fraction'])) {
            $fractionDigits = strlen($matches['fraction']);
            $decimal .= $matches['fraction'];
            $decimal = Number::roundMoneyValue($decimal, $subunit, $fractionDigits);

            if ($fractionDigits > $subunit) {
                $decimal = substr($decimal, 0, $subunit - $fractionDigits);
            } elseif ($fractionDigits < $subunit) {
                $decimal .= str_pad('', $subunit - $fractionDigits, '0');
            }
        } else {
            $decimal .= str_pad('', $subunit, '0');
        }

        if ($negative) {
            $decimal = '-'.ltrim(substr($decimal, 1), '0');
        } else {
            $decimal = ltrim($decimal, '0');
        }

        if ($decimal === '' || $decimal === '-') {
            $decimal = '0';
        }

        return new Money($decimal, $currency);
    }
}
