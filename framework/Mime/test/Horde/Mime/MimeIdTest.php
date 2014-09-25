<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime_Id class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_MimeIdTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider idArithmeticProvider
     */
    public function testIdArithmetic($id, $action, $opts, $expected)
    {
        $id_ob = new Horde_Mime_Id($id);

        $this->assertEquals(
            $expected,
            $id_ob->idArithmetic($action, $opts)
        );
    }

    public function idArithmeticProvider()
    {
        return array(
            array(
                '1.1',
                Horde_Mime_Id::ID_DOWN,
                array(),
                '1.1.0'
            ),
            array(
                '1.1',
                Horde_Mime_Id::ID_DOWN,
                array('no_rfc822' => true),
                '1.1.1'
            ),
            array(
                '1.1',
                Horde_Mime_Id::ID_NEXT,
                array(),
                '1.2'
            ),
            array(
                '1.1',
                Horde_Mime_Id::ID_NEXT,
                array('count' => 3),
                '1.4'
            ),
            array(
                '1.2',
                Horde_Mime_Id::ID_PREV,
                array(),
                '1.1'
            ),
            array(
                '1.1',
                Horde_Mime_Id::ID_PREV,
                array(),
                null
            ),
            array(
                '1.1',
                Horde_Mime_Id::ID_UP,
                array(),
                '1.0'
            ),
            array(
                '1.1',
                Horde_Mime_Id::ID_UP,
                array('no_rfc822' => true),
                '1'
            )
        );
    }

    /**
     * @dataProvider isChildProvider
     */
    public function testIsChild($base, $id, $expected)
    {
        $id_ob = new Horde_Mime_Id($base);

        if ($expected) {
            $this->assertTrue($id_ob->isChild($id));
        } else {
            $this->assertFalse($id_ob->isChild($id));
        }
    }

    public function isChildProvider()
    {
        return array(
            array('1', '1.0', true),
            array('1', '1.1', true),
            array('1', '1.1.0', true),
            array('1', '1.1.1.1.1.1.1', true),
            array('1', '1', false),
            array('1', '2', false),
            array('1', '2.1', false),
            array('1', '10', false),
            array('1', '10.0', false)
        );
    }

}
