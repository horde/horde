<?php
/**
 * Test the inetOrgPerson object.
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
 * Test the inetOrgPerson object.
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
class Horde_Kolab_Server_Integration_InetorgpersonTest extends Horde_Kolab_Server_Integration_Scenario
{
    /**
     * Objects used within this test
     *
     * @var array
     */
    private $objects = array(
        /* Default inetOrgPerson */
        array(
            'type' => 'Horde_Kolab_Server_Object_Inetorgperson',
            'givenName'    => 'Frank',
            'Sn'           => 'Mustermann',
            'Userpassword' => 'Kolab_Server_OrgPersonTest_123',
        ),
        /* Invalid person (no sn) */
        array(
            'type' => 'Horde_Kolab_Server_Object_Inetorgperson',
            'Cn'           => 'Kolab_Server_OrgPersonTest_123',
            'Userpassword' => 'Kolab_Server_OrgPersonTest_123',
        ),
        /* Person with middle names */
        array(
            'type' => 'Horde_Kolab_Server_Object_Inetorgperson',
            'givenName'    => 'Frank',
            'Middlenames'  => 'Günter Eloic',
            'Sn'           => 'Mustermann',
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
            $a = new Horde_Kolab_Server_Object_Inetorgperson($server, null, $this->objects[0]);
            $this->assertContains('Frank Mustermann',
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
     * Test a person with middle names.
     *
     * @return NULL
     */
    public function testHandlePersonWithMiddleNames()
    {
        foreach ($this->servers as $server) {
            $person = $this->assertAdd($server, $this->objects[2],
                                       array('givenName' => $this->objects[2]['givenName'],
                                             'Middlenames' => $this->objects[2]['Middlenames']));

            $this->assertStoreFetch($person, $server,
                                    array('givenName' => 'Kolab_Server_InetorgpersonTest_123$123',
                                          'Middlenames' => 'Kolab_Server_InetorgpersonTest_123$123'),
                                    array('givenName' => 'Kolab_Server_InetorgpersonTest_123$123',
                                          'Middlenames' => 'Kolab_Server_InetorgpersonTest_123$123'));

            $this->assertStoreFetch($person, $server,
                                    array('givenName' => 'Kolab_Server_InetorgpersonTest_123$456',
                                          'Middlenames' => ''),
                                    array('givenName' => 'Kolab_Server_InetorgpersonTest_123$456',
                                          'Middlenames' => ''));

            $this->assertStoreFetch($person, $server,
                                    array('Middlenames' => 'Kolab_Server_InetorgpersonTest_789'),
                                    array('givenName' => 'Kolab_Server_InetorgpersonTest_123$456',
                                          'Middlenames' => 'Kolab_Server_InetorgpersonTest_789'));

            $this->assertStoreFetch($person, $server,
                                    array('givenName' => '',
                                          'Middlenames' => ''),
                                    array('givenName' => '',
                                          'Middlenames' => ''));

            $this->assertStoreFetch($person, $server,
                                    array('Middlenames' => 'Kolab_Server_InetorgpersonTest_789'),
                                    array('givenName' => '',
                                          'Middlenames' => 'Kolab_Server_InetorgpersonTest_789'));

            $this->assertStoreFetch($person, $server,
                                    array('givenName' => 'Frank',
                                          'Middlenames' => ''),
                                    array('givenName' => 'Frank',
                                          'Middlenames' => ''));
        }
    }

    /**
     * Test handling labeled URIs.
     *
     * @return NULL
     */
    public function testHandleLabeledUris()
    {
        foreach ($this->servers as $server) {
            $person = $this->assertAdd($server, $this->objects[0],
                                       array('givenName' => $this->objects[0]['givenName'],
                                             'labelledURI' => array()));

            $this->assertStoreFetch($person, $server,
                                    array('labelledURI' => array('a' => 'http://a.example.com',
                                                                                                                 'b' => 'http://b.example.com')),
                                    array('labelledURI' => array('a' => array('http://a.example.com'),
                                                                                                                 'b' => array('http://b.example.com'))));

            $this->assertStoreFetch($person, $server,
                                    array('labelledURI' => array('a' => 'http://a.example.com',
                                                                                                                 'b' => 'http://b.example.com',
                                                                                                                 'c' => 'http://c.example.com')),
                                    array('labelledURI' => array('a' => array('http://a.example.com'),
                                                                                                                 'b' => array('http://b.example.com'),
                                                                                                                 'c' => array('http://c.example.com'))));

            $this->assertStoreFetch($person, $server,
                                    array('labelledURI' => array()),
                                    array('labelledURI' => array()));

            $this->assertStoreFetch($person, $server,
                                    array('labelledURI' => array('a' => 'http://a.example.com')),
                                    array('labelledURI' => array('a' => array('http://a.example.com'))));
        }
    }


    /**
     * Test handling the home postal address.
     *
     * @return NULL
     */
    public function testHandlingHomePostalAddress()
    {
        //@todo
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
                                       array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_SID => ''));
            $this->assertEasyAttributes($person, $server,
                                        array(
                                            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_SID => array(
                                                'user',
                                                '0',
                                                'somebody',
                                                null,
                                                '',
                                                array('he', 'she'),
                                            ),
                                            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_ORGANIZATION => array(
                                                'them',
                                                '0',
                                                'somebody',
                                                null,
                                                '',
                                                array('they', 'we'),
                                            ),
                                            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_BUSINESSCATEGORY => array(
                                                'them',
                                                '0',
                                                'somebody',
                                                null,
                                                '',
                                                array('they', 'we'),
                                            ),
                                            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_HOMEPHONE => array(
                                                '123456789',
                                                '+1234567890',
                                                array('1', '2'),
                                                null,
                                                '0'
                                            ),
                                            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MOBILE => array(
                                                '123456789',
                                                '+1234567890',
                                                array('1', '2'),
                                                null,
                                                '0'
                                            ),
                                        )
            );
        }
    }
}
