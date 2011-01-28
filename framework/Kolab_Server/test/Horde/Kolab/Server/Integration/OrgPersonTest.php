<?php
/**
 * Test the organizationalPerson object.
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
 * Test the organizationalPerson object.
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
class Horde_Kolab_Server_Integration_OrgPersonTest extends Horde_Kolab_Server_Integration_Scenario
{
    /**
     * Objects used within this test
     *
     * @var array
     */
    private $objects = array(
        /* Default organizationalPerson */
        array(
            'type' => 'Horde_Kolab_Server_Object_Organizationalperson',
            'Cn'           => 'Kolab_Server_OrgPersonTest_123',
            'Sn'           => 'Kolab_Server_OrgPersonTest_123',
            'Userpassword' => 'Kolab_Server_OrgPersonTest_123',
        ),
        /* Invalid person (no sn) */
        array(
            'type' => 'Horde_Kolab_Server_Object_Organizationalperson',
            'Cn'           => 'Kolab_Server_OrgPersonTest_123',
            'Userpassword' => 'Kolab_Server_OrgPersonTest_123',
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
            $a = new Horde_Kolab_Server_Object_Organizationalperson($server, null, $this->objects[0]);
            $this->assertContains(Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN . '=' . $this->objects[0][Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN],
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
     * Test handling simple attributes.
     *
     * @return NULL
     */
    public function testSimpleAttributes()
    {
        foreach ($this->servers as $server) {
            $person = $this->assertAdd($server, $this->objects[0],
                                       array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE => ''));
            $this->assertSimpleAttributes($person, $server,
                                          array(
                                          ));
        }
    }

    /**
     * Test handling the postal address.
     *
     * @return NULL
     */
    public function testHandlingAPostalAddress()
    {
        foreach ($this->servers as $server) {
            $person = $this->assertAdd($server, $this->objects[0],
                                       array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => 'Kolab_Server_OrgPersonTest_123$$ '));

            $this->assertStoreFetch($person, $server,
                                    array('Sn' => 'Kolab_Server_OrgPersonTest_456'),
                                    array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => array('Kolab_Server_OrgPersonTest_456$$ ')));

            $this->assertStoreFetch($person, $server,
                                    array('Sn' => 'Kolab_Server_OrgPersonTest_123',
                                          Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => 'Street 1',
                                          Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALCODE => '12345',
                                          Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CITY => 'Nowhere'),
                                    array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => array('Kolab_Server_OrgPersonTest_123$Street 1$12345 Nowhere')));
            $this->assertStoreFetch($person, $server,
                                    array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTOFFICEBOX => 'öäü/)(="§%$&§§$\'*',
                                          Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => null),
                                    array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => array('Kolab_Server_OrgPersonTest_123$öäü/)(="§%\24&§§\24\'*$12345 Nowhere')));

            $this->assertStoreFetch($person, $server,
                                    array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => null,
                                          Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALCODE => null,
                                          //@todo: Why does this need a string?
                                          Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => '',
                                          Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTOFFICEBOX => null,
                                          Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CITY => null),
                                    array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => array('Kolab_Server_OrgPersonTest_123$$ ')));
        }
    }


    /**
     * Test handling easy attributes.
     *
     * @return NULL
     */
    public function testEasyAttributes()
    {
        foreach ($this->servers as $server) {
            $person = $this->assertAdd($server, $this->objects[0],
                                       array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE => ''));
            $this->assertEasyAttributes($person, $server,
                                        array(
                                            Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE => array(
                                                'Teacher',
                                                '0',
                                                'Something',
                                                null,
                                                '',
                                                array('This', 'That'),
                                            ),
                                            Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_FAX => array(
                                                '123456789',
                                                '+1234567890',
                                                array('1', '2'),
                                                '0',
                                                //@todo: How to delete?
                                                //null
                                            )
                                        )
            );
        }
    }
}
