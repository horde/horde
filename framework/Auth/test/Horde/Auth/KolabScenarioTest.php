<?php
/**
 * Kolab authentication scenarios.
 *
 * $Horde: framework/Auth/tests/Horde/Auth/KolabScenarioTest.php,v 1.3 2009/03/20 23:38:13 wrobel Exp $
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Auth
 */

/**
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Storage.php';

/**
 * Kolab authentication scenarios.
 *
 * $Horde: framework/Auth/tests/Horde/Auth/KolabScenarioTest.php,v 1.3 2009/03/20 23:38:13 wrobel Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Auth_KolabScenarioTest extends Horde_Kolab_Test_Storage
{
    /**
     * Test loggin in after a user has been added.
     *
     * @scenario
     *
     * @return NULL
     */
    public function login()
    {
        $test_user = $this->provideBasicUserOne();

        $this->given('a populated Kolab setup')
            ->and('the Kolab auth driver has been selected')
            ->when('logging in as a user with a password', $test_user['mail'],
                  $test_user['userPassword'])
            ->then('the login was successful');
    }
}