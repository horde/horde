<?php
/**
 * Test the factory.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_FactoryTest
extends Horde_Kolab_Storage_TestCase
{
    public function testCreation()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertType(
            'Horde_Kolab_Storage_Base',
            $factory->create(
                new Horde_Kolab_Storage_Driver_Mock(
                    new Horde_Kolab_Storage_Factory()
                )
            )
        );
    }

    public function testCreationFromParams()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertType(
            'Horde_Kolab_Storage_Base',
            $factory->createFromParams(array('driver' => 'mock'))
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingDriver()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $factory->createDriverFromParams(array());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testInvalidDriver()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $factory->createDriverFromParams(array('driver' => 'something'));
    }

    public function testMockDriver()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertType(
            'Horde_Kolab_Storage_Driver_Mock',
            $factory->createDriverFromParams(
                array('driver' => 'mock')
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testInvalidNamespace()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $factory->createNamespace(
            'undefined'
        );
    }

    public function testFixedNamespace()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Namespace_Fixed',
            $factory->createNamespace(
                'fixed'
            )
        );
    }
}
