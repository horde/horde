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
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Test the inetOrgPerson object.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_InetorgpersonTest extends Horde_Kolab_Test_Server
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
            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME    => 'Frank',
            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_SN           => 'Mustermann',
            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_OrgPersonTest_123',
        ),
        /* Invalid person (no sn) */
        array(
            'type' => 'Horde_Kolab_Server_Object_Inetorgperson',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_OrgPersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_OrgPersonTest_123',
        ),
        /* Person with middle names */
        array(
            'type' => 'Horde_Kolab_Server_Object_Inetorgperson',
            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME    => 'Frank',
            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES  => 'Günter Eloic',
            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_SN           => 'Mustermann',
            Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_OrgPersonTest_123',
        ),
    );

    /**
     * Provide different server types.
     *
     * @return array The different server types.
     */
    public function &provideServers()
    {
        $servers = array();
        /**
         * We always use the test server
         */
        $servers[] = array($this->prepareEmptyKolabServer());
        if (false) {
            $real = $this->prepareLdapKolabServer();
            if (!empty($real)) {
                $servers[] = array($real);
            }
        }
        return $servers;
    }

    /**
     * Test ID generation for a person.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testGenerateId($server)
    {
        $a = new Horde_Kolab_Server_Object_Inetorgperson($server, null, $this->objects[0]);
        $this->assertContains('Frank Mustermann',
                              $a->get(Horde_Kolab_Server_Object_Person::ATTRIBUTE_UID));
    }

    /**
     * Test adding an invalid person.
     *
     * @dataProvider provideServers
     * @expectedException Horde_Kolab_Server_Exception
     *
     * @return NULL
     */
    public function testAddInvalidPerson($server)
    {
        $result = $server->add($this->objects[1]);
    }

    /**
     * Test a person with middle names.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testHandlePersonWithMiddleNames($server)
    {
        $person = $this->assertAdd($server, $this->objects[2],
                                   array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => $this->objects[2][Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME],
                                         Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => $this->objects[2][Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES]));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => 'Kolab_Server_InetorgpersonTest_123$123',
                                      Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => 'Kolab_Server_InetorgpersonTest_123$123'),
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => 'Kolab_Server_InetorgpersonTest_123$123',
                                      Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => 'Kolab_Server_InetorgpersonTest_123$123'));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => 'Kolab_Server_InetorgpersonTest_123$456',
                                      Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => ''),
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => 'Kolab_Server_InetorgpersonTest_123$456',
                                      Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => ''));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => 'Kolab_Server_InetorgpersonTest_789'),
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => 'Kolab_Server_InetorgpersonTest_123$456',
                                      Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => 'Kolab_Server_InetorgpersonTest_789'));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => '',
                                      Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => ''),
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => '',
                                      Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => ''));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => 'Kolab_Server_InetorgpersonTest_789'),
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => '',
                                      Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => 'Kolab_Server_InetorgpersonTest_789'));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => 'Frank',
                                      Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => ''),
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => 'Frank',
                                      Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_MIDDLENAMES => ''));
    }

    /**
     * Test handling labeled URIs.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testHandleLabeledUris($server)
    {
        $person = $this->assertAdd($server, $this->objects[0],
                                   array(Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME => $this->objects[0][Horde_Kolab_Server_Object_Inetorgperson::ATTRIBUTE_GIVENNAME],
                                         Horde_Kolab_Server_Object_Inetorgperson::ATTRARRAY_LABELEDURI => array()));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRARRAY_LABELEDURI => array('a' => 'http://a.example.com',
                                                                                                             'b' => 'http://b.example.com')),
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRARRAY_LABELEDURI => array('a' => array('http://a.example.com'),
                                                                                                             'b' => array('http://b.example.com'))));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRARRAY_LABELEDURI => array('a' => 'http://a.example.com',
                                                                                                             'b' => 'http://b.example.com',
                                                                                                             'c' => 'http://c.example.com')),
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRARRAY_LABELEDURI => array('a' => array('http://a.example.com'),
													     'b' => array('http://b.example.com'),
													     'c' => array('http://c.example.com'))));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRARRAY_LABELEDURI => array()),
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRARRAY_LABELEDURI => array()));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRARRAY_LABELEDURI => array('a' => 'http://a.example.com')),
                                array(Horde_Kolab_Server_Object_Inetorgperson::ATTRARRAY_LABELEDURI => array('a' => array('http://a.example.com'))));
    }


    /**
     * Test handling the home postal address.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testHandlingHomePostalAddress($server)
    {
        //FIXME
    }

    /**
     * Test handling easy attributes.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testEasyAttributes($server)
    {
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
