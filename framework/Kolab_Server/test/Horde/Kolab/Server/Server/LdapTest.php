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
require_once dirname(__FILE__) . '/../LdapBase.php';

/**
 * Test the LDAP backend.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Server_LdapTest extends Horde_Kolab_Server_LdapBase
{
    public function setUp()
    {
        parent::setUp();

        $this->ldap_read  = $this->getMock('Net_LDAP2');
        $this->ldap_write = $this->getMock('Net_LDAP2');
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
            'Net_LDAP2_Search', array('as_struct', 'count'), array(), '', false
        );
        $result->expects($this->any())
            ->method('as_struct')
            ->will($this->returnValue(array(array('dn' => 'test'))));
        $result->expects($this->any())
            ->method('count')
            ->will($this->returnValue(1));
        return $result;
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
            ->will($this->returnValue(new PEAR_Error('Bind failed!')));
        try {
            $this->server->connectGuid('test', 'test');
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Bind failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::BIND_FAILED, $e->getCode());
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
            'Net_LDAP2_Search', array('as_struct', 'count'), array(), '', false
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
            'Net_LDAP2_Search', array('as_struct', 'count'), array(), '', false
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
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $this->server->find($equals);
    }

    public function testMethodFindHasParameterArrayAdditionalParameters()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('base', '(equals=equals)', array())
            ->will($this->returnValue($this->getSearchResultMock()));
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $this->server->find($equals, array());
    }

    public function testMethodFindReturnsArraySearchResult()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('base', '(equals=equals)', array())
            ->will($this->returnValue($this->getSearchResultMock()));
        $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
        $this->assertEquals(
            array(array('dn' => 'test')),
            $this->server->find($equals)->asArray()
        );
    }

    public function testMethodFindThrowsExceptionIfTheSearchFailed()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->will($this->returnValue(new PEAR_Error('Search failed!')));
        try {
            $equals = new Horde_Kolab_Server_Query_Element_Equals('equals', 'equals');
            $this->assertEquals(array('dn' => 'test'), $this->server->find($equals));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Search failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodSaveHasParameterObjectTheObjectToModifyOnTheServer()
    {
        $entry = $this->getMock(
            'Net_LDAP2_Entry', array(), array(), '', false
        );
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $this->ldap_write->expects($this->exactly(1))
            ->method('getEntry')
            ->will($this->returnValue($entry));
        $this->ldap_write->expects($this->exactly(1))
            ->method('modify')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Net_LDAP2_Entry'));
        $object->expects($this->exactly(1))
            ->method('readInternal')
            ->will($this->returnValue(array()));
        $this->server->save($object, array('attributes' => array('dn')));
    }

    public function testMethodSaveHasParameterArrayData()
    {
        $entry = $this->getMock(
            'Net_LDAP2_Entry', array(), array(), '', false
        );
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $this->ldap_write->expects($this->exactly(1))
            ->method('getEntry')
            ->will($this->returnValue($entry));
        $this->ldap_write->expects($this->exactly(1))
            ->method('modify')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Net_LDAP2_Entry'));
        $object->expects($this->exactly(1))
            ->method('readInternal')
            ->will($this->returnValue(array()));
        $this->server->save($object, array('dn' => 'test'));
    }

    public function testMethodSaveHasPostconditionThatTheEntryWasModified()
    {
        $entry = $this->getMock(
            'Net_LDAP2_Entry', array(), array(), '', false
        );
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $this->ldap_write->expects($this->exactly(1))
            ->method('getEntry')
            ->will($this->returnValue($entry));
        $this->ldap_write->expects($this->exactly(1))
            ->method('modify')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Net_LDAP2_Entry'));
        $object->expects($this->exactly(1))
            ->method('readInternal')
            ->will($this->returnValue(array()));
        $this->server->save($object, array('dn' => 'test'));
    }

    public function testMethodSaveThrowsExceptionIfSavingDataFailed()
    {
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $this->ldap_write->expects($this->exactly(1))
            ->method('modify')
            ->will($this->returnValue(new PEAR_Error('Saving failed!')));
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
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $this->ldap_write->expects($this->exactly(1))
            ->method('add')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Net_LDAP2_Entry'));
        $this->server->add($object, array('attributes' => array('dn')));
    }

    public function testMethodAddHasParameterArrayData()
    {
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $this->ldap_write->expects($this->exactly(1))
            ->method('add')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Net_LDAP2_Entry'));
        $this->server->add($object, array('dn' => 'test'));
    }

    public function testMethodAddHasPostconditionThatTheEntryWasModified()
    {
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $this->ldap_write->expects($this->exactly(1))
            ->method('add')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Net_LDAP2_Entry'));
        $this->server->add($object, array('dn' => 'test'));
    }

    public function testMethodAddThrowsExceptionIfSavingDataFailed()
    {
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $this->ldap_write->expects($this->exactly(1))
            ->method('add')
            ->will($this->returnValue(new PEAR_Error('Saving failed!')));
        try {
            $this->server->add($object, array('add' => array('dn' => 'test')));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Saving failed!', $e->getMessage());
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
            ->will($this->returnValue(new PEAR_Error('Deleting failed!')));
        try {
            $this->server->delete('test');
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Deleting failed!', $e->getMessage());
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
            ->will($this->returnValue(new PEAR_Error('Renaming failed!')));
        try {
            $this->server->rename('test', 'new');
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Renaming failed!', $e->getMessage());
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
            ->will($this->returnValue(new PEAR_Error('Schema failed!')));
        try {
            $this->server->getSchema();
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Schema failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

/*     public function testMethodSearchReturnsArrayMappedSearchResultIfMappingIsActivated() */
/*     { */
/*         $ldap = $this->getMock('Net_LDAP2', array('search')); */
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
