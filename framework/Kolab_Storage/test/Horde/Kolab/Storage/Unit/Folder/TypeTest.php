<?php
/**
 * Test the folder type handler.
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
 * Test the folder type handler.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Folder_TypeTest
extends Horde_Kolab_Storage_TestCase
{
    public function testConstruction()
    {
        new Horde_Kolab_Storage_Folder_Type('event');
    }

    public function testTypeEvent()
    {
        $type = new Horde_Kolab_Storage_Folder_Type('event');
        $this->assertEquals('event', $type->getType());
    }

    public function testTypeContact()
    {
        $type = new Horde_Kolab_Storage_Folder_Type('contact');
        $this->assertEquals('contact', $type->getType());
    }

    public function testTypeDefaultEvent()
    {
        $type = new Horde_Kolab_Storage_Folder_Type('event.default');
        $this->assertEquals('event', $type->getType());
    }

    public function testTypeDefaultIsDefault()
    {
        $type = new Horde_Kolab_Storage_Folder_Type('contact.default');
        $this->assertTrue($type->isDefault());
    }

    public function testNoDefault()
    {
        $type = new Horde_Kolab_Storage_Folder_Type('contact');
        $this->assertFalse($type->isDefault());
    }
}
