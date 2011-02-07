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
require_once dirname(__FILE__) . '/../../Autoload.php';

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
class Horde_Kolab_Storage_Unit_Decorator_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testDecoratedList()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $storage = new Horde_Kolab_Storage_Decorator_Cache(
            new Horde_Kolab_Storage_Base(
                $this->getNullMock($factory),
                $factory
            ),
            $this->getMockCache(),
            $factory
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Decorator_Cache',
            $storage->getList()
        );
    }

    public function testFolder()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $storage = new Horde_Kolab_Storage_Decorator_Cache(
            new Horde_Kolab_Storage_Base(
                $this->getNullMock($factory),
                $factory
            ),
            $this->getMockCache(),
            $factory
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder',
            $storage->getFolder('test')
        );
    }
}
