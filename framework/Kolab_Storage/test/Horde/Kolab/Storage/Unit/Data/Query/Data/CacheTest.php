<?php
/**
 * Test the cached data query.
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
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the cached data query.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Data_Query_Data_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testSynchronize()
    {
        $this->_getDataCache()->synchronize();
    }

    private function _getDataCache()
    {
        return new Horde_Kolab_Storage_Data_Query_Data_Cache(
            $this->getMock('Horde_Kolab_Storage_Data'),
            array(
                'cache' => $this->getMockDataCache(),
                'factory' => new Horde_Kolab_Storage_Factory()
            )
        );
    }
}
