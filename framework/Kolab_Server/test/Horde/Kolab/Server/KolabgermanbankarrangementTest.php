<?php
/**
 * Test the kolabGermanBankArrangement object.
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
 * Test the kolabGermanBankArrangement object.
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
class Horde_Kolab_Server_KolabgermanbankarrangementTest extends Horde_Kolab_Test_Server
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
            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_GIVENNAME    => 'Frank',
            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SN           => 'Mustermann',
            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_USERPASSWORD => 'Kolab_Server_OrgPersonTest_123',
        ),
        /* Default account */
        array(
            'type' => 'Horde_Kolab_Server_Object_Kolabgermanbankarrangement',
            Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_NUMBER   => '0123456789',
            Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_BANKCODE => '1111111',
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
        if (true) {
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
        $person = $this->assertAdd($server, $this->objects[0],
                                   array(Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SID => ''));
        $account_data = $this->objects[1];
        $account_data[Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_OWNERUID] = $person->getUid();
        $a = new Horde_Kolab_Server_Object_Kolabgermanbankarrangement($server, null, $account_data);
        $this->assertContains(Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_NUMBER . '=' . $this->objects[1][Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_NUMBER],
                              $a->get(Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_UID));
    }

    /**
     * Test adding an invalid Account.
     *
     * @dataProvider provideServers
     * @expectedException Horde_Kolab_Server_Exception
     *
     * @return NULL
     */
    public function testAddInvalidAccount($server)
    {
        $result = $server->add($this->objects[1]);
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
                                   array(Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SID => ''));
        $account_data = $this->objects[1];
        $account_data[Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_OWNERUID] = $person->getUid();
        $account = $this->assertAdd($server, $account_data,
                                    array(Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_OWNERUID => $person->getUid()));
        $this->assertEasyAttributes($account, $server,
                                    array(
                                        Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_HOLDER => array(
                                            'something',
                                            'somewhere',
                                            null,
                                            array('a', 'b'),
                                            '',
                                        ),
                                        Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_BANKNAME => array(
                                            'something',
                                            'somewhere',
                                            null,
                                            array('a', 'b'),
                                            '',
                                        ),
                                        Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_INFO => array(
                                            'something',
                                            'somewhere',
                                            null,
                                            array('a', 'b'),
                                            '',
                                        ),
                                    )
        );
    }

    /**
     * Test modifying the account number of an account. This should have an
     * effect on the UID of the object and needs to rename the object.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function testModifyAccountNumber($server)
    {
        $person = $this->assertAdd($server, $this->objects[0],
                                   array(Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SID => ''));
        $account_data = $this->objects[1];
        $account_data[Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_OWNERUID] = $person->getUid();
        $account = $server->add($account_data);
        $this->assertNoError($account);

        $account = $server->fetch($account->getUid());
        $this->assertNoError($account);

        $this->assertEquals($this->objects[1][Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_NUMBER],
                            $account->get(Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_NUMBER));

        $result = $account->save(array(Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_NUMBER => '66666666'));
        $this->assertNoError($result);

        $account = $server->fetch($account->getUid());
        $this->assertNoError($account);

        $this->assertEquals($account->get(Horde_Kolab_Server_Object_Kolabgermanbankarrangement::ATTRIBUTE_NUMBER),
                            '66666666');

        $result = $server->delete($account->getUid());
        $this->assertNoError($result);
    }
}
