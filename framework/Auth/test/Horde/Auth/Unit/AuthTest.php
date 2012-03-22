<?php
/**
 * Test the Horde_Auth:: class.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @link       http://pear.horde.org/index.php?package=Auth
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';


class Horde_Auth_Unit_AuthTest extends Horde_Auth_TestCase
{
    /**
     * @dataProvider getCredentials
     */
    public function testGetSalt($encryption, $password, $salt)
    {
        $this->assertEquals($salt, Horde_Auth::getSalt($encryption, $password, 'foobar'));
    }

    /**
     * @dataProvider getCredentials
     */
    public function testGetCryptedPassword($encryption, $password, $salt)
    {
        $this->assertEquals($password, Horde_Auth::getCryptedPassword('foobar', $password, $encryption, false));
    }
}