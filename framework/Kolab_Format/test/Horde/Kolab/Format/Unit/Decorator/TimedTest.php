<?php
/**
 * Test the decorator for time measurements.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the decorator for time measurements.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Unit_Decorator_TimedTest
extends Horde_Kolab_Format_TestCase
{
    public function testConstructor()
    {
        $this->getFactory()->createTimed('XML', 'contact');
    }

    public function testGetName()
    {
        $this->assertEquals(
            'kolab.xml',
            $this->getFactory()->createTimed('XML', 'contact')->getName()
        );
    }

    public function testGetMimeType()
    {
        $this->assertEquals(
            'application/x-vnd.kolab.contact',
            $this->getFactory()->createTimed('XML', 'contact')->getMimeType()
        );
    }

    public function testGetDisposition()
    {
        $this->assertEquals(
            'attachment',
            $this->getFactory()->createTimed('XML', 'contact')->getDisposition()
        );
    }

    public function testTimeSpent()
    {
        $mock = $this->getMock('Horde_Kolab_Format');
        $timed = $this->getFactory()->createTimed(
            'XML', 'contact', array('handler' => $mock)
        );
        $a = '';
        $timed->load($a);
        $this->assertType(
            'float',
            $timed->timeSpent()
        );
    }

    public function testTimeSpentIncreases()
    {
        $mock = $this->getMock('Horde_Kolab_Format');
        $timed = $this->getFactory()->createTimed(
            'XML', 'contact', array('handler' => $mock)
        );
        $a = '';
        $timed->load($a);
        $t_one = $timed->timeSpent();
        $timed->save(array());
        $this->assertTrue(
            $t_one < $timed->timeSpent()
        );
    }
}
