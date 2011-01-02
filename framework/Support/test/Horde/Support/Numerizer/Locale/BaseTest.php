<?php
/**
 * @category   Horde
 * @package    Horde_Support
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Horde_Support
 * @subpackage UnitTests
 */
class Horde_Support_Numerizer_Locale_BaseTest extends PHPUnit_Framework_TestCase
{
    public function testStraightParsing()
    {
        $numerizer = Horde_Support_Numerizer::factory();
        $strings = array(
            1 => 'one',
            5 => 'five',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            27 => 'twenty seven',
            31 => 'thirty-one',
            59 => 'fifty nine',
            100 => 'a hundred',
            100 => 'one hundred',
            150 => 'one hundred and fifty',
            // 150 => 'one fifty',
            200 => 'two-hundred',
            500 => '5 hundred',
            999 => 'nine hundred and ninety nine',
            1000 => 'one thousand',
            1200 => 'twelve hundred',
            1200 => 'one thousand two hundred',
            17000 => 'seventeen thousand',
            21473 => 'twentyone-thousand-four-hundred-and-seventy-three',
            74002 => 'seventy four thousand and two',
            99999 => 'ninety nine thousand nine hundred ninety nine',
            100000 => '100 thousand',
            250000 => 'two hundred fifty thousand',
            1000000 => 'one million',
            1250007 => 'one million two hundred fifty thousand and seven',
            1000000000 => 'one billion',
            1000000001 => 'one billion and one',
        );

        foreach ($strings as $key => $string) {
            $this->assertEquals($key, (int)$numerizer->numerize($string));
        }
    }

    public function testLeavesDatesAlone()
    {
        $numerizer = Horde_Support_Numerizer::factory();

        $this->assertEquals('2006-08-20 03:00', $numerizer->numerize('2006-08-20 03:00'));
        $this->assertEquals('2006-08-20 15:30:30', $numerizer->numerize('2006-08-20 15:30:30'));
    }

    public function testStaticNumerize()
    {
        $this->assertEquals('2006-08-20 03:00', Horde_Support_Numerizer::numerize('2006-08-20 03:00'));
    }
}
