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
    public function test() {}
    /* /\** */
    /*  * @expectedException Horde_Kolab_Storage_Exception */
    /*  *\/ */
    /* public function testCreateQueryForUnsupported() */
    /* { */
    /*     $list = new Horde_Kolab_Storage_List_Base( */
    /*         $this->getNullMock(), */
    /*         new Horde_Kolab_Storage_Factory() */
    /*     ); */
    /*     $factory = new Horde_Kolab_Storage_Factory(); */
    /*     $factory->createListQuery('NO_SUCH_QUERY', $list); */
    /* } */

    /* public function testQueryReturnsQuery() */
    /* { */
    /*     $list = new Horde_Kolab_Storage_List_Base( */
    /*         $this->getNullMock(), */
    /*         new Horde_Kolab_Storage_Factory() */
    /*     ); */
    /*     $factory = new Horde_Kolab_Storage_Factory(); */
    /*     $this->assertInstanceOf( */
    /*         'Horde_Kolab_Storage_List_Query', */
    /*         $factory->createListQuery('Horde_Kolab_Storage_List_Query_List_Base', $list) */
    /*     ); */
    /* } */

    /* public function testQueryStub() */
    /* { */
    /*     $list = new Horde_Kolab_Storage_List_Base( */
    /*         $this->getNullMock(), */
    /*         new Horde_Kolab_Storage_Factory() */
    /*     ); */
    /*     $factory = new Horde_Kolab_Storage_Factory(); */
    /*     $this->assertInstanceOf( */
    /*         'Horde_Kolab_Storage_List_Query', */
    /*         $factory->createListQuery( */
    /*             'Horde_Kolab_Storage_Stub_ListQuery', */
    /*             $list */
    /*         ) */
    /*     ); */
    /* } */
}
