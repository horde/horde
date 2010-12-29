<?php
/**
 * Test the basic list query.
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
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the basic list query.
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
class Horde_Kolab_Storage_Unit_List_Query_BaseTest
extends Horde_Kolab_Storage_TestCase
{
    public function testByTypeReturnsArray()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $query = $list->getQuery('Base');
        $this->assertType('array', $query->listByType('test'));
    }

    public function testListCalendarsListsCalendars()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getAnnotatedMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $query = $list->getQuery('Base');
        $this->assertEquals(array('INBOX/Calendar'), $query->listByType('event'));
    }

    public function testListTasklistsListsTasklists()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getAnnotatedMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $query = $list->getQuery('Base');
        $this->assertEquals(array('INBOX/Tasks'), $query->listByType('task'));
    }
}
