<?php
/**
 * Test the kolabExternalPop3Account object.
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
 * Test the kolabExternalPop3Account object.
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
class Horde_Kolab_Server_Integration_Kolabpop3accountTest extends Horde_Kolab_Server_Integration_Scenario
{
    /**
     * Objects used within this test
     *
     * @var array
     */
    private $objects = array(
        /* Default bank account owner */
        array(
            'type' => 'Horde_Kolab_Server_Object_Kolabinetorgperson',
            'givenName'    => 'Frank',
            'Sn'           => 'Mustermann',
            'Userpassword' => 'Kolab_Server_OrgPersonTest_123',
        ),
        /* Default account */
        array(
            'type' => 'Horde_Kolab_Server_Object_Kolabpop3account',
            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_MAIL      => 'frank@example.com',
            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER    => 'pop.example.com',
            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_LOGINNAME => 'frank',
            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_PASSWORD  => 'test',
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
            $person = $this->assertAdd($server, $this->objects[0],
                                       array(Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SID => ''));
            $account_data = $this->objects[1];
            $account_data[Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_OWNERUID] = $person->getUid();
            $a = new Horde_Kolab_Server_Object_Kolabpop3account($server, null, $account_data);
            $this->assertContains(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_MAIL . '=' . $this->objects[1][Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_MAIL],
                                  $a->get(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_UID));
        }
    }

    /**
     * Test adding an invalid Account.
     *
     * @expectedException Horde_Kolab_Server_Exception
     *
     * @return NULL
     */
    public function testAddInvalidAccount()
    {
        $this->addToServers($this->objects[1]);
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
                                       array(Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SID => ''));
            $account_data = $this->objects[1];
            $account_data[Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_OWNERUID] = $person->getUid();
            $account = $this->assertAdd($server, $account_data,
                                        array(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_OWNERUID => $person->getUid()));
            $this->assertEasyAttributes($account, $server,
                                        array(
                                            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_PASSWORD => array(
                                                'something',
                                                'somewhere',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_DESCRIPTION => array(
                                                'something',
                                                'somewhere',
                                                null,
                                                '',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER => array(
                                                'something',
                                                'somewhere',
                                                array('a', 'b'),
                                            ),
                                            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_PORT => array(
                                                '110',
                                                '111',
                                                null,
                                                '',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_USESSL => array(
                                                'TRUE',
                                                'FALSE',
                                                null,
                                                '',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_USETLS => array(
                                                'TRUE',
                                                'FALSE',
                                                null,
                                                '',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_LOGINMETHOD => array(
                                                'something',
                                                'somewhere',
                                                null,
                                                array('a', 'b'),
                                                '',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_CHECKCERTIFICATE => array(
                                                'TRUE',
                                                'FALSE',
                                                null,
                                                '',
                                            ),
                                            Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_KEEPMAILONSERVER => array(
                                                'TRUE',
                                                'FALSE',
                                                null,
                                                '',
                                            ),
                                        )
            );
        }
    }

    /**
     * Test modifying the attributes required for the UID of the account. This
     * should lead to renaming object.
     *
     * @return NULL
     */
    public function testModifyUidElements()
    {
        foreach ($this->servers as $server) {
            $person = $this->assertAdd($server, $this->objects[0],
                                       array(Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SID => ''));
            $account_data = $this->objects[1];
            $account_data[Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_OWNERUID] = $person->getUid();
            $account = $server->add($account_data);
            $account = $server->fetch($account->getUid());

            $this->assertEquals($this->objects[1][Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER],
                                $account->get(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER));

            $result = $account->save(array(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER => 'pop3s.example.com'));

            $account = $server->fetch($account->getUid());

            $this->assertContains(
                'pop3s.example.com',
                $account->get(Horde_Kolab_Server_Object_Kolabpop3account::ATTRIBUTE_SERVER, false)
            );

            $this->assertContains('frank@example.com', $account->getUid());

            $result = $server->delete($account->getUid());
        }
    }
}
