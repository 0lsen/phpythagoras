<?php

namespace Math\Model\Number;

use Math\Exception\DivisionByZeroException;
use Math\Exception\UnknownOperandException;
use Math\Functions\CalcUtil;
use Math\Functions\Denominator;

/**
 * Class RationalNumber
 * @package Math\Number\Model
 */
class RationalNumber extends AbstractNumber implements ComparableNumber
{
    public static $decimalThreshold = 8;

    /** sign @var integer */
    private $s;

    /** numerator @var integer */
    private $n;

    /** denominator @var integer */
    private $d;

    /**
     * RationalNumber constructor.
     * @param $n
     * @param null $d
     * @param null $s
     * @throws DivisionByZeroException
     * @throws UnknownOperandException
     */
    public function __construct($n, $d = null, $s = null)
    {
        if (is_int($n)) {
            if (!$n) {
                $this->s = 0;
            } else {
                $this->n = abs($n);
                if (is_int($d)) {
                    if (!$d) {
                        throw new DivisionByZeroException();
                    } else {
                        $this->d = $d;
                    }
                    if (is_numeric($s)) {
                        $this->s = $s <=> 0;
                    } else {
                        $this->s = ($n < 0) == ($d < 0) ? 1 : -1;
                    }
                } else {
                    $this->d = 1;
                    $this->s = $n < 0 ? -1 : 1;
                }
            }
        } elseif ($n instanceof RationalNumber) {
            $this->n = $n->n;
            $this->d = $n->d;
            $this->s = $n->s;
        } else {
            throw new UnknownOperandException(get_class($n));
        }
    }

    public function __toString()
    {
        if (!$this->s) return "0";

        $int = floor($this->n / $this->d);
        $mod = $this->n % $this->d;

        $string = "";
        if ($this->s < 0) $string .= "- ";
        if ($int) $string .= $int;
        if ($int && $mod) $string .= " ";
        if ($mod) $string .= $mod."/".$this->d;

        return $string;
    }

    private function reduce()
    {
        if ($this->s) {
            $gcd = Denominator::GCD($this->n, $this->d);
            if ($gcd > 1) {
                $this->n /= $gcd;
                $this->d /= $gcd;
            }
        }
    }

    public function value()
    {
        if ($this->s) {
            $abs = $this->absoluteValue();
            return $this->s == 1 ? $abs : -$abs;
        } else {
            return 0;
        }
    }

    public function absoluteValue()
    {
        if (!$this->s) {
            return 0;
        }
        return $this->n / $this->d;
    }

    public function equals(Number $number)
    {
        if ($number instanceof RationalNumber) {
            if (!$this->s && !$number->s) {
                return true;
            } else {
                return $this->n == $number->n
                    && $this->d == $number->d
                    && $this->s == $number->s;
            }
        } elseif ($number instanceof Number) {
            $value = $number->value();
            return is_numeric($value) && $this->value() == $value;
        } else {
            throw new UnknownOperandException();
        }
    }

    public function compareTo(ComparableNumber $number)
    {
        return $this->value() <=> $number->value();
    }

    public function negative()
    {
        if ($this->s) {
            $this->s = $this->s == 1 ? -1 : 1;
        }
        return $this;
    }

    public function add(Number $number)
    {
        if ($number instanceof RationalNumber) {
            if ($this->s) {
                if ($number->s) {
                    $lcm = Denominator::LCM($this->d, $number->d);
                    $summand1 = $this->n * $lcm / $this->d;
                    $summand2 = $number->n * $lcm / $number->d;
                    $this->d = $lcm;
                    if ($this->s == $number->s) {
                        $this->n = $summand1 + $summand2;
                    } else {
                        $n = $this->s == 1 ? $summand1 - $summand2 : $summand2 - $summand1;
                        $this->n = abs($n);
                        $this->s = $n <=> 0;
                    }
                    $this->reduce();
                }
            } else {
                $this->s = $number->s;
                $this->n = $number->n;
                $this->d = $number->d;
            }
            return $this;
        } else {
            return (new RealNumber($this->value()))->add($number);
        }
    }

    public function subtract(Number $number)
    {
        return $this->add($number->negative_());
    }

    public function multiplyWith(Number $number)
    {
        if ($number instanceof RationalNumber) {
            if (!$this->s || !$number->s) {
                $this->s = 0;
            } else {
                $this->n *= $number->n;
                $this->d *= $number->d;
                $this->s = (($this->s < 0) == ($number->s < 0)) ? 1 : -1;
                $this->reduce();
            }
            return $this;
        } else {
            return (new RealNumber($this->value()))->multiplyWith($number);
        }
    }

    public function divideBy(Number $number)
    {
        if ($number instanceof RationalNumber) {
            return $this->multiplyWith($number->reciprocal_());
        } else {
            return $this->multiplyWith((new RealNumber(1))->divideBy($number));
        }
    }

    public function square()
    {
        if ($this->s) {
            $this->s = 1;
            $this->n *= $this->n;
            $this->d *= $this->d;
        }
        return $this;
    }

    public function squareRoot()
    {
        return $this->root(2);
    }

    public function root($nth)
    {
        if ($this->s) {
            $n = CalcUtil::nthRoot($this->n, $nth);
            $d = CalcUtil::nthRoot($this->d, $nth);
            if ($this->canBeCastToInt($n) && $this->canBeCastToInt($d)) {
                $this->n = round($n, 0);
                $this->d = round($d, 0);
                return $this;
            } else {
                return new RealNumber($n/$d);
            }
        }
        return $this;
    }

    private function canBeCastToInt($number) {
        return round($number, self::$decimalThreshold) == round($number, 0);
    }

    public function normSquared()
    {
        return $this->square_();
    }

    /**
     * @return $this
     * @throws DivisionByZeroException
     */
    public function reciprocal() {
        if ($this->s) {
            $n = $this->n;
            $this->n = $this->d;
            $this->d = $n;
            return $this;
        } else {
            throw new DivisionByZeroException();
        }
    }

    /**
     * @return int
     */
    public function getS(): int
    {
        return $this->s;
    }

    /**
     * @return int
     */
    public function getN(): int
    {
        return $this->n;
    }

    /**
     * @return int
     */
    public function getD(): int
    {
        return $this->d;
    }
}