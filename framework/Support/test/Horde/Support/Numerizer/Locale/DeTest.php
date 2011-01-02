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
class Horde_Support_Numerizer_Locale_DeTest extends PHPUnit_Framework_TestCase
{
    public function testStraightParsing()
    {
        $numerizer = Horde_Support_Numerizer::factory(array('locale' => 'de'));
        $strings = array(
            array(1, 'eins'),
            array(5, 'fünf'),
            array(10, 'zehn'),
            array(11, 'elf'),
            array(12, 'zwölf'),
            array(13, 'dreizehn'),
            array(14, 'vierzehn'),
            array(15, 'fünfzehn'),
            array(16, 'sechzehn'),
            array(17, 'siebzehn'),
            array(18, 'achtzehn'),
            array(19, 'neunzehn'),
            array(20, 'zwanzig'),
            array(27, 'siebenundzwanzig'),
            array(31, 'einunddreißig'),
            array(59, 'neunundfünfzig'),
            array(100, 'einhundert'),
            array(100, 'ein hundert'),
            array(150, 'hundertundfünfzig'),
            array(150, 'einhundertundfünfzig'),
            array(200, 'zweihundert'),
            array(500, 'fünfhundert'),
            array(999, 'neunhundertneunundneunzig'),
            array(1000, 'eintausend'),
            array(1200, 'zwölfhundert'),
            array(1200, 'eintausendzweihundert'),
            array(17000, 'siebzehntausend'),
            array(21473, 'einundzwanzigtausendvierhundertdreiundsiebzig'),
            array(74002, 'vierundsiebzigtausendzwei'),
            array(74002, 'vierundsiebzigtausendundzwei'),
            array(99999, 'neunundneunzigtausendneunhundertneunundneunzig'),
            array(100000, 'hunderttausend'),
            array(100000, 'einhunderttausend'),
            array(250000, 'zweihundertfünfzigtausend'),
            array(1000000, 'eine million'),
            array(1250007, 'eine million zweihundertfünfzigtausendundsieben'),
            array(1000000000, 'eine milliarde'),
            array(1000000001, 'eine milliarde und eins'),
        );

        foreach ($strings as $pair) {
            $this->assertEquals((string)$pair[0], $numerizer->numerize($pair[1]));
        }
    }

    public function testLocaleVariants()
    {
        $this->assertInstanceOf('Horde_Support_Numerizer_Locale_De', Horde_Support_Numerizer::factory(array('locale' => 'de_DE')));
        $this->assertInstanceOf('Horde_Support_Numerizer_Locale_De', Horde_Support_Numerizer::factory(array('locale' => 'de_at')));
    }

    public function testStaticNumerize()
    {
        $this->assertEquals(1250007, Horde_Support_Numerizer::numerize('eine million zweihundertfünfzigtausendundsieben', array('locale' => 'de')));
    }
}
