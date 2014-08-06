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

    /**
     * @depends testIdentityAdd
     */
    public function testArrayAccessExists()
    {
        $this->identity->add(array());

        $this->assertTrue(isset($this->identity[0]));
        $this->assertFalse(isset($this->identity[1]));
    }

    /**
     * @depends testIdentityAdd
     */
    public function testArrayAccessGet()
    {
        $this->identity->add(array());

        $this->assertInternalType('array', $this->identity[0]);
        $this->assertNull($this->identity[1]);
    }

    /**
     * @depends testIdentityAdd
     */
    public function testArrayAccessUnset()
    {
        $this->identity->add(array());

        $this->assertInternalType('array', $this->identity[0]);

        unset($this->identity[0]);

        $this->assertNull($this->identity[0]);
    }

    /**
     * @depends testIdentityAdd
     */
    public function testCountable()
    {
        $this->assertEquals(0, count($this->identity));
        $this->identity->add(array());
        $this->assertEquals(1, count($this->identity));
    }

    /**
     * @depends testIdentityAdd
     */
    public function testIterator()
    {
        $this->identity->add(array());
        $this->assertEquals(1, count(iterator_to_array($this->identity)));
    }

}
