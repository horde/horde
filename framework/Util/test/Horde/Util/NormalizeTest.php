<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Util
 * @subpackage UnitTests
 */
class Horde_Util_NormalizeTest extends PHPUnit_Framework_TestCase
{
    public function testNormalizeToAscii()
    {
        // No normalization
        $str = 'ABC123abc';
        $this->assertEquals(
            $str,
            Horde_String_Normalize::normalizeToAscii($str)
        );

        // Non-ascii can all be normalized
        $this->assertEquals(
            'AABBEESSs',
            Horde_String_Normalize::normalizeToAscii('AÀBÞEÉSß')
        );

        // Some non-ascii cannot be normalized
        $this->assertEquals(
            'AA黾BB',
            Horde_String_Normalize::normalizeToAscii('AÀ黾BÞ')
        );
    }

}
