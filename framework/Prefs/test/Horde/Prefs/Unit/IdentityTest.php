<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Prefs
 * @package   Prefs
 */

/**
 * Test the Identity object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Prefs
 * @package   Prefs
 */
class Horde_Prefs_Unit_IdentityTest extends PHPUnit_Framework_TestCase
{
    private $identity;

    /**
     */
    public function setUp()
    {
        $this->identity = new Horde_Prefs_Identity(array(
            'prefs' => new Horde_Prefs('foo'),
            'user' => 'foo'
        ));
    }

    /**
     */
    public function testIdentityAdd()
    {
        $this->assertEquals(
            0,
            $this->identity->add(array())
        );
    }

    /**
     * @depends testIdentityAdd
     */
    public function testIdentityGet()
    {
        $this->identity->add(array());

        $this->assertInternalType('array', $this->identity->get(0));
        $this->assertNull($this->identity->get(1));
    }

    /**
     * @depends testIdentityAdd
     */
    public function testIdentityDelete()
    {
        $this->identity->add(array());

        $this->assertEquals(
            array(),
            $this->identity->delete(0)
        );

        $this->assertNull($this->identity->get(0));
    }

}
