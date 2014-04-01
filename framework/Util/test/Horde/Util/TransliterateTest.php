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
    public function testTransliterateToAsciiFallback()
    {
        // No normalization
        $str = 'ABC123abc';
        $this->assertEquals(
            $str,
            Horde_Util_Mock_Transliterate::testFallback($str)
        );

        // Non-ascii can all be transliterated
        $this->assertEquals(
            'AABTHEESss',
            Horde_Util_Mock_Transliterate::testFallback('AÀBÞEÉSß')
        );

        // Some non-ascii cannot be transliterated
        $this->assertEquals(
            'AA黾BTH',
            Horde_Util_Mock_Transliterate::testFallback('AÀ黾BÞ')
        );
    }

    public function testTransliterateToAsciiIntl()
    {
        if (!class_exists('Transliterator')) {
            $this->markTestSkipped('intl extension not installed or too old');
        }

        // No normalization
        $str = 'ABC123abc';
        $this->assertEquals(
            $str,
            Horde_Util_Mock_Transliterate::testIntl($str)
        );

        // Non-ascii can all be transliterated
        $this->assertEquals(
            'AABTHEESss',
            Horde_Util_Mock_Transliterate::testIntl('AÀBÞEÉSß')
        );

        // Some non-ascii cannot be transliterated
        $this->assertEquals(
            'AA mianBTH',
            Horde_Util_Mock_Transliterate::testIntl('AÀ黾BÞ')
        );
    }

    public function testTransliterateToAsciiIconv()
    {
        if (!extension_loaded('iconv')) {
            $this->markTestSkipped('iconv extension not installed');
        }

        // No normalization
        $str = 'ABC123abc';
        $this->assertEquals(
            $str,
            Horde_Util_Mock_Transliterate::testIconv($str)
        );

        // Non-ascii can all be transliterated
        $this->assertEquals(
            'AAB?EESss',
            Horde_Util_Mock_Transliterate::testIconv('AÀBÞEÉSß')
        );

        // Some non-ascii cannot be transliterated
        $this->assertEquals(
            'AA?B?',
            Horde_Util_Mock_Transliterate::testIconv('AÀ黾BÞ')
        );
    }
}
