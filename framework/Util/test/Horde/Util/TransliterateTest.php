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
    public function testTransliterateToAscii()
    {
        // No normalization
        $str = 'ABC123abc';
        $this->assertEquals(
            $str,
            Horde_String_Transliterate::toAscii($str)
        );

        // Non-ascii can all be transliterated
        $this->assertEquals(
            'AABBEESSs',
            Horde_String_Transliterate::toAscii('AÀBÞEÉSß')
        );

        // Some non-ascii cannot be transliterated
        $this->assertEquals(
            'AA黾BB',
            Horde_String_Transliterate::toAscii('AÀ黾BÞ')
        );
    }

}
