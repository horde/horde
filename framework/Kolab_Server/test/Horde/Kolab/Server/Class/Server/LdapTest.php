<?php
/**
 * Test the LDAP driver.
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
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/../../LdapTestCase.php';

/**
 * Test the LDAP backend.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Class_Server_LdapTest extends Horde_Kolab_Server_LdapTestCase
{
    public function setUp()
    {
        $this->ldap_read  = $this->getMock('Horde_Ldap');
        $this->ldap_write = $this->getMock('Horde_Ldap');
        $connection = new Horde_Kolab_Server_Connection_Splittedldap(
            $this->ldap_read,
            $this->ldap_write
        );

        $this->server = new Horde_Kolab_Server_Ldap_Standard(
            $connection,
            'base'
        );
    }

    private function getSearchResultMock()
    {
        $result = $this->getMock(
            'Horde_Ldap_Search', array('asArray', 'count'), array(), '', false
        );
        $result->expects($this->any())
            ->method('asArray')
            ->will($this->returnValue(array(array('dn' => 'test'))));
        $result->expects($this->any())
            ->method('count')
            ->will($this->returnValue(1));
        return $result;
    }

    public function testMethodGetbaseguidHasResultStringBaseGuid()
    {
        $this->assertEquals('base', $this->server->getBaseGuid());
    }

    public function testMethodConnectguidHasStringParameterGuid()
    {
        $this->server->connectGuid('guid', '');
    }

    public function testMethodConnectguidHasStringParameterPass()
    {
        $this->server->connectGuid('', 'pass');
    }

    public function testMethodConnectguidHasPostconditionThatTheGuidIsSetIfTheConnectionWasSuccessful()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('bind')
            ->will($this->returnValue(true));
        $this->server->connectGuid('test', 'test');
        $this->assertEquals('test', $this->server->getGuid());
    }

    public function testMethodConnectguidDoesNotCallBindAgainIfAlreadyConnectedWithThisGuid()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('bind')
            ->will($this->returnValue(true));
        $this->server->connectGuid('test', 'test');
        $this->server->connectGuid('test', 'test');
    }

    public function testMethodConnectguidDoesNotCallBindAgainIfAlreadyConnectedWithThisGuidEvenIfTheGuidIsEmpty()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('bind')
            ->will($this->returnValue(true));
        $this->server->connectGuid('', '');
        $this->server->connectGuid('', '');
    }

    public function testMethodConnectguidThrowsExceptionIfTheConnectionFailed()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('bind')
            ->will($this->throwException(new Horde_Ldap_Exception('Bind failed!')));
        try {
            $this->server->connectGuid('test', 'test');
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Bind failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodConnectguidThrowsExceptionIfTheCredentialsWereInvalid()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('bind')
            ->will($this->throwException(new Horde_Ldap_Exception('Credentials invalid!', 49)));
        try {
            $this->server->connectGuid('test', 'test');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception_Bindfailed $e) {
            $this->assertEquals('Invalid username/password!', $e->getMessage());
        }
    }

    public function testMethodGetguidHasResultBooleanFalseIfNotConnected()
    {
        $this->assertSame(false, $this->server->getGuid());
    }

    public function testMethodGetguidHasResultStringGuidIfNotConnected()
    {
        $this->server->connectGuid('guid', '');
        $this->assertEquals('guid', $this->server->getGuid());
    }

    public function testMethodReadHasParameterStringGuid()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('test', null, array('scope' => 'base'))
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->server->read('test');
    }

    public function testMethodReadReturnsArrayReadResult()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('test', null, array('scope' => 'base'))
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->assertEquals(array('dn' => 'test'), $this->server->read('test'));
    }

    public function testMethodReadThrowsExceptionIfTheObjectWasNotFound()
    {
        $result = $this->getMock(
            'Horde_Ldap_Search', array('asArray', 'count'), array(), '', false
        );
        $result->expects($this->exactly(1))
            ->method('count')
            ->will($this->returnValue(0));
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->will($this->returnValue($result));
        try {
            $this->assertEquals(array(), $this->server->read('test'));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Empty result!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::EMPTY_RESULT, $e->getCode());
        }
    }

    public function testMethodReadAttributesHasParameterStringGuid()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('guid', null, array('scope' => 'base', 'attributes' => array()))
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->server->readAttributes('guid', array());
    }

    public function testMethodReadAttributesHasParameterArrayAttributes()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('', null, array('scope' => 'base', 'attributes' => array('a')))
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->server->readAttributes('', array('a'));
    }

    public function testMethodReadAttributesReturnsArrayReadResult()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('test', null, array('scope' => 'base', 'attributes' => array('a')))
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->assertEquals(array('dn' => 'test'), $this->server->readAttributes('test', array('a')));
    }

    public function testMethodReadAttributesThrowsExceptionIfTheObjectWasNotFound()
    {
        $result = $this->getMock(
            'Horde_Ldap_Search', array('asArray', 'count'), array(), '', false
        );
        $result->expects($this->exactly(1))
            ->method('count')
            ->will($this->returnValue(0));
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('test', null, array('scope' => 'base', 'attributes' => array('a')))
            ->will($this->returnValue($result));
        try {
            $this->assertEquals(array(), $this->server->readAttributes('test', array('a')));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Empty result!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::EMPTY_RESULT, $e->getCode());
        }
    }

    public function testMethodFindHasParameterQueryelementTheSearchCriteria()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('base', '(equals=equals)', array())
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->server->find('(equals=equals)');
    }

    public function testMethodFindHasParameterArrayAdditionalParameters()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('base', '(equals=equals)', array())
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->server->find('(equals=equals)', array());
    }

    public function testMethodFindReturnsArraySearchResult()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('base', '(equals=equals)', array())
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->assertEquals(
            array(array('dn' => 'test')),
            $this->server->find('(equals=equals)')->asArray()
        );
    }

    public function testMethodFindThrowsExceptionIfTheSearchFailed()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->will($this->throwException(new Horde_Ldap_Exception('Search failed!')));
        try {
            $this->assertEquals(array('dn' => 'test'), $this->server->find('(equals=equals)'));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Search failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodSaveHasParameterObjectTheObjectToModifyOnTheServer()
    {
        $entry = $this->getMock(
            'Horde_Ldap_Entry', array(), array(), '', false
        );
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $this->ldap_write->expects($this->exactly(1))
            ->method('getEntry')
            ->will($this->returnValue($entry));
        $this->ldap_write->expects($this->exactly(1))
            ->method('modify')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Horde_Ldap_Entry'));
        $object->expects($this->exactly(1))
            ->method('readInternal')
            ->will($this->returnValue(array()));
        $this->server->save($object, array('attributes' => array('dn')));
    }

    public function testMethodSaveHasParameterArrayData()
    {
        $entry = $this->getMock(
            'Horde_Ldap_Entry', array(), array(), '', false
        );
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $this->ldap_write->expects($this->exactly(1))
            ->method('getEntry')
            ->will($this->returnValue($entry));
        $this->ldap_write->expects($this->exactly(1))
            ->method('modify')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Horde_Ldap_Entry'));
        $object->expects($this->exactly(1))
            ->method('readInternal')
            ->will($this->returnValue(array()));
        $this->server->save($object, array('dn' => 'test'));
    }

    public function testMethodSaveHasPostconditionThatTheEntryWasModified()
    {
        $entry = $this->getMock(
            'Horde_Ldap_Entry', array(), array(), '', false
        );
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $this->ldap_write->expects($this->exactly(1))
            ->method('getEntry')
            ->will($this->returnValue($entry));
        $this->ldap_write->expects($this->exactly(1))
            ->method('modify')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Horde_Ldap_Entry'));
        $object->expects($this->exactly(1))
            ->method('readInternal')
            ->will($this->returnValue(array()));
        $this->server->save($object, array('dn' => 'test'));
    }

    public function testMethodSaveThrowsExceptionIfSavingDataFailed()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $this->ldap_write->expects($this->exactly(1))
            ->method('modify')
            ->will($this->throwException(new Horde_Ldap_Exception('Saving failed!')));
        $object->expects($this->exactly(1))
            ->method('readInternal')
            ->will($this->returnValue(array()));
        try {
            $this->server->save($object, array('dn' => 'test'));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Saving failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodAddHasParameterObjectTheObjectToAddToTheServer()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $this->ldap_write->expects($this->exactly(1))
            ->method('add')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Horde_Ldap_Entry'));
        $this->server->add($object, array('attributes' => array('dn')));
    }

    public function testMethodAddHasParameterArrayData()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $this->ldap_write->expects($this->exactly(1))
            ->method('add')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Horde_Ldap_Entry'));
        $this->server->add($object, array('dn' => 'test'));
    }

    public function testMethodAddHasPostconditionThatTheEntryWasModified()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $this->ldap_write->expects($this->exactly(1))
            ->method('add')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Horde_Ldap_Entry'));
        $this->server->add($object, array('dn' => 'test'));
    }

    public function testMethodAddThrowsExceptionIfSavingDataFailed()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $this->ldap_write->expects($this->exactly(1))
            ->method('add')
            ->will($this->throwException(new Horde_Ldap_Exception('Saving failed!')));
        try {
            $this->server->add($object, array('add' => array('dn' => 'test')));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Adding object failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodDeleteHasParameterStringGuid()
    {
        $this->ldap_write->expects($this->exactly(1))
            ->method('delete')
            ->with('guid');
        $this->server->delete('guid');
    }

    public function testMethodDeleteHasPostconditionThatTheEntryWasDeleted()
    {
        $this->ldap_write->expects($this->exactly(1))
            ->method('delete')
            ->with('test');
        $this->server->delete('test');
    }

    public function testMethodDeleteThrowsExceptionIfDeletingDataFailed()
    {
        $this->ldap_write->expects($this->exactly(1))
            ->method('delete')
            ->will($this->throwException(new Horde_Ldap_Exception('Deleting failed!')));
        try {
            $this->server->delete('test');
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Deleting object failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodRenameHasParameterStringOldGuid()
    {
        $this->ldap_write->expects($this->exactly(1))
            ->method('move')
            ->with('oldguid', '');
        $this->server->rename('oldguid', '');
    }

    public function testMethodRenameHasParameterStringNewGuid()
    {
        $this->ldap_write->expects($this->exactly(1))
            ->method('move')
            ->with('', 'newguid');
        $this->server->rename('', 'newguid');
    }

    public function testMethodRenameHasPostconditionThatTheEntryWasRenamed()
    {
        $this->ldap_write->expects($this->exactly(1))
            ->method('move')
            ->with('test', 'new');
        $this->server->rename('test', 'new');
    }

    public function testMethodRenameThrowsExceptionIfRenamingDataFailed()
    {
        $this->ldap_write->expects($this->exactly(1))
            ->method('move')
            ->will($this->throwException(new Horde_Ldap_Exception('Renaming failed!')));
        try {
            $this->server->rename('test', 'new');
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Renaming object failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodGetschemaReturnsArrayWithADescriptionOfAllObjectClasses()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('schema')
            ->will($this->returnValue(array('schema' => 'dummy')));
        $this->assertEquals(
            array('schema' => 'dummy'),
            $this->server->getSchema()
        );
    }

    public function testMethodGetschemaThrowsExceptionIfTheSchemaRetrievalFailed()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('schema')
            ->will($this->throwException(new Horde_Ldap_Exception('Schema failed!')));
        try {
            $this->server->getSchema();
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Retrieving the schema failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodGetparentguidHasResultStringParentGuid()
    {
        $this->assertEquals(
            'cn=parent', $this->server->getParentGuid('cn=child,cn=parent')
        );
    }

/*     public function testMethodSearchReturnsArrayMappedSearchResultIfMappingIsActivated() */
/*     { */
/*         $ldap = $this->getMock('Horde_Ldap', array('search')); */
/*         $ldap->expects($this->exactly(1)) */
/*             ->method('search') */
/*             ->will($this->returnValue(new Search_Mock(array(array('dn2' => 'test'))))); */
/*         $this->server->setLdap($ldap); */
/*         $this->server->setParams(array('map' => array('dn' => 'dn2'))); */
/*         $this->assertEquals( */
/*             array(array('dn' => 'test')), */
/*             $this->server->search( */
/*                 'filter', */
/*                 array('attributes' => array('dn')) */
/*             ) */
/*         ); */
/*     } */

}
