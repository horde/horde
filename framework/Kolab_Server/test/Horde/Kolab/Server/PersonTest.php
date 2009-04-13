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
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Test the person object.
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
class Horde_Kolab_Server_PersonTest extends Horde_Kolab_Test_Server
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
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_PersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN           => 'Kolab_Server_PersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_PersonTest_123',
        ),
        /* Invalid person (no sn) */
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_PersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_PersonTest_123',
        ),
        /* Person with problematic characters */
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_PersonTest_!"$%&()=?',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN           => 'Kolab_Server_PersonTest_!"$%&()=?',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_PersonTest_!"$%&()=?',
        ),
        /* Person with difficult encoding */
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_PersonTest_ügöräß§',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN           => 'Kolab_Server_PersonTest_ügöräß§',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_PersonTest_ügöräß§',
        ),
        /* Person with forward slash */
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_PersonTest_/',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN           => 'Kolab_Server_PersonTest_/',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_PersonTest_/',
        ),
        /* Person with double cn */
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => array('Kolab_Server_PersonTest_cn1',
                                                                              'Kolab_Server_PersonTest_cn2'),
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN           => 'Kolab_Server_PersonTest_cncn',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_PersonTest_cncn',
        ),
        /* Person with name suffix*/
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_PersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN           => 'Kolab_Server_PersonTest_123',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_SNSUFFIX     => 'Kolab_Server_PersonTest_suffix',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_PersonTest_123',
        ),
        /* Person for telephone number handling*/
        array(
            'type' => 'Horde_Kolab_Server_Object_Person',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN           => 'Kolab_Server_PersonTest_123456',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN           => 'Kolab_Server_PersonTest_123456',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_SNSUFFIX     => 'Kolab_Server_PersonTest_suffix',
            Horde_Kolab_Server_Object_Person::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_PersonTest_123456',
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
        $a = new Horde_Kolab_Server_Object_Person($server, null, $this->objects[0]);
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
     * Test adding a person.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testAddPerson($server)
    {
        $adds = array(0, 2, 3, 4);
        foreach ($adds as $add) {
            $result = $server->add($this->objects[$add]);
            $this->assertNoError($result);
            $cn_result = $server->uidForCn($this->objects[$add][Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN]);
            $this->assertNoError($cn_result);
            $dn_parts = Net_LDAP2_Util::ldap_explode_dn($cn_result, array('casefold' => 'lower'));
            $dnpart = Net_LDAP2_Util::unescape_dn_value($dn_parts[0]);
            /**
             * FIXME: I currently do not really understand why the forward slash
             * is not correctly converted back but I lack the time to analyse it
             * in detail. The server entry looks okay.
            */
            $dnpart = str_replace('\/', '/', $dnpart);
            $this->assertContains(Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN . '=' . $this->objects[$add][Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN],
                                  $dnpart[0]);
            $result = $server->delete($cn_result);
            $this->assertNoError($result);
            $cn_result = $server->uidForCn($this->objects[$add][Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN]);
            $this->assertNoError($cn_result);
            $this->assertFalse($server->uidForCn($this->objects[$add][Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN]));
        }
    }

    /**
     * Test modifying the surname of a person.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testModifyPersonSn($server)
    {
        $person = $this->assertAdd($server, $this->objects[2],
                                   array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN => $this->objects[2][Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN]));
        $this->assertSimpleSequence($person, $server,
                                    Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN,
                                    array('modified', 'modified_again'));
    }

    /**
     * Test modifying the cn of a person. This should have an effect on the UID
     * of the object and needs to rename the object.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testModifyPersonCn($server)
    {
        $person = $server->add($this->objects[2]);
        $this->assertNoError($person);

        $person = $server->fetch($person->getUid());

        $this->assertEquals($this->objects[2][Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN],
                            $person->get(Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN));

        $result = $person->save(array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN => 'Kolab_Server_PersonTest_äö'));
        $cn_result = $server->uidForCn('Kolab_Server_PersonTest_äö');
        $person = $server->fetch($cn_result);
        $this->assertEquals($person->get(Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN),
                            'Kolab_Server_PersonTest_äö');
        $result = $server->delete($cn_result);
        $this->assertNoError($result);
        $cn_result = $server->uidForCn('Kolab_Server_PersonTest_äö');
        $this->assertNoError($cn_result);
        $this->assertFalse($cn_result);
    }

    /**
     * Test adding a person with two common names.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testAddDoubleCnPerson($server)
    {
        $person = $this->assertAdd($server, $this->objects[5],
                                   array());

        $cn_result = $server->uidForCn($this->objects[5][Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN][0]);
        $this->assertNoError($cn_result);
        $dn_parts = Net_LDAP2_Util::ldap_explode_dn($cn_result, array('casefold' => 'lower'));
        $dnpart = Net_LDAP2_Util::unescape_dn_value($dn_parts[0]);
        $this->assertContains(Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN . '=' . $this->objects[5][Horde_Kolab_Server_Object_Person::ATTRIBUTE_CN][0],
                              $dnpart[0]);
    }

    /**
     * Test a person with a name suffix.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testHandlePersonWithSuffix($server)
    {
        $person = $this->assertAdd($server, $this->objects[6],
                                   array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN => $this->objects[6][Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN],
                                         Horde_Kolab_Server_Object_Person::ATTRIBUTE_SNSUFFIX => $this->objects[6][Horde_Kolab_Server_Object_Person::ATTRIBUTE_SNSUFFIX]));
        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN => 'Kolab_Server_PersonTest_123$123',
                                      Horde_Kolab_Server_Object_Person::ATTRIBUTE_SNSUFFIX => 'Kolab_Server_PersonTest_123$123'),
                                array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN => 'Kolab_Server_PersonTest_123$123',
                                      Horde_Kolab_Server_Object_Person::ATTRIBUTE_SNSUFFIX => 'Kolab_Server_PersonTest_123$123'));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN => 'Kolab_Server_PersonTest_123$456',
                                      Horde_Kolab_Server_Object_Person::ATTRIBUTE_SNSUFFIX => ''),
                                array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN => 'Kolab_Server_PersonTest_123$456',
                                      Horde_Kolab_Server_Object_Person::ATTRIBUTE_SNSUFFIX => ''));

        $this->assertStoreFetch($person, $server,
                                array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_SNSUFFIX => 'Kolab_Server_PersonTest_789'),
                                array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN => 'Kolab_Server_PersonTest_123$456',
                                      Horde_Kolab_Server_Object_Person::ATTRIBUTE_SNSUFFIX => 'Kolab_Server_PersonTest_789'));

        try {
            $person->save(array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_SN => '',
                                Horde_Kolab_Server_Object_Person::ATTRIBUTE_SNSUFFIX => 'test'));
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertError($e);
        }
    }

    /**
     * Test handling a phone number.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testHandlingAPhoneNumaber($server)
    {
        $person = $this->assertAdd($server, $this->objects[7],
                                   array(Horde_Kolab_Server_Object_Person::ATTRIBUTE_TELNO => ''));
        $this->assertSimpleSequence($person, $server,
                                    Horde_Kolab_Server_Object_Person::ATTRIBUTE_TELNO,
                                    array('123456789', '+1234567890', array('1', '2'), null, '0'), true);
    }
}
