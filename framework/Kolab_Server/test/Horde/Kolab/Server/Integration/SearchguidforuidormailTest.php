<?php
/**
 * Test the "GuidForUidOrMail" search using the mock driver.
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
require_once dirname(__FILE__) . '/../LdapTestCase.php';

/**
 * Test the "GuidForUidOrMail" search using the mock driver.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Integration_SearchguidforuidormailTest
extends Horde_Kolab_Server_LdapTestCase
{
    public function setUp()
    {
        $connection = new Horde_Kolab_Server_Connection_Mock(
            new Horde_Kolab_Server_Connection_Mock_Ldap(
                array('basedn' => 'dc=test'),
                array(
                    'dn=user,dc=test' => array(
                        'dn' => 'dn=user,dc=test',
                        'data' => array(
                            'uid' => array('user'),
                            'mail' => array('user@example.org'),
                            'objectClass' => array('top', 'kolabInetOrgPerson'),
                        )
                    )
                )
            )
        );

        $this->composite = new Horde_Kolab_Server_Composite(
            new Horde_Kolab_Server_Ldap_Standard(
                $connection,
                'dc=test'
            ),
            new Horde_Kolab_Server_Objects_Base(),
            new Horde_Kolab_Server_Structure_Kolab(),
            new Horde_Kolab_Server_Search_Base(),
            new Horde_Kolab_Server_Schema_Base()
        );
        $this->composite->server->connectGuid();
    }

    public function testSearchingForUnknownUserReturnsEmptyGuid()
    {
        $this->composite->search->searchGuidForUidOrMail('unknown');
    }

    public function testSearchingForUserByMailReturnsTheGuid()
    {
        $this->assertEquals(
            'dn=user,dc=test',
            $this->composite->search->searchGuidForUidOrMail('user@example.org')
        );
    }

    public function testSearchingForUserByUidReturnsTheGuid()
    {
        $this->assertEquals(
            'dn=user,dc=test',
            $this->composite->search->searchGuidForUidOrMail('user')
        );
    }
}
