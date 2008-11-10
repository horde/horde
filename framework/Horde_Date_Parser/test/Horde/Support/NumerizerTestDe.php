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
class Horde_Support_NumerizerTestDe extends PHPUnit_Framework_TestCase
{
    public function testStraightParsing()
    {
        $numerizer = Horde_Support_Numerizer::factory();
        $strings = array(
            1 => 'eins',
            5 => 'fünf',
            10 => 'zehn',
            11 => 'elf',
            12 => 'zwölf',
            13 => 'dreizehn',
            14 => 'vierzehn',
            15 => 'fünfzehn',
            16 => 'sechzehn',
            17 => 'siebzehn',
            18 => 'achtzehn',
            19 => 'neunzehn',
            20 => 'zwanzig',
            27 => 'siebenundzwanzig',
            31 => 'einunddreißig',
            59 => 'neunundfünfzig',
            100 => 'einhundert',
            100 => 'ein hundert',
            150 => 'hundertundfünfzig',
            150 => 'einhundertundfünfzig',
            200 => 'zweihundert',
            500 => 'fünfhundert',
            999 => 'neunhundertneunundneunzig',
            1000 => 'eintausend',
            1200 => 'zwölfhundert',
            1200 => 'eintausenzweihundert',
            17000 => 'siebzehntausend',
            21473 => 'einundzwanzigtausendvierhundertdreiundsiebzig',
            74002 => 'vierundsiebzigtausendzwei',
            74002 => 'vierundsiebzigtausendundzwei',
            99999 => 'neunundneunzigtausendneunhundertneunundneunzig',
            100000 => 'hunderttausend',
            100000 => 'einhunderttausend',
            250000 => 'zweihundertfünfzigtausend',
            1000000 => 'eine million',
            1250007 => 'eine million zweihundertfünfzigtausendundsieben',
            1000000000 => 'eine milliarde',
            1000000001 => 'eine milliarde und eins',
        );

        foreach ($strings as $key => $string) {
            $this->assertEquals($key, (int)$numerizer->numerize($string));
        }
    }

}
