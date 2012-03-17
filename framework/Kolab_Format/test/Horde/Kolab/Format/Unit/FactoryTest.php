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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
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

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testUnknownFormatException()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $factory->create('Exception', 'InvalidRoot');
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

    public function testConstructorParams()
    {
        if (!class_exists('Horde_Support_Timer')) {
            $this->markTestSkipped('Horde_Support package missing!');
        }
        $factory = new Horde_Kolab_Format_Factory(array('timelog' => true));
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Decorator_Timed',
            $factory->create('Xml', 'contact')
        );
    }

    public function testTypeFactory()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Stub_Types',
            $factory->createXmlType('Horde_Kolab_Format_Stub_Types')
        );
    }

    public function testTypeFactoryV1()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Stub_Types_V1',
            $factory->createXmlType(
                'Horde_Kolab_Format_Stub_Types',
                array('api-version' => 1)
            )
        );
    }

    public function testTypeFactoryV2()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Stub_Types_V2',
            $factory->createXmlType(
                'Horde_Kolab_Format_Stub_Types',
                array('api-version' => 2)
            )
        );
    }

    public function testTypeFactoryV3()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Format_Stub_Types',
            $factory->createXmlType(
                'Horde_Kolab_Format_Stub_Types',
                array('api-version' => 3)
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testTypeMissing()
    {
        $factory = new Horde_Kolab_Format_Factory();
        $factory->createXmlType('Horde_Kolab_Format_Stub_Types_NOSUCH');
    }
}
