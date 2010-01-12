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
        $conf['basedn'] = 'dc=test';
        $conf['mock']   = true;
        $conf['data']   = array(
            'dn=user,dc=test' => array(
                'dn' => 'dn=user,dc=test',
                'data' => array(
                    'uid' => array('user'),
                    'mail' => array('user@example.org'),
                    'objectClass' => array('top', 'kolabInetOrgPerson'),
                )
            )
        );
        $server_factory = new Horde_Kolab_Server_Factory_Configuration(
            $conf
        );

        $this->composite = $server_factory->getComposite();
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
