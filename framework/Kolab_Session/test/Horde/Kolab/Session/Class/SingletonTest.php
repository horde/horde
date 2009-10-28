<?php
/**
 * Test the Kolab session singleton pattern.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Kolab session singleton pattern.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Class_SingletonTest extends Horde_Kolab_Session_SessionTestCase
{
    public function setUp()
    {
        global $conf;

        /** Provide a minimal configuration for the server */
        $conf['kolab']['ldap']['basedn'] = '';
    }

    public function testMethodSingletonHasResultHordekolabsession()
    {
        $this->assertType(
            'Horde_Kolab_Session',
            Horde_Kolab_Session_Singleton::singleton(
                'user', array('password' => 'pass')
            )
        );
    }

    public function testMethodSingletonHasResultHordekolabsessionAlwaysTheSameIfTheSessionIsValid()
    {
        $session1 = Horde_Kolab_Session_Singleton::singleton(
            'user', array('password' => 'pass')
        );
        $session2 = Horde_Kolab_Session_Singleton::singleton(
            'user', array('password' => 'pass')
        );
        $this->assertSame($session1, $session2);
    }
}