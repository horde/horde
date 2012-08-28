<?php
/**
 * Tests the folder type factory.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Tests the folder type factory.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_Folder_TypesTest
extends PHPUnit_Framework_TestCase
{
    public function testConstruction()
    {
        new Horde_Kolab_Storage_Folder_Types();
    }

    public function testType()
    {
        $types = new Horde_Kolab_Storage_Folder_Types();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Type',
            $types->create('event')
        );
    }

    public function testTypeContact()
    {
        $types = new Horde_Kolab_Storage_Folder_Types();
        $this->assertEquals('contact', $types->create('contact')->getType());
    }

    public function testTypeDefaultEvent()
    {
        $types = new Horde_Kolab_Storage_Folder_Types();
        $this->assertEquals('event', $types->create('event.default')->getType());
    }

    public function testTypeDefaultIsDefault()
    {
        $types = new Horde_Kolab_Storage_Folder_Types();
        $this->assertTrue($types->create('contact.default')->isDefault());
    }

    public function testNoDefault()
    {
        $types = new Horde_Kolab_Storage_Folder_Types();
        $this->assertFalse($types->create('contact')->isDefault());
    }

    public function testSame()
    {
        $types = new Horde_Kolab_Storage_Folder_Types();
        $this->assertSame(
            $types->create('contact'), $types->create('contact')
        );
    }
}
