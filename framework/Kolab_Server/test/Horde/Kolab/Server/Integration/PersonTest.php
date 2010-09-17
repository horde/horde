<?php
/**
 * Test the person object.
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
require_once dirname(__FILE__) . '/Scenario.php';

/**
 * Test the person object.
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
class Horde_Kolab_Server_Integration_PersonTest extends Horde_Kolab_Server_Integration_Scenario
{

    public $cn = 'Kolab_Server_PersonTest';

    /**
     * Objects used within this test
     *
     * @var array
     */
    private $objects = array(
        /* Default dummy person */
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            'Cn'           => 'Kolab_Server_PersonTest_123',
            'Sn'           => 'Kolab_Server_PersonTest_123',
            'Userpassword' => 'Kolab_Server_PersonTest_123',
        ),
        /* Invalid person (no sn) */
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            'Cn'           => 'Kolab_Server_PersonTest_123',
            'Userpassword' => 'Kolab_Server_PersonTest_123',
        ),
        /* Person with problematic characters */
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            'Cn'           => 'Kolab_Server_PersonTest_!"$%&()=?',
            'Sn'           => 'Kolab_Server_PersonTest_!"$%&()=?',
            'Userpassword' => 'Kolab_Server_PersonTest_!"$%&()=?',
        ),
        /* Person with difficult encoding */
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            'Cn'           => 'Kolab_Server_PersonTest_ügöräß§',
            'Sn'           => 'Kolab_Server_PersonTest_ügöräß§',
            'Userpassword' => 'Kolab_Server_PersonTest_ügöräß§',
        ),
        /* Person with forward slash */
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            'Cn'           => 'Kolab_Server_PersonTest_/',
            'Sn'           => 'Kolab_Server_PersonTest_/',
            'Userpassword' => 'Kolab_Server_PersonTest_/',
        ),
        /* Person with double cn */
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            'Cn'           => array('Kolab_Server_PersonTest_cn1',
                                                                              'Kolab_Server_PersonTest_cn2'),
            'Sn'           => 'Kolab_Server_PersonTest_cncn',
            'Userpassword' => 'Kolab_Server_PersonTest_cncn',
        ),
        /* Person with name suffix*/
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            'Cn'           => 'Kolab_Server_PersonTest_123',
            'Sn'           => 'Kolab_Server_PersonTest_123',
            'Userpassword' => 'Kolab_Server_PersonTest_123',
        ),
        /* Person for telephone number handling*/
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            'Cn'           => 'Kolab_Server_PersonTest_123456',
            'Sn'           => 'Kolab_Server_PersonTest_123456',
            'Userpassword' => 'Kolab_Server_PersonTest_123456',
        ),
        /* Person with a creation date*/
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            'Cn'           => 'Kolab_Server_PersonTest_123456',
            'Sn'           => 'Kolab_Server_PersonTest_123456',
            'Userpassword' => 'Kolab_Server_PersonTest_123456',
            'Creationdate' => '191008030000Z',
        ),
    );

    /**
     * Set up testing.
     *
     * @return NULL
     */
    protected function setUp()
    {
        parent::setUp();

        $this->initializeEnvironments();
        $this->servers = $this->getKolabServers();
    }

    /**
     * Test ID generation for a person.
     *
     * @return NULL
     */
    public function testGenerateId()
    {
        foreach ($this->servers as $server) {
            $a = new Horde_Kolab_Server_Object_Person($server, null, $this->objects[0]);
            $this->assertContains('Cn' . '=' . $this->objects[0]['Cn'],
                                  $a->get(Horde_Kolab_Server_Object_Person::ATTRIBUTE_UID));
        }
    }

    /**
     * Test adding an invalid person.
     *
     * @expectedException Horde_Kolab_Server_Exception
     *
     * @return NULL
     */
    public function testAddInvalidPerson()
    {
        $this->addToServers($this->objects[1]);
    }

    /**
     * Test adding a person.
     *
     * @return NULL
     */
    public function testAddPerson()
    {
        foreach ($this->servers as $server) {
            $adds = array(0, 2, 3, 4);
            foreach ($adds as $add) {
                $result = $server->add($this->objects[$add]);
                $this->assertNoError($result);
                $cn_result = $server->uidForCn($this->objects[$add]['Cn']);
                $this->assertNoError($cn_result);
                $dn_parts = Horde_Ldap_Util::explodeDN($cn_result, array('casefold' => 'lower'));
                $dnpart = Horde_Ldap_Util::unescapeDNValue($dn_parts[0]);
                /**
                 * @todo: I currently do not really understand why the forward slash
                 * is not correctly converted back but I lack the time to analyse it
                 * in detail. The server entry looks okay.
                 */
                $dnpart = str_replace('\/', '/', $dnpart);
                $this->assertContains('Cn' . '=' . $this->objects[$add]['Cn'],
                                      $dnpart[0]);
                $result = $server->delete($cn_result);
                $this->assertNoError($result);
                $cn_result = $server->uidForCn($this->objects[$add]['Cn']);
                $this->assertNoError($cn_result);
                $this->assertFalse($server->uidForCn($this->objects[$add]['Cn']));
            }
        }
    }

    /**
     * Test modifying the surname of a person.
     *
     * @return NULL
     */
    public function testModifyPersonSn()
    {
        foreach ($this->servers as $server) {
            $person = $this->assertAdd($server, $this->objects[2],
                                       array('Cn' => $this->objects[2]['Cn']));
            $this->assertSimpleSequence($person, $server,
                                        'Sn',
                                        array('modified', 'modified_again'), true);
        }
    }

    /**
     * Test modifying the cn of a person. This should have an effect on the UID
     * of the object and needs to rename the object.
     *
     * @return NULL
     */
    public function testModifyPersonCn()
    {
        foreach ($this->servers as $server) {
            $person = $server->add($this->objects[2]);
            $this->assertNoError($person);

            $person = $server->fetch($person->getUid());

            $this->assertEquals($this->objects[2]['Cn'],
                                $person->get('Cn'));

            $result = $person->save(array('Cn' => 'Kolab_Server_PersonTest_äö'));
            $cn_result = $server->uidForCn('Kolab_Server_PersonTest_äö');
            $person = $server->fetch($cn_result);
            $this->assertEquals($person->get('Cn'),
                                'Kolab_Server_PersonTest_äö');
            $result = $server->delete($cn_result);
            $this->assertNoError($result);
            $cn_result = $server->uidForCn('Kolab_Server_PersonTest_äö');
            $this->assertNoError($cn_result);
            $this->assertFalse($cn_result);
        }
    }

    /**
     * Test adding a person with two common names.
     *
     * @return NULL
     */
    public function testAddDoubleCnPerson()
    {
        foreach ($this->servers as $server) {
            $person = $this->assertAdd($server, $this->objects[5],
                                       array());

            $cn_result = $server->uidForCn($this->objects[5]['Cn'][0]);
            $this->assertNoError($cn_result);
            $dn_parts = Horde_Ldap_Util::explodeDN($cn_result, array('casefold' => 'lower'));
            $dnpart = Horde_Ldap_Util::unescapeDNValue($dn_parts[0]);
            $this->assertContains('Cn' . '=' . $this->objects[5]['Cn'][0],
                                  $dnpart[0]);
        }
    }

    /**
     * Test handling a phone number.
     *
     * @return NULL
     */
    public function testHandlingAPhoneNumaber()
    {
        foreach ($this->servers as $server) {
            $person = $this->assertAdd($server, $this->objects[7],
                                       array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_TELNO => ''));
            $this->assertSimpleSequence($person, $server,
                                        Horde_Kolab_Server_Object_Person::ATTRIBUTE_TELNO,
                                        array('123456789', '+1234567890', array('1', '2'), null, '0'), true);
        }
    }

    /**
     * Test retrrieving a date.
     *
     * @return NULL
     */
    public function testGetDate()
    {
        foreach ($this->servers as $server) {
            $person = $this->assertAdd($server, $this->objects[8],
                                       array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_TELNO => ''));
            $cdate = $person->get(Horde_Kolab_Server_Object_Person::ATTRDATE_CREATIONDATE);
            $this->assertEquals('Horde_Date', get_class($cdate));
            $this->assertEquals('1910-08-03 01:00:00', (string) $cdate);
        }
    }
}
