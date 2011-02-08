<?php
/**
 * Test the factory.
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the factory.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Format_Unit_FactoryTest
extends PHPUnit_Framework_TestCase
{
    public function testFactory()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Xml_Contact',
            $factory->create('Xml', 'Contact')
        );
    }

    public function testFactoryUcfirst()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Xml_Contact',
            $factory->create('xml', 'Contact')
        );
    }

    public function testFactoryStrtolower()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Xml_Contact',
            $factory->create('XML', 'Contact')
        );
    }

    public function testTypeUcfirst()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Xml_Contact',
            $factory->create('Xml', 'contact')
        );
    }

    public function testTypeStrtolower()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Xml_Contact',
            $factory->create('Xml', 'CONTACT')
        );
    }

    public function testTypeDashes()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Xml_Contact',
            $factory->create('Xml', 'CON--TACT')
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testFactoryException()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $factory->create('UNKNOWN', 'contact');
    }

    public function testTimeLog()
    {
        if (!class_exists('Horde_Support_Timer')) {
            $this->markTestSkipped('Horde_Support package missing!');
        }
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Decorator_Timed',
            $factory->create('Xml', 'contact', array('timelog' => true))
        );
    }

    public function testMemoryLog()
    {
        if (!class_exists('Horde_Support_Memory')) {
            $this->markTestSkipped('Horde_Support package missing!');
        }
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Decorator_Memory',
            $factory->create('Xml', 'contact', array('memlog' => true))
        );
    }
}
