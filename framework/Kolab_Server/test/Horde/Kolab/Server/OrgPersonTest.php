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
        $result = $server->add($this->objects[0]);
        $this->assertNoError($result);

        $cn = $this->objects[0][Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CN];

        $cn_result = $server->uidForCn($cn);
        $this->assertNoError($cn_result);

        $person = $server->fetch($cn_result);
        $this->assertNoError($person);

        $this->assertEquals($person->get(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE),
                            '');

        $person->save(array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE => 'Teacher'));

        $cn_result = $server->uidForCn($cn);
        $this->assertNoError($cn_result);

        $person = $server->fetch($cn_result);
        $this->assertNoError($person);

        $this->assertEquals($person->get(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE),
                            'Teacher');

        $person->save(array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE => 'öäü/)(="§%$&§§$\'*'));

        $cn_result = $server->uidForCn($cn);
        $this->assertNoError($cn_result);

        $person = $server->fetch($cn_result);
        $this->assertNoError($person);

        $this->assertEquals($person->get(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE),
                            'öäü/)(="§%$&§§$\'*');


        $person->save(array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE => ''));

        $cn_result = $server->uidForCn($cn);
        $this->assertNoError($cn_result);

        $person = $server->fetch($cn_result);
        $this->assertNoError($person);

        $this->assertEquals($person->get(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_JOBTITLE),
                            '');

        $result = $server->delete($cn_result);
        $this->assertNoError($result);
        $cn_result = $server->uidForCn($this->objects[0][Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CN]);
        $this->assertNoError($cn_result);
        $this->assertFalse($server->uidForCn($this->objects[0][Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CN]));
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
        $result = $server->add($this->objects[0]);
        $this->assertNoError($result);

        $cn = $this->objects[0][Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CN];

        $cn_result = $server->uidForCn($cn);
        $this->assertNoError($cn_result);

        $person = $server->fetch($cn_result);
        $this->assertNoError($person);

        $this->assertEquals($person->get(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS),
                            'Kolab_Server_OrgPersonTest_123$$ ');

        $person->save(array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => 'Street 1',
                            Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALCODE => '12345',
                            Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CITY => 'Nowhere'));

        $cn_result = $server->uidForCn($cn);
        $this->assertNoError($cn_result);

        $person = $server->fetch($cn_result);
        $this->assertNoError($person);

        $this->assertEquals('Kolab_Server_OrgPersonTest_123$Street 1$12345 Nowhere',
                            $person->get(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS));

        $person->save(array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTOFFICEBOX => 'öäü/)(="§%$&§§$\'*',
                            Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => ''));

        $cn_result = $server->uidForCn($cn);
        $this->assertNoError($cn_result);

        $person = $server->fetch($cn_result);
        $this->assertNoError($person);

        $this->assertEquals('Kolab_Server_OrgPersonTest_123$öäü/)(="§%\5c24&§§\5c24\'*$12345 Nowhere',
                            $person->get(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS));


        $person->save(array(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_STREET => '',
                            Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALCODE => '',
                            Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTOFFICEBOX => '',
                            Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CITY => ''));

        $cn_result = $server->uidForCn($cn);
        $this->assertNoError($cn_result);

        $person = $server->fetch($cn_result);
        $this->assertNoError($person);

        $this->assertEquals('Kolab_Server_OrgPersonTest_123$$ ',
                            $person->get(Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_POSTALADDRESS));

        $result = $server->delete($cn_result);
        $this->assertNoError($result);
        $cn_result = $server->uidForCn($this->objects[0][Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CN]);
        $this->assertNoError($cn_result);
        $this->assertFalse($server->uidForCn($this->objects[0][Horde_Kolab_Server_Object_Organizationalperson::ATTRIBUTE_CN]));
    }
}
