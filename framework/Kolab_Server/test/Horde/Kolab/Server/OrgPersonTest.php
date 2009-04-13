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
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Test the organizationalPerson object.
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
class Horde_Kolab_Server_OrgPersonTest extends Horde_Kolab_Test_Server
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
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_OrgPersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN           => 'Kolab_Server_OrgPersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_OrgPersonTest_123',
        ),
        /* Invalid person (no sn) */
        array(
            'type' => 'Horde_Kolab_Server_Object_Organizationalperson',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_OrgPersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_OrgPersonTest_123',
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
        $a = new Horde_Kolab_Server_Object_Organizationalperson($server, null, $this->objects[0]);
        $this->assertContains(Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN . '=' . $this->objects[0][Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN],
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
     * Test handling a job title.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testHandlingAJobTitle($server)
    {
        $person = $this->assertAdd($server, $this->objects[0],
                                   array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE => ''));
        $this->assertSimpleSequence($person, $server,
                                    Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE,
                                    array('Teacher', 'öäü/)(="§%$&§§$\'*', '', '0'));
    }

    /**
     * Test handling the postal address.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testHandlingAPostalAddress($server)
    {
        $person = $this->assertAdd($server, $this->objects[0],
                                   array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESSRAW => 'Kolab_Server_OrgPersonTest_123$$$ '));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_SN => 'Kolab_Server_OrgPersonTest_456'),
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESSRAW => 'Kolab_Server_OrgPersonTest_456$$$ '));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_SN => 'Kolab_Server_OrgPersonTest_123',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => 'Street 1',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALCODE => '12345',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => 'c/o here',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CITY => 'Nowhere'),
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESSRAW => 'Kolab_Server_OrgPersonTest_123$c/o here$Street 1$12345 Nowhere'));
        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTOFFICEBOX => 'öäü/)(="§%$&§§$\'*',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => ''),
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESSRAW => 'Kolab_Server_OrgPersonTest_123$c/o here$öäü/)(="§%\24&§§\24\'*$12345 Nowhere'));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => '',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALCODE => '',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS => '',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTOFFICEBOX => '',
                                      Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CITY => ''),
                                array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESSRAW => 'Kolab_Server_OrgPersonTest_123$$$ '));
    }
}
