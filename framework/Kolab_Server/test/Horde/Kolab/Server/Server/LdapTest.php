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

        $this->logger = new Horde_Log_Handler_Mock();

        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        Horde_Kolab_Server_Factory::setup(
            array('logger' => new Horde_Log_Logger($this->logger)), $injector
        );
        $this->server = $injector->getInstance('Horde_Kolab_Server');
    }

    public function testMethodConnectuidHasPostconditionThatTheUidIsSetIfTheConnectionWasSuccessful()
    {
        $ldap = $this->getMock('Net_LDAP2', array('bind'));
        $ldap->expects($this->exactly(1))
            ->method('bind')
            ->will($this->returnValue(true));
        $this->server->setLdap($ldap);
        $this->server->connectUid('test', 'test');
        $this->assertEquals('test', $this->server->uid);
    }

    public function testMethodConnectuidThrowsExceptionIfTheConnectionFailed()
    {
        $ldap = $this->getMock('Net_LDAP2', array('bind'));
        $ldap->expects($this->exactly(1))
            ->method('bind')
            ->will($this->returnValue(PEAR::raiseError('Bind failed!')));
        $this->server->setLdap($ldap);
        try {
            $this->server->connectUid('test', 'test');
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Bind failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::BIND_FAILED, $e->getCode());
        }
    }

    public function testMethodSearchHasPostconditionThatItIsPossibleToTestTheLastResultForAnExceededSearchSizeLimit()
    {
        $ldap = $this->getMock('Net_LDAP2', array('search'));
        $ldap->expects($this->exactly(1))
            ->method('search')
            ->will($this->returnValue(new Search_Mock(array(array('dn' => 'test')), true)));
        $this->server->setLdap($ldap);
        $this->server->search('filter');
        $this->assertTrue($this->server->sizeLimitExceeded());
    }

    public function testMethodSearchReturnsArraySearchResult()
    {
        $ldap = $this->getMock('Net_LDAP2', array('search'));
        $ldap->expects($this->exactly(1))
            ->method('search')
            ->will($this->returnValue(new Search_Mock(array(array('dn' => 'test')))));
        $this->server->setLdap($ldap);
        $this->assertEquals(array(array('dn' => 'test')), $this->server->search('filter'));
    }

    public function testMethodSearchReturnsArrayMappedSearchResultIfMappingIsActivated()
    {
        $ldap = $this->getMock('Net_LDAP2', array('search'));
        $ldap->expects($this->exactly(1))
            ->method('search')
            ->will($this->returnValue(new Search_Mock(array(array('dn2' => 'test')))));
        $this->server->setLdap($ldap);
        $this->server->setParams(array('map' => array('dn' => 'dn2')));
        $this->assertEquals(
            array(array('dn' => 'test')),
            $this->server->search(
                'filter',
                array('attributes' => array('dn'))
            )
        );
    }

    public function testMethodSearchThrowsExceptionIfTheSearchFailed()
    {
        $ldap = $this->getMock('Net_LDAP2', array('search'));
        $ldap->expects($this->exactly(1))
            ->method('search')
            ->will($this->returnValue(PEAR::raiseError('Search failed!')));
        $this->server->setLdap($ldap);
        try {
            $this->assertEquals(array('dn' => 'test'), $this->server->search('filter'));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Search failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodReadReturnsArrayReadResult()
    {
        $ldap = $this->getMock('Net_LDAP2', array('search'));
        $ldap->expects($this->exactly(1))
            ->method('search')
            ->will($this->returnValue(new Search_Mock(array(array('dn' => 'test')))));
        $this->server->setLdap($ldap);
        $this->assertEquals(array('dn' => 'test'), $this->server->read('test'));
    }

    public function testMethodReadThrowsExceptionIfTheObjectWasNotFound()
    {
        $ldap = $this->getMock('Net_LDAP2', array('search'));
        $ldap->expects($this->exactly(1))
            ->method('search')
            ->will($this->returnValue(new Search_Mock(array())));
        $this->server->setLdap($ldap);
        try {
            $this->assertEquals(array(), $this->server->read('test', array('dn')));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Empty result!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::EMPTY_RESULT, $e->getCode());
        }
    }

    public function testMethodSaveHasPostconditionThatTheEntryWasSaved()
    {
        $ldap = $this->getMock('Net_LDAP2', array('add'));
        $ldap->expects($this->exactly(1))
            ->method('add')
            ->with(new PHPUnit_Framework_Constraint_IsInstanceOf('Net_LDAP2_Entry'));
        $this->server->setLdap($ldap);
        $this->server->save('test', array('add' => array('dn' => 'test')));
    }

    public function testMethodSaveThrowsExceptionIfDataToSaveIsNoArray()
    {
        $ldap = $this->getMock('Net_LDAP2', array('add'));
        $this->server->setLdap($ldap);
        try {
            $this->server->save('test', array('add' => 'hello'));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Unable to create fresh entry: Parameter $attrs needs to be an array!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }

    }

    public function testMethodSaveThrowsExceptionIfSavingDataFailed()
    {
        $ldap = $this->getMock('Net_LDAP2', array('add'));
        $ldap->expects($this->exactly(1))
            ->method('add')
            ->will($this->returnValue(PEAR::raiseError('Saving failed!')));
        $this->server->setLdap($ldap);
        try {
            $this->server->save('test', array('add' => array('dn' => 'test')));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Saving failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodDeleteHasPostconditionThatTheEntryWasDeleted()
    {
        $ldap = $this->getMock('Net_LDAP2', array('delete'));
        $ldap->expects($this->exactly(1))
            ->method('delete')
            ->with('test');
        $this->server->setLdap($ldap);
        $this->server->delete('test');
    }

    public function testMethodDeleteThrowsExceptionIfDeletingDataFailed()
    {
        $ldap = $this->getMock('Net_LDAP2', array('delete'));
        $ldap->expects($this->exactly(1))
            ->method('delete')
            ->will($this->returnValue(PEAR::raiseError('Deleting failed!')));
        $this->server->setLdap($ldap);
        try {
            $this->server->delete('test');
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Deleting failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodRenameHasPostconditionThatTheEntryWasRenamed()
    {
        $ldap = $this->getMock('Net_LDAP2', array('move'));
        $ldap->expects($this->exactly(1))
            ->method('move')
            ->with('test', 'new');
        $this->server->setLdap($ldap);
        $this->server->rename('test', 'new');
    }

    public function testMethodRenameThrowsExceptionIfRenamingDataFailed()
    {
        $ldap = $this->getMock('Net_LDAP2', array('move'));
        $ldap->expects($this->exactly(1))
            ->method('move')
            ->will($this->returnValue(PEAR::raiseError('Renaming failed!')));
        $this->server->setLdap($ldap);
        try {
            $this->server->rename('test', 'new');
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Renaming failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodGetobjectclassesHasResultArrayWithLowerCaseObjectclassNames()
    {
        $ldap = $this->getMock('Net_LDAP2', array('search'));
        $ldap->expects($this->exactly(1))
            ->method('search')
            ->will($this->returnValue(new Search_Mock(array(array(Horde_Kolab_Server_Object::ATTRIBUTE_OC => array('test', 'PERSON', 'Last'))))));
        $this->server->setLdap($ldap);
        $this->assertEquals(
            array('test', 'person', 'last'),
            $this->server->getObjectClasses('test')
        );
    }

    public function testMethodGetobjectclassesThrowsExceptionIfTheObjectHasNoAttributeObjectclass()
    {
        $ldap = $this->getMock('Net_LDAP2', array('search'));
        $ldap->expects($this->exactly(1))
            ->method('search')
            ->will($this->returnValue(new Search_Mock(array(array('dummy' => array('test', 'PERSON', 'Last'))))));
        $this->server->setLdap($ldap);
        try {
            $this->server->getObjectClasses('test');
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('The object test has no objectClass attribute!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    public function testMethodGetschemaReturnsArrayWithADescriptionOfAllObjectClasses()
    {
        $ldap = $this->getMock('Net_LDAP2', array('schema'));
        $ldap->expects($this->exactly(1))
            ->method('schema')
            ->will($this->returnValue(array('schema' => 'dummy')));
        $this->server->setLdap($ldap);
        $this->assertEquals(
            array('schema' => 'dummy'),
            $this->server->getSchema()
        );
    }

    public function testMethodGetschemaThrowsExceptionIfTheSchemaRetrievalFailed()
    {
        $ldap = $this->getMock('Net_LDAP2', array('schema'));
        $ldap->expects($this->exactly(1))
            ->method('schema')
            ->will($this->returnValue(PEAR::raiseError('Schema failed!')));
        $this->server->setLdap($ldap);
        try {
            $this->server->getSchema();
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Schema failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

    /**
     * Test handling of object classes.
     *
     * @return NULL
     */
/*     public function testGetObjectClasses() */
/*     { */
/*       $ldap = $this->getMock('Horde_Kolab_Server_ldap', array('read')); */
/*         $ldap->expects($this->any()) */
/*             ->method('read') */
/*             ->will($this->returnValue(array ( */
/*                                           'objectClass' => */
/*                                           array ( */
/*                                               'count' => 4, */
/*                                               0 => 'top', */
/*                                               1 => 'inetOrgPerson', */
/*                                               2 => 'kolabInetOrgPerson', */
/*                                               3 => 'hordePerson', */
/*                                           ), */
/*                                           0 => 'objectClass', */
/*                                           'count' => 1))); */

/*         $classes = $ldap->getObjectClasses('cn=Gunnar Wrobel,dc=example,dc=org'); */
/*         if ($classes instanceOf PEAR_Error) { */
/*             $this->assertEquals('', $classes->getMessage()); */
/*         } */
/*         $this->assertContains('top', $classes); */
/*         $this->assertContains('kolabinetorgperson', $classes); */
/*         $this->assertContains('hordeperson', $classes); */

/*         $ldap = $this->getMock('Horde_Kolab_Server_ldap', array('read')); */
/*         $ldap->expects($this->any()) */
/*              ->method('read') */
/*              ->will($this->returnValue(PEAR::raiseError('LDAP Error: No such object: cn=DOES NOT EXIST,dc=example,dc=org: No such object'))); */

/*         $classes = $ldap->getObjectClasses('cn=DOES NOT EXIST,dc=example,dc=org'); */
/*         $this->assertEquals('LDAP Error: No such object: cn=DOES NOT EXIST,dc=example,dc=org: No such object', */
/*                             $classes->message); */
/*     } */

}


class Search_Mock
{
    public function __construct($result, $limit = false)
    {
        $this->result = $result;
        $this->limit  = $limit;
    }
    public function as_struct()
    {
        return $this->result;
    }
    public function sizeLimitExceeded()
    {
        return $this->limit;
    }
}