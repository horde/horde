<?php
/**
 * Horde_Service_Gravatar abstracts communication with Services supporting the
 * Gravatar API (http://www.gravatar.com/site/implement/).
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Service_Gravatar
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Service_Gravatar
 */

require_once 'Horde/Service/Gravatar.php';

/**
 * Horde_Service_Gravatar abstracts communication with Services supporting the
 * Gravatar API (http://www.gravatar.com/site/implement/).
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Service_Gravatar
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Service_Gravatar
 */
class Horde_Service_Gravatar_GravatarTest
extends PHPUnit_Framework_TestCase
{
    public function testReturn()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertType('string', $g->getId('test'));
    }

    public function testAddress()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            '0c17bf66e649070167701d2d3cd71711',
            $g->getId('test@example.org')
        );
    }

    /**
     * @dataProvider provideAddresses
     */
    public function testAddresses($mail, $id)
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals($id, $g->getId($mail));
    }

    public function provideAddresses()
    {
        return array(
            array('test@example.org', '0c17bf66e649070167701d2d3cd71711'),
            array('x@example.org', 'ae46d8cbbb834a85db7287f8342d0c42'),
            array('test@example.com', '55502f40dc8b7c769880b10874abc9d0'),
        );
    }

    public function testIgnoreCase()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            '0c17bf66e649070167701d2d3cd71711',
            $g->getId('Test@EXAMPLE.orG')
        );
    }

    public function testTrimming()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            '0c17bf66e649070167701d2d3cd71711',
            $g->getId(' Test@Example.orG ')
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidMail()
    {
        $g = new Horde_Service_Gravatar();
        $g->getId(0.0);
    }

    public function testAvatarUrl()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            'http://www.gravatar.com/avatar/0c17bf66e649070167701d2d3cd71711',
            $g->getAvatarUrl(' Test@Example.orG ')
        );
    }

}