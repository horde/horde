<?php
/**
 * Test the uncached query set.
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
 * Test the uncached query set.
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
class Horde_Kolab_Storage_Unit_QuerySet_UncachedTest
extends Horde_Kolab_Storage_TestCase
{
    public function testAddDataQuery()
    {
        $this->_assertDataQuery(array('data' => array('queryset' => 'horde')));
    }

    public function testAddMySet()
    {
        $this->_assertDataQuery(
            array('data' => array('myset' => array(Horde_Kolab_Storage_Data::QUERY_PREFS => 'h-prefs')))
        );
    }

    private function _assertDataQuery($params)
    {
        $driver = $this->getNullMock();
        $factory = new Horde_Kolab_Storage_Factory();
        $list = new Horde_Kolab_Storage_List_Query_List_Base(
            $driver,
            new Horde_Kolab_Storage_Folder_Types(),
            new Horde_Kolab_Storage_List_Query_List_Defaults_Bail()
        );
        $data = new Horde_Kolab_Storage_Data_Base(
            new Horde_Kolab_Storage_Folder_Base(
                $list, 'INBOX/Preferences'
            ),
            $driver,
            $factory,
            'h-prefs'
        );
        $query_set = new Horde_Kolab_Storage_QuerySet_Uncached(
            $factory, $params
        );
        $query_set->addDataQuerySet($data);
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Data_Query',
            $data->getQuery(Horde_Kolab_Storage_Data::QUERY_PREFS)
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testNoSuchDataQuerySet()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        new Horde_Kolab_Storage_QuerySet_Uncached(
            $factory, array('data' => array('queryset' => 'NO_SUCH_SET'))
        );
    }

}
