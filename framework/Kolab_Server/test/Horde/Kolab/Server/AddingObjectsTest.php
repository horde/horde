<?php
/**
 * Adding objects to the server.
 *
 * $Horde: framework/Kolab_Server/test/Horde/Kolab/Server/AddingObjectsTest.php,v 1.3 2009/01/06 17:49:27 jan Exp $
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
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Server.php';

/**
 * Adding objects to the server.
 *
 * $Horde: framework/Kolab_Server/test/Horde/Kolab/Server/AddingObjectsTest.php,v 1.3 2009/01/06 17:49:27 jan Exp $
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
class Horde_Kolab_Server_AddingObjectsTest extends Horde_Kolab_Test_Server {
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
            ->then('the result should be an object of type', KOLAB_OBJECT_USER);
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
            ->when('adding a Kolab server object', $user)
            ->then('the result should indicate an error with', $error);
    }

}
