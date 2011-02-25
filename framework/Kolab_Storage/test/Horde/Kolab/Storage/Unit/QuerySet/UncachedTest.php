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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the uncached query set.
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
class Horde_Kolab_Storage_Unit_QuerySet_UncachedTest
extends Horde_Kolab_Storage_TestCase
{
    public function testAddListQuery()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        $query_set = new Horde_Kolab_Storage_QuerySet_Uncached(
            $factory
        );
        $query_set->addListQuerySet($list);
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query',
            $list->getQuery()
        );
    }

    public function testHordeset()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        $query_set = new Horde_Kolab_Storage_QuerySet_Uncached(
            $factory, array('list' => array('queryset' => Horde_Kolab_Storage_QuerySet_Uncached::HORDE))
        );
        $query_set->addListQuerySet($list);
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_Share',
            $list->getQuery(Horde_Kolab_Storage_List::QUERY_SHARE)
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testNoSuchSet()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        new Horde_Kolab_Storage_QuerySet_Uncached(
            $factory, array('list' => array('queryset' => 'NO_SUCH_SET'))
        );
    }

    public function testMySet()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        $query_set = new Horde_Kolab_Storage_QuerySet_Uncached(
            $factory, array('list' => array('myset' => array(Horde_Kolab_Storage_List::QUERY_ACL)))
        );
        $query_set->addListQuerySet($list);
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query_ACL',
            $list->getQuery(Horde_Kolab_Storage_List::QUERY_ACL)
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingMySet()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        $query_set = new Horde_Kolab_Storage_QuerySet_Uncached(
            $factory, array('list' => array('myset' => array(Horde_Kolab_Storage_List::QUERY_ACL)))
        );
        $query_set->addListQuerySet($list);
        $list->getQuery(Horde_Kolab_Storage_List::QUERY_BASE);
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testUnsupported()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        $query_set = new Horde_Kolab_Storage_QuerySet_Uncached(
            $factory, array('list' => array('myset' => array('UNSUPPORTED')))
        );
        $query_set->addListQuerySet($list);
    }

    public function testQueryStub()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        $query_set = new Horde_Kolab_Storage_QuerySet_Uncached(
            $factory,
            array(
                'list' => array(
                    'classmap' => array(
                        'Stub' => 'Horde_Kolab_Storage_Stub_ListQuery'
                    ),
                    'myset' => array('Stub')
                )
            )
        );
        $query_set->addListQuerySet($list);
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Stub_ListQuery',
            $list->getQuery('Stub')
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testInvalidClass()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $factory = new Horde_Kolab_Storage_Factory();
        $query_set = new Horde_Kolab_Storage_QuerySet_Uncached(
            $factory,
            array(
                'list' => array(
                    'classmap' => array(
                        'Stub' => 'NON_EXISTING_CLASS'
                    ),
                    'myset' => array('Stub')
                )
            )
        );
        $query_set->addListQuerySet($list);
        $list->getQuery('Stub');
    }
}
