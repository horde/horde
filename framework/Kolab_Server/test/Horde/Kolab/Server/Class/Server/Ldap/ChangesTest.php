<?php
/**
 * Test the LDAP changeset handler.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the LDAP changeset handler.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Class_Server_Ldap_ChangesTest extends PHPUnit_Framework_TestCase
{
    public function testMethodConstructHasParameterServerobject()
    {
        $changes = new Horde_Kolab_Server_Ldap_Changes(
            $this->getMock('Horde_Kolab_Server_Object_Interface'), array()
        );
    }

    public function testMethodConstructHasParameterArrayDataToBeStored()
    {
        $changes = new Horde_Kolab_Server_Ldap_Changes(
            $this->getMock('Horde_Kolab_Server_Object_Interface'),
            array('store' => 'value')
        );
    }

    public function testMethodGetchangesetHasResultArrayEmptyIfOldAndNewDatasetsWereEmpty()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $object->expects($this->once())
            ->method('readInternal')
            ->will($this->returnValue(array()));
        $changes = new Horde_Kolab_Server_Ldap_Changes(
            $object, array()
        );
        $this->assertEquals(array(), $changes->getChangeset());
    }

    public function testMethodGetchangesetHasResultArrayEmptyIfOldAndNewDatasetsWereEqual()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $object->expects($this->once())
            ->method('readInternal')
            ->will($this->returnValue(array('a' => array('a'))));
        $changes = new Horde_Kolab_Server_Ldap_Changes(
            $object, array('a' => array('a'))
        );
        $this->assertEquals(array(), $changes->getChangeset());
    }

    public function testMethodGetchangesetHasResultArrayNewAttributesInNewDatasetAsAdded()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $object->expects($this->once())
            ->method('readInternal')
            ->will($this->returnValue(array()));
        $changes = new Horde_Kolab_Server_Ldap_Changes(
            $object, array('new' => 'a')
        );
        $this->assertEquals(
            array(
                'add' => array(
                    'new' => 'a'
                )
            ),
            $changes->getChangeset()
        );
    }

    public function testMethodGetchangesetHasResultArrayMissingValuesInNewDatasetAsDeleted()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $object->expects($this->once())
            ->method('readInternal')
            ->will($this->returnValue(array('old' => 'a')));
        $changes = new Horde_Kolab_Server_Ldap_Changes(
            $object, array()
        );
        $this->assertEquals(
            array(
                'delete' => array(
                    'old'
                )
            ),
            $changes->getChangeset()
        );
    }

    public function testMethodGetchangesetHasResultArraySingleValuesWithDifferencesAsReplaced()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $object->expects($this->once())
            ->method('readInternal')
            ->will($this->returnValue(array('value' => 'a')));
        $changes = new Horde_Kolab_Server_Ldap_Changes(
            $object, array('value' => 'b')
        );
        $this->assertEquals(
            array(
                'replace' => array(
                    'value' => 'b'
                )
            ),
            $changes->getChangeset()
        );
    }

    public function testMethodGetchangesetHasResultArrayTheNewValuesAsAdded()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $object->expects($this->once())
            ->method('readInternal')
            ->will($this->returnValue(array('value' => array('a', 'b', 'c'))));
        $changes = new Horde_Kolab_Server_Ldap_Changes(
            $object, array('value' => array('a', 'b', 'c', 'd'))
        );
        $this->assertEquals(
            array(
                'add' => array(
                    'value' => array('d')
                )
            ),
            $changes->getChangeset()
        );
    }

    public function testMethodGetchangesetHasResultArrayTheRemovedValuesAsDeleted()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $object->expects($this->once())
            ->method('readInternal')
            ->will($this->returnValue(array('value' => array('a', 'b', 'c'))));
        $changes = new Horde_Kolab_Server_Ldap_Changes(
            $object, array('value' => array('b', 'c'))
        );
        $this->assertEquals(
            array(
                'delete' => array(
                    'value' => array('a')
                )
            ),
            $changes->getChangeset()
        );
    }

    public function testMethodGetchangesetHasResultArrayTheNewValuesAsAddedAndTheRemovedValuesAsDeleted()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $object->expects($this->once())
            ->method('readInternal')
            ->will($this->returnValue(array('value' => array('a', 'b', 'c'))));
        $changes = new Horde_Kolab_Server_Ldap_Changes(
            $object, array('value' => array('b', 'c', 'd'))
        );
        $this->assertEquals(
            array(
                'add' => array(
                    'value' => array('d')
                ),
                'delete' => array(
                    'value' => array('a')
                )
            ),
            $changes->getChangeset()
        );
    }
}
