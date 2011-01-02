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
class Horde_Kolab_Storage_Unit_Decorator_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testDecoratedList()
    {
        $storage = new Horde_Kolab_Storage_Decorator_Cache(
            new Horde_Kolab_Storage_Base(
                $this->getNullMock(),
                new Horde_Kolab_Storage_Factory()
            ),
            new Horde_Kolab_Storage_Cache(null)
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Decorator_Cache',
            $storage->getList()
        );
    }
}
