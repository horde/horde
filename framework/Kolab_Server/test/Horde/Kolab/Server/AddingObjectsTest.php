<?php
/**
 * Adding objects to the server.
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
 * Adding objects to the server.
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
class Horde_Kolab_Server_AddingObjectsTest extends Horde_Kolab_Test_Server
{
    /**
     * Test adding valid users.
     *
     * @param array $user The user to add.
     *
     * @scenario
     * @dataProvider validUsers
     *
     * @return NULL
     */
    public function addingValidUser($user)
    {
        $this->given('an empty Kolab server')
            ->when('adding a Kolab server object', $user)
            ->then('the result should be an object of type', 'Horde_Kolab_Server_Object_user');
    }

    /**
     * Test adding invalid users.
     *
     * @param array  $user  The user to add.
     * @param string $error The error to expect.
     *
     * @scenario
     * @dataProvider provideInvalidUsers
     *
     * @return NULL
     */
    public function addingInvalidUser($user, $error)
    {
        $this->given('an empty Kolab server')
            ->when('adding an invalid Kolab server object', $user)
            ->then('the result should indicate an error with', $error);
    }

}
