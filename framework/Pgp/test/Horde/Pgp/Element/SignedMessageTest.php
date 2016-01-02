<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */

/**
 * Tests for the PGP signed message element object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */
class Horde_Pgp_Element_SignedMessageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getSignersKeyIdProvider
     */
    public function testGetSignersKeyId($expected, $data)
    {
        $sig = Horde_Pgp_Element_SignedMessage::create($data);

        $this->assertInstanceOf(
            'Horde_Pgp_Element_SignedMessage',
            $sig
        );

        $this->assertInstanceOf(
            'Horde_Pgp_Element_Signature',
            $sig->signature
        );

        $this->assertInstanceOf(
            'Horde_Pgp_Element_Text',
            $sig->text
        );

        $this->assertEquals(
            $expected,
            $sig->signature->getSignersKeyId()
        );
    }

    public function getSignersKeyIdProvider()
    {
        return array(
            array(
                'BADEABD7',
                $this->_getFixture('pgp_signed.txt')
            )
        );
    }

    public function testDashUnescapeOutput()
    {
        $this->assertEquals(
            $this->_getFixture('dash_escape.txt'),
            Horde_Pgp_Element_SignedMessage::dashUnescapeText(
                $this->_getFixture('dash_escape_escaped.txt')
            )
        );
    }

    public function testDashEscapeOutput()
    {
        $this->assertEquals(
            $this->_getFixture('dash_escape_escaped.txt'),
            Horde_Pgp_Element_SignedMessage::dashEscapeText(
                $this->_getFixture('dash_escape.txt')
            )
        );
    }

    protected function _getFixture($file)
    {
        return file_get_contents(__DIR__ . '/../fixtures/' . $file);
    }

}
