<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Util
 * @subpackage UnitTests
 */
class Horde_Util_TransliterateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider fallbackDataProvider
     */
    public function testTransliterateToAsciiFallback($str, $expected)
    {
        $this->assertEquals(
            $expected,
            Horde_Util_Mock_Transliterate::testFallback($str)
        );
    }

    public function fallbackDataProvider()
    {
        return array(
            // No normalization
            array('ABC123abc', 'ABC123abc'),
            // Non-ascii can all be transliterated
            array('AÀBÞEÉSß', 'AABTHEESss'),
            // Some non-ascii cannot be transliterated
            array('AÀ黾BÞ', 'AA黾BTH')
        );
    }

    /**
     * @dataProvider intlDataProvider
     */
    public function testTransliterateToAsciiIntl($str, $expected)
    {
        if (!class_exists('Transliterator')) {
            $this->markTestSkipped('intl extension not installed or too old');
        }

        $this->assertEquals(
            $expected,
            Horde_Util_Mock_Transliterate::testIntl($str)
        );
    }

    public function intlDataProvider()
    {
        return array(
            // No normalization
            array('ABC123abc', 'ABC123abc'),
            // Non-ascii can all be transliterated
            array('AÀBÞEÉSß', 'AABTHEESss'),
            // Some non-ascii cannot be transliterated
            array('AÀ黾BÞ', 'AA mianBTH')
        );
    }

    /**
     * @dataProvider iconvDataProvider
     */
    public function testTransliterateToAsciiIconv($str, $expected)
    {
        if (!extension_loaded('iconv')) {
            $this->markTestSkipped('iconv extension not installed');
        }

        $this->assertEquals(
            $expected,
            Horde_Util_Mock_Transliterate::testIconv($str)
        );
    }

    public function iconvDataProvider()
    {
        return array(
            // No normalization
            array('ABC123abc', 'ABC123abc'),
            // Non-ascii can all be transliterated
            array('AÀBÞEÉSß', 'AAB?EESss'),
            // Some non-ascii cannot be transliterated
            array('AÀ黾BÞ', 'AA?B?')
        );
    }

}
