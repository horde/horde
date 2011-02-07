<?php
/**
 * Test the basic storage handler.
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
 * Test the basic storage handler.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_BaseTest
extends Horde_Kolab_Storage_TestCase
{
    public function testConstruction()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        new Horde_Kolab_Storage_Base(
            new Horde_Kolab_Storage_Driver_Mock($factory),
            $factory
        );
    }

    public function testGetList()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $base = new Horde_Kolab_Storage_Base(
            new Horde_Kolab_Storage_Driver_Mock($factory),
            $factory
        );
        $this->assertInstanceOf('Horde_Kolab_Storage_List', $base->getList());
    }

    public function testSameList()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $base = new Horde_Kolab_Storage_Base(
            new Horde_Kolab_Storage_Driver_Mock($factory),
            $factory
        );
        $this->assertSame($base->getList(), $base->getList());
    }

   public function testGetFolder()
   {
        $factory = new Horde_Kolab_Storage_Factory();
        $base = new Horde_Kolab_Storage_Base(
            $this->getAnnotatedMock(),
            $factory
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder', $base->getFolder('INBOX')
        );
    }

   public function testGetData()
   {
        $factory = new Horde_Kolab_Storage_Factory();
        $base = new Horde_Kolab_Storage_Base(
            $this->getAnnotatedMock(),
            $factory
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Data', $base->getData('INBOX')
        );
    }

   public function testSameData()
   {
        $factory = new Horde_Kolab_Storage_Factory();
        $base = new Horde_Kolab_Storage_Base(
            $this->getAnnotatedMock(),
            $factory
        );
        $this->assertSame(
            $base->getData('INBOX'), $base->getData('INBOX')
        );
    }

   public function testDifferentFolders()
   {
        $factory = new Horde_Kolab_Storage_Factory();
        $base = new Horde_Kolab_Storage_Base(
            $this->getAnnotatedMock(),
            $factory
        );
        $this->assertNotSame(
            $base->getData('INBOX'), $base->getData('INBOX/a')
        );
    }
}
