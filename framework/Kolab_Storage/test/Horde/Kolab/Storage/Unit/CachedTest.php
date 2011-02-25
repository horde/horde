<?php
/**
 * Test the cache decorator for the storage handler.
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
 * Test the cache decorator for the storage handler.
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
class Horde_Kolab_Storage_Unit_CachedTest
extends Horde_Kolab_Storage_TestCase
{
    public function testConstruction()
    {
        $this->createCachedStorage();
    }

    public function testGetList()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Decorator_Cache',
            $this->createCachedStorage()->getList()
        );
    }

    public function testSameList()
    {
        $base = $this->createCachedStorage();
        $this->assertSame($base->getList(), $base->getList());
    }

    public function testGetFolder()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder',
            $this->createCachedStorage($this->getAnnotatedMock())->getFolder('INBOX')
        );
    }

    public function testGetData()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Data_Decorator_Cache',
            $this->createCachedStorage($this->getAnnotatedMock())->getData('INBOX')
        );
    }

    public function testSameData()
    {
        $base = $this->createCachedStorage($this->getAnnotatedMock());
        $this->assertSame(
            $base->getData('INBOX'), $base->getData('INBOX')
        );
    }

    public function testDifferentFolders()
    {
        $base = $this->createCachedStorage($this->getAnnotatedMock());
        $this->assertNotSame(
            $base->getData('INBOX'), $base->getData('INBOX/a')
        );
    }
}
